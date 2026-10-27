<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the Xentree Connect launcher.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_xentreeconnect;

/**
 * Tests for the Xentree Connect launcher.
 *
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\launcher
 */
final class launcher_test extends \basic_testcase {
    private const NOW = 1790000000;

    /** @var string PEM of a throwaway RSA key created for the test run. */
    private static string $pem;

    public static function setUpBeforeClass(): void {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        openssl_pkey_export($key, $pem);
        self::$pem = $pem;
    }

    private function config(array $overrides = []): array {
        return $overrides + [
            'baseurl' => 'https://my.xentree.ai',
            'clientid' => 'moodle-demo',
            'issuer' => 'https://moodle.example.com',
            'audience' => 'xentree-connect',
            'organizationid' => 'org-123',
            'kid' => 'moodle-key-1',
            'privatekeypem' => self::$pem,
        ];
    }

    private function launcher(array $overrides = []): launcher {
        return new launcher($this->config($overrides), fn() => self::NOW);
    }

    private function decode(string $segment): array {
        $json = base64_decode(strtr($segment, '-_', '+/'));
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_assertion_header_carries_kid_and_rs256(): void {
        [$header] = explode('.', $this->launcher()->build_assertion('moodle-user-7'));
        $this->assertSame(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'moodle-key-1'], $this->decode($header));
    }

    public function test_assertion_claims_match_the_xentree_contract(): void {
        $jwt = $this->launcher()->build_assertion('moodle-user-7', 'learner@example.org');
        $claims = $this->decode(explode('.', $jwt)[1]);

        $this->assertSame('https://moodle.example.com', $claims['iss']);
        $this->assertSame('xentree-connect', $claims['aud']);
        $this->assertSame('moodle-user-7', $claims['sub']);
        $this->assertSame('org-123', $claims['organization_id']);
        $this->assertSame(self::NOW, $claims['iat']);
        $this->assertSame(self::NOW + launcher::ASSERTION_TTL, $claims['exp']);
        $this->assertMatchesRegularExpression('/^moodle-[0-9a-f]{32}$/', $claims['jti']);
        $this->assertSame('learner@example.org', $claims['email']);
    }

    public function test_email_claim_is_omitted_when_empty_or_malformed(): void {
        foreach ([null, '', '   ', 'not-an-email'] as $email) {
            $claims = $this->decode(explode('.', $this->launcher()->build_assertion('moodle-user-7', $email))[1]);
            $this->assertArrayNotHasKey('email', $claims, 'email=' . var_export($email, true));
        }
    }

    public function test_jti_is_unique_per_assertion(): void {
        $launcher = $this->launcher();
        $first = $this->decode(explode('.', $launcher->build_assertion('moodle-user-7'))[1])['jti'];
        $second = $this->decode(explode('.', $launcher->build_assertion('moodle-user-7'))[1])['jti'];
        $this->assertNotSame($first, $second);
    }

    public function test_signature_verifies_against_the_published_jwks(): void {
        $launcher = $this->launcher();
        $jwt = $launcher->build_assertion('moodle-user-7');
        [$h, $p, $s] = explode('.', $jwt);

        $jwk = $launcher->public_jwks()['keys'][0];
        $this->assertSame('moodle-key-1', $jwk['kid']);
        $this->assertSame($this->decode($h)['kid'], $jwk['kid']);

        $details = openssl_pkey_get_details(openssl_pkey_get_private(self::$pem));
        $this->assertSame(base64_encode($details['rsa']['n']), base64_encode(base64_decode(strtr($jwk['n'], '-_', '+/'))));
        $public = openssl_pkey_get_public($details['key']);
        $signature = base64_decode(strtr($s, '-_', '+/'));
        $this->assertSame(1, openssl_verify("$h.$p", $signature, $public, OPENSSL_ALGO_SHA256));
    }

    public function test_public_jwks_shape(): void {
        $jwk = $this->launcher()->public_jwks()['keys'][0];
        $this->assertSame(['kty', 'use', 'alg', 'kid', 'n', 'e'], array_keys($jwk));
        $this->assertSame(['RSA', 'sig', 'RS256'], [$jwk['kty'], $jwk['use'], $jwk['alg']]);
        $this->assertSame('AQAB', $jwk['e']);
    }

    public function test_missing_settings_are_reported_and_block_signing(): void {
        foreach (launcher::REQUIRED as $key) {
            $launcher = $this->launcher([$key => '']);
            $this->assertSame([$key => 'missing'], $launcher->problems(), $key);
            $this->assertFalse($launcher->is_configured(), $key);
        }
        $this->expectException(\RuntimeException::class);
        $this->launcher(['kid' => ''])->build_assertion('moodle-user-7');
    }

    public function test_non_string_settings_count_as_missing(): void {
        $launcher = new launcher(['baseurl' => false, 'kid' => null]);
        $this->assertSame('missing', $launcher->problems()['baseurl']);
        $this->assertSame('missing', $launcher->problems()['kid']);
    }

    public function test_invalid_private_key_is_rejected(): void {
        $launcher = $this->launcher(['privatekeypem' => "-----BEGIN PRIVATE KEY-----\n\n-----END PRIVATE KEY-----\n"]);
        $this->expectException(\RuntimeException::class);
        $launcher->build_assertion('moodle-user-7');
    }

    public function test_https_url_validation(): void {
        $valid = ['https://my.xentree.ai', 'https://my.xentree.ai/', 'HTTPS://learn.example.com', 'https://x.example:8443/base'];
        foreach ($valid as $url) {
            $this->assertTrue(launcher::is_valid_https_url($url), $url);
        }
        $invalid = ['', 'http://my.xentree.ai', 'my.xentree.ai', 'https://', 'ftp://my.xentree.ai', 'javascript:alert(1)',
            'https://user:pw@my.xentree.ai', 'https://my.xentree.ai?x=1', 'https://my.xentree.ai#frag', "https://my.xentree.ai\n",
            'https://my xentree.ai'];
        foreach ($invalid as $url) {
            $this->assertFalse(launcher::is_valid_https_url($url), var_export($url, true));
        }
    }

    public function test_http_base_url_is_reported_invalid(): void {
        $this->assertSame(['baseurl' => 'invalid'], $this->launcher(['baseurl' => 'http://my.xentree.ai'])->problems());
    }

    public function test_launch_url(): void {
        $this->assertSame('https://my.xentree.ai/connect/v1/launch/moodle-demo', $this->launcher()->launch_url());
        $this->assertSame(
            'https://learn.example.com/base/connect/v1/launch/moodle-demo',
            $this->launcher(['baseurl' => 'https://learn.example.com/base/'])->launch_url()
        );
    }

    public function test_launch_url_requires_valid_base_url(): void {
        $this->expectException(\RuntimeException::class);
        $this->launcher(['baseurl' => 'http://my.xentree.ai'])->launch_url();
    }

    public function test_subject_for_user(): void {
        $this->assertSame('moodle-user-42', launcher::subject_for_user(42));
    }
}
