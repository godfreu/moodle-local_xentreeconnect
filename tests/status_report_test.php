<?php

namespace local_xentreeconnect;

use local_xentreeconnect\tests\memory_store;

/**
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\status_report
 */
final class status_report_test extends \basic_testcase {
    private const NOW = 1790000000;
    private const URL = 'https://moodle.example.org/local/xentreeconnect/webhook.php';

    private function build(credentials $credentials, ?memory_store $store = null, int $days = 365): array {
        return status_report::build($credentials, $store ?? new memory_store(), self::URL, $days, self::NOW);
    }

    public function test_healthy_site_has_no_warnings_and_names_the_sources(): void {
        $report = $this->build(new credentials(['privatekeypem' => 'PEM', 'webhooksecret' => 'whsec_x'], []));
        $this->assertSame([], $report['warnings']);
        $this->assertSame(['configured' => true, 'source' => 'config.php', 'problem' => null], $report['key']);
        $this->assertSame(self::URL, $report['receiverurl']);
        $this->assertFalse($report['previoussecret']['configured']);
    }

    public function test_database_storage_and_missing_secret_are_warned_about(): void {
        $report = $this->build(new credentials([], ['privatekeypem' => 'PEM', 'webhooksecret' => 'whsec_x']));
        $this->assertSame(['keyindatabase', 'secretindatabase'], $report['warnings']);
        $missing = $this->build(new credentials(['privatekeypem' => 'PEM'], []));
        $this->assertSame(['secretmissing'], $missing['warnings']);
    }

    public function test_retention_zero_is_warned_about(): void {
        $report = $this->build(new credentials(['privatekeypem' => 'PEM', 'webhooksecret' => 's'], []), null, 0);
        $this->assertSame(['retentionzero'], $report['warnings']);
        $this->assertSame(0, $report['retentiondays']);
    }

    public function test_a_configured_file_that_failed_is_reported_with_its_reason(): void {
        $credentials = new credentials([], ['webhooksecret' => 's', 'webhooksecretfile' => '/nope'], fn() => null);
        $report = $this->build($credentials);
        $this->assertContains('filefailed:secret:unreadable', $report['warnings']);
        $this->assertContains('secretindatabase', $report['warnings']);
    }

    public function test_counts_cover_the_last_24_hours_only(): void {
        $store = new memory_store();
        $store->count_outcome('bad_signature', self::NOW - 100);
        $store->count_outcome('bad_signature', self::NOW - 200);
        $store->count_outcome('stale_timestamp', self::NOW - 300);
        $store->count_outcome('accepted', self::NOW - 400);
        $store->count_outcome('bad_signature', self::NOW - 3 * DAYSECS);
        $store->insert_event(['deliveryid' => 'a', 'eventid' => 'a', 'eventtype' => 't', 'userid' => 7, 'occurredat' => 1, 'receivedat' => self::NOW - 50, 'sessionseconds' => 1, 'sessionstatus' => 'x']);
        $store->insert_event(['deliveryid' => 'b', 'eventid' => 'b', 'eventtype' => 't', 'userid' => 7, 'occurredat' => 1, 'receivedat' => self::NOW - 2 * DAYSECS, 'sessionseconds' => 1, 'sessionstatus' => 'x']);

        $report = $this->build(new credentials(['privatekeypem' => 'PEM', 'webhooksecret' => 's'], []), $store);
        $this->assertSame(3, $report['rejected24h']);
        $this->assertSame(1, $report['events24h']);
        $this->assertSame(['accepted' => 1, 'bad_signature' => 2, 'stale_timestamp' => 1], $report['outcomes']);
    }

    public function test_the_report_never_contains_a_key_or_secret_value(): void {
        $credentials = new credentials(
            ['privatekeypem' => '-----BEGIN PRIVATE KEY-----SUPERSECRETPEM', 'webhooksecret' => 'whsec_SUPERSECRETVALUE'],
            ['webhooksecretprev' => 'whsec_OLDSUPERSECRET']
        );
        $json = json_encode($this->build($credentials));
        foreach (['SUPERSECRETPEM', 'SUPERSECRETVALUE', 'OLDSUPERSECRET', 'BEGIN PRIVATE'] as $needle) {
            $this->assertStringNotContainsString($needle, $json);
        }
    }

    public function test_every_string_the_page_derives_from_a_report_exists_in_both_languages(): void {
        foreach (['en', 'id'] as $lang) {
            $string = [];
            require dirname(__DIR__) . "/lang/$lang/local_xentreeconnect.php";
            foreach ([credentials::FROM_CONFIG, credentials::FROM_FILE, credentials::FROM_DATABASE, credentials::NONE] as $source) {
                $this->assertArrayHasKey('source_' . preg_replace('/[^a-z]/', '', $source), $string, "$lang source $source");
            }
            foreach (array_keys(credentials::ITEMS) as $item) {
                $this->assertArrayHasKey('credential_' . $item, $string, "$lang $item");
            }
            foreach (['retentionzero', 'keyindatabase', 'secretindatabase', 'secretmissing', 'filefailed'] as $warning) {
                $this->assertArrayHasKey('warning_' . $warning, $string, "$lang $warning");
            }
            foreach (['insidewebroot', 'unreadable', 'empty'] as $reason) {
                $this->assertArrayHasKey('filereason_' . $reason, $string, "$lang $reason");
            }
        }
    }
}
