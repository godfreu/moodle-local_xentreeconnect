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
 * Tests for the webhook receiver.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_xentreeconnect;

require_once(__DIR__ . '/standalone/memory_store.php');

use local_xentreeconnect\tests\memory_store;
use local_xentreeconnect\webhook\receiver;

/**
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\webhook\receiver
 */
final class receiver_test extends \basic_testcase {
    private const NOW = 1790000000;
    private const SECRET = 'whsec_current';
    private const OLD = 'whsec_previous';

    private memory_store $store;
    /** @var array[] */
    private array $logged = [];

    protected function setUp(): void {
        $this->store = new memory_store([7, 8, 9]);
        $this->logged = [];
    }

    private function receiver(array $secrets = [self::SECRET]): receiver {
        return new receiver($this->store, $secrets, fn() => self::NOW, function (string $o, string $t, string $d): void {
            $this->logged[] = [$o, $t, $d];
        });
    }

    private function session(array $over = []): array {
        return $over + [
            'schema_version' => 1, 'event_id' => 'evt-0001', 'event_type' => 'erpi.session.completed',
            'occurred_at' => '2026-10-14T08:30:00Z', 'external_subject' => 'moodle-user-7',
            'session_duration_seconds' => 1260, 'session_status' => 'completed',
        ];
    }

    private function revoked(array $over = []): array {
        return $over + [
            'schema_version' => 1, 'event_id' => 'evt-rev1', 'event_type' => 'connect.consent.revoked',
            'occurred_at' => '2026-10-14T09:00:00Z', 'external_subject' => 'moodle-user-7', 'reason' => 'user',
        ];
    }

    /** Signs like Xentree's V2 header; $secrets lists every secret to sign with (rotation signs with two). */
    private function request(array $payload, array $over = [], ?string $raw = null): array {
        $body = $raw ?? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $t = $over['t'] ?? self::NOW;
        $signwith = $over['secrets'] ?? [self::SECRET];
        $parts = ['t=' . $t];
        foreach ($signwith as $secret) {
            $parts[] = 'v1=' . hash_hmac('sha256', $t . '.' . $body, $secret);
        }
        $headers = [
            'x-xentree-signature-v2' => $over['signature'] ?? implode(',', $parts),
            'x-xentree-delivery' => $over['delivery'] ?? 'delivery-0001',
            'x-xentree-event' => $over['event'] ?? ($payload['event_type'] ?? ''),
            'content-length' => (string) strlen($body),
        ];
        foreach ($over['drop'] ?? [] as $name) {
            unset($headers[$name]);
        }
        return [$headers, $body];
    }

    private function send(array $payload, array $over = [], ?string $raw = null, ?receiver $receiver = null, string $method = 'POST'): array {
        [$headers, $body] = $this->request($payload, $over, $raw);
        return ($receiver ?? $this->receiver())->handle($method, $headers, fn() => $body);
    }

    private function assertReply(int $status, array $response, ?string $code = null): void {
        $this->assertSame($status, $response['status'], json_encode($response));
        if ($code !== null) {
            $this->assertSame($code, $response['body']['status'] ?? $response['body']['error']);
        }
    }

    // --- accepted events ---------------------------------------------------------------

    public function test_valid_session_is_stored_with_only_the_minimal_fields(): void {
        $response = $this->send($this->session());
        $this->assertReply(200, $response, 'ok');
        $this->assertCount(1, $this->store->events);
        $this->assertSame([
            'deliveryid' => 'delivery-0001', 'eventid' => 'evt-0001', 'eventtype' => 'erpi.session.completed', 'userid' => 7,
            'occurredat' => gmmktime(8, 30, 0, 10, 14, 2026), 'receivedat' => self::NOW, 'sessionseconds' => 1260,
            'sessionstatus' => 'completed',
        ], array_values($this->store->events)[0]);
        $this->assertSame(1, $this->store->counted('accepted'));
    }

    public function test_offsets_and_fractions_in_occurred_at_are_understood(): void {
        $this->send($this->session(['occurred_at' => '2026-10-14T20:01:10.108452+07:00', 'event_id' => 'evt-2']));
        $this->assertSame(gmmktime(13, 1, 10, 10, 14, 2026), array_values($this->store->events)[0]['occurredat']);
    }

    public function test_unknown_extra_fields_are_ignored_and_never_stored(): void {
        $this->send($this->session(['email' => 'who@example.org', 'conversation_id' => 'c1', 'future_field' => ['a' => 1]]));
        $this->assertStringNotContainsString('who@example.org', json_encode($this->store->events));
        $this->assertStringNotContainsString('c1', json_encode($this->store->events));
    }

    public function test_optional_fields_that_do_not_fit_become_null(): void {
        $this->send($this->session(['session_duration_seconds' => 'long', 'session_status' => 'two words!']));
        $row = array_values($this->store->events)[0];
        $this->assertNull($row['sessionseconds']);
        $this->assertNull($row['sessionstatus']);
    }

    // --- idempotency -------------------------------------------------------------------

    public function test_redelivery_with_the_same_delivery_id_is_acknowledged_without_a_second_row(): void {
        $this->send($this->session());
        $again = $this->send($this->session());
        $this->assertReply(200, $again, 'duplicate');
        $this->assertCount(1, $this->store->events);
        $this->assertSame(1, $this->store->counted('duplicate'));
    }

    public function test_a_captured_request_replayed_with_another_delivery_id_is_still_a_duplicate(): void {
        $this->send($this->session());
        $this->assertReply(200, $this->send($this->session(), ['delivery' => 'delivery-0002']), 'duplicate');
        $this->assertCount(1, $this->store->events);
    }

    // --- authentication ----------------------------------------------------------------

    public function test_bad_signature_is_rejected_and_nothing_is_processed(): void {
        $this->assertReply(401, $this->send($this->session(), ['secrets' => ['whsec_wrong']]), 'bad_signature');
        $this->assertSame([], $this->store->events);
        $this->assertSame(1, $this->store->counted('bad_signature'));
    }

    public function test_tampered_body_is_rejected(): void {
        [$headers, $body] = $this->request($this->session());
        $response = $this->receiver()->handle('POST', $headers, fn() => str_replace('1260', '9999', $body));
        $this->assertReply(401, $response, 'bad_signature');
        $this->assertSame([], $this->store->events);
    }

    public function test_missing_or_malformed_signature_headers_are_rejected(): void {
        $this->assertReply(401, $this->send($this->session(), ['drop' => ['x-xentree-signature-v2']]), 'bad_signature');
        foreach (['', 'garbage', 't=abc,v1=00', 'v1=00', 't=1790000000', 't=1790000000,v1', 't=-5,v1=aa'] as $header) {
            $this->assertReply(401, $this->send($this->session(), ['signature' => $header]));
        }
        $this->assertSame([], $this->store->events);
    }

    public function test_stale_and_future_timestamps_are_rejected(): void {
        $this->assertReply(401, $this->send($this->session(), ['t' => self::NOW - 301]), 'stale_timestamp');
        $this->assertReply(401, $this->send($this->session(), ['t' => self::NOW + 301]), 'stale_timestamp');
        $this->assertReply(200, $this->send($this->session(), ['t' => self::NOW - 299]));
        $this->assertSame(1, $this->store->counted('stale_timestamp') - 1);
    }

    public function test_uppercase_hex_signatures_are_accepted(): void {
        [$headers, $body] = $this->request($this->session());
        $headers['x-xentree-signature-v2'] = preg_replace_callback('/v1=([0-9a-f]+)/', fn($m) => 'v1=' . strtoupper($m[1]), $headers['x-xentree-signature-v2']);
        $this->assertReply(200, $this->receiver()->handle('POST', $headers, fn() => $body));
    }

    public function test_rotation_accepts_either_secret_and_rejects_neither(): void {
        $both = ['secrets' => [self::SECRET, self::OLD]]; // what Xentree sends during the 24 hour grace period
        $this->assertReply(200, $this->send($this->session(), $both, null, $this->receiver([self::SECRET, self::OLD])));
        $this->assertReply(200, $this->send($this->session(['event_id' => 'e2']), $both + ['delivery' => 'delivery-0002'], null, $this->receiver([self::OLD])));
        $this->assertReply(200, $this->send($this->session(['event_id' => 'e3']), $both + ['delivery' => 'delivery-0003'], null, $this->receiver([self::SECRET])));
        $this->assertReply(401, $this->send($this->session(['event_id' => 'e4']), $both + ['delivery' => 'delivery-0004'], null, $this->receiver(['whsec_other'])));
        $this->assertCount(3, $this->store->events);
    }

    public function test_no_secret_configured_answers_503_so_xentree_retries(): void {
        $this->assertReply(503, $this->send($this->session(), [], null, $this->receiver([])), 'not_configured');
        $this->assertReply(503, $this->send($this->session(), [], null, $this->receiver(['', ''])));
        $this->assertSame([], $this->store->events);
    }

    // --- request shape ------------------------------------------------------------------

    public function test_only_post_is_allowed(): void {
        foreach (['GET', 'PUT', 'DELETE', 'HEAD'] as $method) {
            $response = $this->send($this->session(), [], null, null, $method);
            $this->assertReply(405, $response, 'method_not_allowed');
            $this->assertSame('POST', $response['headers']['Allow']);
        }
        $this->assertSame([], $this->store->stats, 'bad methods are not even counted');
    }

    public function test_oversized_declared_body_is_refused_before_it_is_read(): void {
        $read = false;
        $headers = ['content-length' => (string) (receiver::MAX_BODY + 1), 'x-xentree-signature-v2' => 't=1,v1=00'];
        $response = $this->receiver()->handle('POST', $headers, function () use (&$read) {
            $read = true;
            return '';
        });
        $this->assertReply(413, $response, 'too_large');
        $this->assertFalse($read);
    }

    public function test_oversized_actual_body_is_refused(): void {
        $stream = fopen('php://memory', 'w+b');
        fwrite($stream, str_repeat('a', receiver::MAX_BODY + 10));
        rewind($stream);
        $this->assertNull(receiver::read_limited($stream));
        $response = $this->receiver()->handle('POST', [], fn() => null);
        $this->assertReply(413, $response, 'too_large');

        $ok = fopen('php://memory', 'w+b');
        fwrite($ok, str_repeat('a', receiver::MAX_BODY));
        rewind($ok);
        $this->assertSame(receiver::MAX_BODY, strlen(receiver::read_limited($ok)));
    }

    public function test_invalid_json_after_a_valid_signature_is_a_400(): void {
        $this->assertReply(400, $this->send([], [], '{not json'), 'bad_payload');
        $this->assertReply(400, $this->send([], [], '[1,2,3]'), 'bad_payload');
        $this->assertReply(400, $this->send([], [], '"text"'), 'bad_payload');
    }

    public function test_required_fields_are_checked_strictly(): void {
        $bad = [
            ['event_id' => ''], ['event_id' => 'has space'], ['event_id' => str_repeat('a', 65)], ['event_id' => 5],
            ['occurred_at' => 'yesterday'], ['occurred_at' => '2026-10-14'], ['occurred_at' => null],
            ['external_subject' => 7], ['schema_version' => 'one'], ['schema_version' => 0],
        ];
        foreach ($bad as $i => $change) {
            $payload = $change + $this->session();
            $this->assertReply(400, $this->send($payload, ['delivery' => 'delivery-bad' . $i]), 'bad_payload');
        }
        $this->assertSame([], $this->store->events);
    }

    public function test_missing_or_malformed_delivery_header_is_a_400(): void {
        $this->assertReply(400, $this->send($this->session(), ['drop' => ['x-xentree-delivery']]), 'bad_payload');
        $this->assertReply(400, $this->send($this->session(), ['delivery' => 'short']), 'bad_payload');
        $this->assertReply(400, $this->send($this->session(), ['delivery' => "has space and\nnewline"]), 'bad_payload');
    }

    public function test_event_header_must_agree_with_the_payload(): void {
        $this->assertReply(400, $this->send($this->session(), ['event' => 'connect.consent.revoked']), 'bad_payload');
    }

    // --- identity ------------------------------------------------------------------------

    public function test_unknown_or_deleted_user_is_acknowledged_and_counted_not_retried(): void {
        $this->assertReply(200, $this->send($this->session(['external_subject' => 'moodle-user-404'])), 'ok');
        $this->assertSame(1, $this->store->counted('unknown_subject'));
        $this->assertSame([], $this->store->events);
    }

    /** @dataProvider bad_subjects */
    public function test_malformed_subjects_never_map_to_a_user(string $subject): void {
        $this->assertNull(receiver::user_from_subject($subject));
        $this->assertReply(200, $this->send($this->session(['external_subject' => $subject])), 'ok');
        $this->assertSame([], $this->store->events);
    }

    public static function bad_subjects(): array {
        return [
            ['moodle-user-0'], ['moodle-user-007'], ['moodle-user-'], ['moodle-user-7 '], [' moodle-user-7'], ['moodle-user--7'],
            ['moodle-user-7a'], ['Moodle-User-7'], ['other-7'], ['7'], [''], ['moodle-user-99999999999'], ['moodle-user-2147483648'],
            ["moodle-user-7\n"],
        ];
    }

    public function test_valid_subject_mapping(): void {
        $this->assertSame(7, receiver::user_from_subject('moodle-user-7'));
        $this->assertSame(2147483647, receiver::user_from_subject('moodle-user-2147483647'));
    }

    // --- other events ----------------------------------------------------------------------

    public function test_consent_revoked_deletes_that_users_rows_only_and_is_idempotent(): void {
        $this->send($this->session(), ['delivery' => 'delivery-0001']);
        $this->send($this->session(['event_id' => 'evt-b']), ['delivery' => 'delivery-0002']);
        $this->send($this->session(['event_id' => 'evt-c', 'external_subject' => 'moodle-user-8']), ['delivery' => 'delivery-0003']);
        $this->assertCount(3, $this->store->events);

        $this->assertReply(200, $this->send($this->revoked(), ['delivery' => 'delivery-0004']), 'ok');
        $this->assertCount(1, $this->store->events);
        $this->assertSame(8, array_values($this->store->events)[0]['userid']);
        $this->assertReply(200, $this->send($this->revoked(), ['delivery' => 'delivery-0004']));
        $this->assertCount(1, $this->store->events);
        $this->assertSame(2, $this->store->counted('revoked'));
    }

    public function test_consent_revoked_stores_nothing_and_checks_its_fields(): void {
        $this->send($this->revoked());
        $this->assertSame([], $this->store->events);
        $this->assertReply(400, $this->send($this->revoked(['reason' => 'because']), ['delivery' => 'delivery-0009']), 'bad_payload');
        $this->assertReply(200, $this->send($this->revoked(['external_subject' => 'nonsense']), ['delivery' => 'delivery-0010']));
        $this->assertSame(1, $this->store->counted('unknown_subject'));
    }

    public function test_revoked_notice_works_for_a_user_who_no_longer_exists(): void {
        $this->store->users = [];
        $this->store->events[1] = ['deliveryid' => 'old', 'eventid' => 'o', 'eventtype' => 'x', 'userid' => 7, 'occurredat' => 1, 'receivedat' => 1, 'sessionseconds' => null, 'sessionstatus' => null];
        $this->assertReply(200, $this->send($this->revoked()));
        $this->assertSame([], $this->store->events);
    }

    public function test_test_ping_and_unknown_events_are_acknowledged_and_ignored(): void {
        $ping = ['schema_version' => 1, 'event_id' => 'evt-p', 'event_type' => 'webhook.test.ping', 'occurred_at' => '2026-10-14T08:30:00Z', 'message' => 'Test event from Xentree'];
        $this->assertReply(200, $this->send($ping), 'ok');
        $this->assertSame(1, $this->store->counted('ping'));

        $future = ['schema_version' => 1, 'event_id' => 'evt-f', 'event_type' => 'something.new'] ;
        $this->assertReply(200, $this->send($future, ['delivery' => 'delivery-0002']), 'ok');
        $this->assertSame(1, $this->store->counted('unknown_event'));
        $this->assertSame([], $this->store->events);
    }

    public function test_a_newer_schema_version_is_acknowledged_not_parsed(): void {
        $this->assertReply(200, $this->send($this->session(['schema_version' => 2])), 'ok');
        $this->assertSame(1, $this->store->counted('unsupported_schema'));
        $this->assertSame([], $this->store->events);
    }

    // --- what leaves the endpoint --------------------------------------------------------------

    public function test_replies_carry_short_codes_only(): void {
        $payload = $this->session(['event_type' => 'x@example.org', 'external_subject' => 'moodle-user-7']);
        foreach ([$this->send($payload, ['event' => 'x@example.org']), $this->send($this->session(), ['secrets' => ['nope']])] as $response) {
            $json = json_encode($response['body']);
            $this->assertMatchesRegularExpression('/^\{"(status|error)":"[a-z_]+"\}$/', $json);
        }
    }

    public function test_log_lines_hold_event_type_and_ids_but_never_content(): void {
        $this->send($this->session(['event_type' => 'who@example.org/../etc', 'external_subject' => 'moodle-user-7']), ['event' => '']);
        $this->send($this->session(), ['secrets' => ['nope'], 'delivery' => 'delivery-0777']);
        $dump = json_encode($this->logged);
        $this->assertStringNotContainsString('@', $dump);
        $this->assertStringNotContainsString('/', $dump);
        $this->assertStringContainsString('delivery-0777', $dump);
        $this->assertStringNotContainsString('moodle-user', $dump);
    }

    public function test_headers_are_read_from_server_variables(): void {
        $headers = receiver::headers_from_server([
            'HTTP_X_XENTREE_SIGNATURE_V2' => 't=1,v1=ab', 'HTTP_X_XENTREE_DELIVERY' => 'abc', 'CONTENT_LENGTH' => '12',
            'REQUEST_METHOD' => 'POST', 'argv' => ['x'],
        ]);
        $this->assertSame(['x-xentree-signature-v2' => 't=1,v1=ab', 'x-xentree-delivery' => 'abc', 'content-length' => '12'], $headers);
    }
}
