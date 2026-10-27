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
 * Validates the plugin configuration and builds the signed Xentree Connect launch assertion.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Pure PHP (no Moodle API) so the JWT and configuration logic can be unit tested in isolation.

namespace local_xentreeconnect;

/**
 * Validates the plugin configuration and builds the signed Xentree Connect launch assertion.
 *
 * @package    local_xentreeconnect
 */
final class launcher {
    /** Settings that must be filled before a launch can be signed. */
    public const REQUIRED = ['baseurl', 'clientid', 'issuer', 'audience', 'organizationid', 'kid', 'privatekeypem'];

    /** Lifetime of a launch assertion in seconds. */
    public const ASSERTION_TTL = 300;

    /** @var string[] Normalised configuration, keyed by setting name. */
    private array $config;

    /** @var callable */
    private $clock;

    /**
     * @param array $config Raw plugin settings (values that are not strings are treated as empty).
     * @param callable|null $clock Returns the current unix time; injectable for tests.
     */
    public function __construct(array $config, ?callable $clock = null) {
        $this->config = [];
        foreach (self::REQUIRED as $key) {
            $value = $config[$key] ?? '';
            $this->config[$key] = is_string($value) ? trim($value) : '';
        }
        $this->clock = $clock ?? 'time';
    }

    /**
     * Builds a launcher from the saved plugin settings.
     */
    public static function from_site_config(): self {
        $config = [];
        foreach (self::REQUIRED as $key) {
            $config[$key] = get_config('local_xentreeconnect', $key);
        }
        // The key may come from config.php or a file outside the web root instead of the database.
        $config['privatekeypem'] = credentials::from_site_config()->value('privatekeypem');
        return new self($config);
    }

    /**
     * Whether a URL is an absolute https URL without credentials, query or fragment.
     *
     * Strict: surrounding whitespace makes it invalid, so callers trim user input first.
     */
    public static function is_valid_https_url(string $url): bool {
        if ($url === '' || preg_match('/[\s\x00-\x1f]/', $url)) {
            return false;
        }
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            return false;
        }
        if (strtolower($parts['scheme']) !== 'https') {
            return false;
        }
        return !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['query']) && !isset($parts['fragment']);
    }

    /**
     * Stable external subject for a Moodle user.
     */
    public static function subject_for_user(int $userid): string {
        return 'moodle-user-' . $userid;
    }

    /**
     * Shallow configuration check (no key parsing) that is cheap enough to run on every page.
     *
     * @return string[] Setting name => 'missing' or 'invalid'. Empty when ready.
     */
    public function problems(): array {
        $problems = [];
        foreach (self::REQUIRED as $key) {
            if ($this->config[$key] === '') {
                $problems[$key] = 'missing';
            }
        }
        if (!isset($problems['baseurl']) && !self::is_valid_https_url($this->config['baseurl'])) {
            $problems['baseurl'] = 'invalid';
        }
        return $problems;
    }

    public function is_configured(): bool {
        return $this->problems() === [];
    }

    /**
     * The Xentree launch endpoint this assertion must be posted to.
     *
     * @throws \RuntimeException when the base URL or client id is unusable.
     */
    public function launch_url(): string {
        $problems = $this->problems();
        if (isset($problems['baseurl']) || isset($problems['clientid'])) {
            throw new \RuntimeException('Xentree base URL and client ID must be configured.');
        }
        return rtrim($this->config['baseurl'], '/') . '/connect/v1/launch/' . rawurlencode($this->config['clientid']);
    }

    /**
     * Builds and signs a launch assertion (RS256) for one Moodle user.
     *
     * @param string $subject External subject, see {@see self::subject_for_user()}.
     * @param string|null $email Real e-mail address of the user; omitted from the claims when empty or malformed.
     * @throws \RuntimeException when the configuration is incomplete or the key cannot sign.
     */
    public function build_assertion(string $subject, ?string $email = null): string {
        $problems = $this->problems();
        if ($problems !== []) {
            throw new \RuntimeException('Xentree Connect is not configured: ' . implode(', ', array_keys($problems)));
        }

        $key = $this->private_key();
        $now = (int) ($this->clock)();
        $payload = [
            'iss' => $this->config['issuer'],
            'aud' => $this->config['audience'],
            'sub' => $subject,
            'organization_id' => $this->config['organizationid'],
            'iat' => $now,
            'exp' => $now + self::ASSERTION_TTL,
            'jti' => 'moodle-' . bin2hex(random_bytes(16)),
        ];
        $email = trim((string) $email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $payload['email'] = $email;
        }
        // Xentree selects the verification key from the registered JWKS by this kid.
        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => $this->config['kid']];

        $signinginput = self::base64url(json_encode($header, JSON_UNESCAPED_SLASHES)) . '.' .
            self::base64url(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = '';
        if (!openssl_sign($signinginput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Unable to sign the launch assertion.');
        }
        return $signinginput . '.' . self::base64url($signature);
    }

    /**
     * Public JWKS matching the configured private key, ready to register in Xentree.
     *
     * @throws \RuntimeException when the key or kid is missing or not an RSA key.
     */
    public function public_jwks(): array {
        if ($this->config['kid'] === '') {
            throw new \RuntimeException('Key ID is not configured.');
        }
        $details = openssl_pkey_get_details($this->private_key());
        if ($details === false || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new \RuntimeException('Only RSA keys are supported.');
        }
        return ['keys' => [[
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $this->config['kid'],
            'n' => self::base64url($details['rsa']['n']),
            'e' => self::base64url($details['rsa']['e']),
        ]]];
    }

    /**
     * @return \OpenSSLAsymmetricKey
     * @throws \RuntimeException
     */
    private function private_key() {
        if ($this->config['privatekeypem'] === '') {
            throw new \RuntimeException('Private key is not configured.');
        }
        $key = openssl_pkey_get_private($this->config['privatekeypem']);
        if ($key === false) {
            throw new \RuntimeException('Private key is invalid or unreadable.');
        }
        return $key;
    }

    private static function base64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
