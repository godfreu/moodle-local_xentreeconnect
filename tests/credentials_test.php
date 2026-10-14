<?php

namespace local_xentreeconnect;

/**
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\credentials
 */
final class credentials_test extends \basic_testcase {
    private function reader(array $files): callable {
        return static fn(string $path): ?string => $files[$path] ?? null;
    }

    public function test_config_php_wins_over_file_and_database(): void {
        $c = new credentials(
            ['webhooksecret' => 'from-config'],
            ['webhooksecret' => 'from-db', 'webhooksecretfile' => '/etc/x/secret'],
            $this->reader(['/etc/x/secret' => 'from-file'])
        );
        $this->assertSame(['value' => 'from-config', 'source' => 'config.php', 'problem' => null], $c->resolve('webhooksecret'));
    }

    public function test_file_wins_over_database_and_is_trimmed(): void {
        $c = new credentials([], ['webhooksecret' => 'from-db', 'webhooksecretfile' => '/etc/x/secret'],
            $this->reader(['/etc/x/secret' => "from-file\n"]));
        $this->assertSame(['value' => 'from-file', 'source' => 'file', 'problem' => null], $c->resolve('webhooksecret'));
    }

    public function test_database_is_the_fallback(): void {
        $c = new credentials([], ['webhooksecret' => ' from-db ']);
        $this->assertSame(['value' => 'from-db', 'source' => 'database', 'problem' => null], $c->resolve('webhooksecret'));
    }

    public function test_nothing_configured(): void {
        $c = new credentials([], []);
        $this->assertSame(['value' => '', 'source' => 'none', 'problem' => null], $c->resolve('privatekeypem'));
    }

    public function test_the_file_path_can_itself_be_forced_in_config_php(): void {
        $c = new credentials(['privatekeyfile' => '/etc/forced.pem'], ['privatekeyfile' => '/etc/stored.pem'],
            $this->reader(['/etc/forced.pem' => 'FORCED-PEM', '/etc/stored.pem' => 'STORED-PEM']));
        $this->assertSame('FORCED-PEM', $c->value('privatekeypem'));
    }

    public function test_unreadable_or_empty_file_falls_back_to_the_database_and_reports_why(): void {
        $stored = ['webhooksecret' => 'from-db', 'webhooksecretfile' => '/etc/x/secret'];
        $missing = (new credentials([], $stored, $this->reader([])))->resolve('webhooksecret');
        $this->assertSame(['from-db', 'database', 'unreadable'], [$missing['value'], $missing['source'], $missing['problem']]);
        $empty = (new credentials([], $stored, $this->reader(['/etc/x/secret' => " \n"])))->resolve('webhooksecret');
        $this->assertSame(['from-db', 'database', 'empty'], [$empty['value'], $empty['source'], $empty['problem']]);
    }

    public function test_a_file_inside_the_web_root_is_refused(): void {
        $c = new credentials([], ['webhooksecretfile' => '/var/www/moodle/public/secret.txt'],
            $this->reader(['/var/www/moodle/public/secret.txt' => 'leaky']), '/var/www/moodle/public');
        $result = $c->resolve('webhooksecret');
        $this->assertSame(['', 'none', 'insidewebroot'], [$result['value'], $result['source'], $result['problem']]);
        // A sibling directory with the same prefix is not inside.
        $outside = new credentials([], ['webhooksecretfile' => '/var/www/moodle/public-secrets/s'],
            $this->reader(['/var/www/moodle/public-secrets/s' => 'ok']), '/var/www/moodle/public');
        $this->assertSame('file', $outside->resolve('webhooksecret')['source']);
    }

    public function test_items_are_independent_and_unknown_items_are_rejected(): void {
        $c = new credentials(['webhooksecret' => 'a'], ['webhooksecretprev' => 'b']);
        $this->assertSame('a', $c->value('webhooksecret'));
        $this->assertSame('b', $c->value('webhooksecretprev'));
        $this->assertSame('', $c->value('privatekeypem'));
        $this->expectException(\InvalidArgumentException::class);
        $c->resolve('baseurl');
    }
}
