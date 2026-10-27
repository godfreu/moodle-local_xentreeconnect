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
 * Verifies deliveries produced by Xentree signing fixtures.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 G. E. Bayu Alfian <79591609+godfreu@users.noreply.github.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_xentreeconnect;

require_once(__DIR__ . '/standalone/memory_store.php');

use local_xentreeconnect\tests\memory_store;
use local_xentreeconnect\webhook\receiver;

/**
 * Verifies bytes and headers produced by Xentree's real signing code (tests/fixtures/backend_deliveries.json, made by
 * tests/fixtures/generate_backend_fixtures.py). If Xentree changes how it signs or shapes a delivery, this fails.
 *
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\webhook\receiver
 */
final class backend_compat_test extends \basic_testcase {
    private static array $fixtures;

    public static function setUpBeforeClass(): void {
        $data = json_decode(file_get_contents(__DIR__ . '/fixtures/backend_deliveries.json'), true, 512, JSON_THROW_ON_ERROR);
        self::$fixtures = [];
        foreach ($data['deliveries'] as $delivery) {
            self::$fixtures[$delivery['name']] = $delivery;
        }
    }

    private function deliver(string $name, array $secrets, ?int $now = null, ?memory_store $store = null): array {
        $fixture = self::$fixtures[$name];
        $store ??= new memory_store([7, 8]);
        $headers = array_change_key_case($fixture['headers'], CASE_LOWER);
        $receiver = new receiver($store, $secrets, fn() => $now ?? $fixture['timestamp']);
        $response = $receiver->handle('POST', $headers, fn() => $fixture['body']);
        return [$response, $store, $fixture];
    }

    public function test_a_session_completed_delivery_signed_by_xentree_is_accepted_and_stored(): void {
        [$response, $store, $fixture] = $this->deliver('session_completed', [self::$fixtures['session_completed']['secret_current']]);
        $this->assertSame(200, $response['status']);
        $this->assertCount(1, $store->events);
        $row = array_values($store->events)[0];
        $this->assertSame($fixture['expect']['userid'], $row['userid']);
        $this->assertSame($fixture['expect']['seconds'], $row['sessionseconds']);
        $this->assertSame($fixture['expect']['status'], $row['sessionstatus']);
        $this->assertSame($fixture['expect']['eventid'], $row['eventid']);
        $this->assertSame($fixture['headers']['X-Xentree-Delivery'], $row['deliveryid']);
        $this->assertSame(gmmktime(8, 30, 0, 10, 14, 2026), $row['occurredat']);
        $this->assertStringNotContainsString('must-not-leak', json_encode($store->events));
    }

    public function test_the_payload_has_exactly_the_documented_fields(): void {
        $payload = json_decode(self::$fixtures['session_completed']['body'], true);
        $this->assertSame(
            ['event_id', 'event_type', 'external_subject', 'occurred_at', 'schema_version', 'session_duration_seconds', 'session_status'],
            array_keys($payload)
        );
        $this->assertSame(1, $payload['schema_version']);
    }

    public function test_wrong_secret_or_a_later_clock_is_rejected(): void {
        [$response, $store] = $this->deliver('session_completed', ['whsec_not_the_one']);
        $this->assertSame(401, $response['status']);
        $this->assertSame([], $store->events);

        $secret = self::$fixtures['session_completed']['secret_current'];
        [$late] = $this->deliver('session_completed', [$secret], self::$fixtures['session_completed']['timestamp'] + 301);
        $this->assertSame(401, $late['status']);
    }

    public function test_rotation_window_header_verifies_with_either_secret(): void {
        $fixture = self::$fixtures['session_completed_during_rotation'];
        $this->assertStringContainsString(',v1=', substr($fixture['headers']['X-Xentree-Signature-V2'], 12), 'two v1 values');
        $this->assertSame(2, substr_count($fixture['headers']['X-Xentree-Signature-V2'], 'v1='));
        foreach ([[$fixture['secret_current']], [$fixture['secret_previous']], [$fixture['secret_current'], $fixture['secret_previous']]] as $secrets) {
            [$response, $store] = $this->deliver('session_completed_during_rotation', $secrets);
            $this->assertSame(200, $response['status']);
            $this->assertCount(1, $store->events);
            $this->assertSame($fixture['expect']['userid'], array_values($store->events)[0]['userid']);
            // 09:00:00.123456 at +07:00 is 02:00:00 UTC
            $this->assertSame(gmmktime(2, 0, 0, 10, 14, 2026), array_values($store->events)[0]['occurredat']);
        }
        [$response] = $this->deliver('session_completed_during_rotation', ['whsec_unrelated']);
        $this->assertSame(401, $response['status']);
    }

    public function test_a_delivery_signed_with_only_the_new_secret_is_rejected_by_a_site_that_has_only_the_old_one(): void {
        [$response] = $this->deliver('session_completed', [self::$fixtures['session_completed_during_rotation']['secret_previous']]);
        $this->assertSame(401, $response['status']);
    }

    public function test_the_consent_revoked_notice_deletes_the_members_rows(): void {
        $store = new memory_store([7, 8]);
        $store->insert_event(['deliveryid' => 'old-1', 'eventid' => 'old-e1', 'eventtype' => 'erpi.session.completed', 'userid' => 7,
            'occurredat' => 1, 'receivedat' => 1, 'sessionseconds' => 1, 'sessionstatus' => 'completed']);
        $store->insert_event(['deliveryid' => 'old-2', 'eventid' => 'old-e2', 'eventtype' => 'erpi.session.completed', 'userid' => 8,
            'occurredat' => 1, 'receivedat' => 1, 'sessionseconds' => 1, 'sessionstatus' => 'completed']);
        [$response, , $fixture] = $this->deliver('consent_revoked', [self::$fixtures['consent_revoked']['secret_current']], null, $store);
        $this->assertSame(200, $response['status']);
        $this->assertSame('admin', json_decode($fixture['body'], true)['reason']);
        $this->assertSame([8], array_values(array_map(fn($r) => $r['userid'], $store->events)));
    }

    public function test_the_test_ping_is_acknowledged(): void {
        [$response, $store] = $this->deliver('test_ping', [self::$fixtures['test_ping']['secret_current']]);
        $this->assertSame(200, $response['status']);
        $this->assertSame(1, $store->counted('ping'));
        $this->assertSame([], $store->events);
    }

    public function test_all_deliveries_of_the_fixture_set_pass_with_the_current_secret(): void {
        foreach (self::$fixtures as $name => $fixture) {
            [$response] = $this->deliver($name, [$fixture['secret_current']]);
            $this->assertSame(200, $response['status'], $name);
        }
    }
}
