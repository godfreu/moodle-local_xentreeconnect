<?php

namespace local_xentreeconnect;

// Needs the stand-ins from tests/standalone/moodle_stubs.php; inside Moodle the real API is tested by tests/privacy/provider_test.php.
if (!class_exists('fake_moodle_db')) {
    return;
}

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_xentreeconnect\privacy\provider;

/**
 * Logic of the privacy provider against stand-ins for Moodle's Privacy API ({@see tests/standalone/moodle_stubs.php}).
 * Not a validation of the real API: that is tests/privacy/provider_test.php, which only runs inside Moodle.
 *
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\privacy\provider
 */
final class privacy_logic_test extends \basic_testcase {
    protected function setUp(): void {
        global $DB;
        $DB = new \fake_moodle_db();
        $DB->rows = [
            1 => (object) ['id' => 1, 'userid' => 7, 'eventtype' => 'erpi.session.completed', 'occurredat' => 1790000300,
                'receivedat' => 1790000400, 'sessionseconds' => 600, 'sessionstatus' => 'completed', 'deliveryid' => 'd1', 'eventid' => 'e1'],
            2 => (object) ['id' => 2, 'userid' => 7, 'eventtype' => 'erpi.session.completed', 'occurredat' => 1790000100,
                'receivedat' => 1790000200, 'sessionseconds' => null, 'sessionstatus' => null, 'deliveryid' => 'd2', 'eventid' => 'e2'],
            3 => (object) ['id' => 3, 'userid' => 8, 'eventtype' => 'erpi.session.completed', 'occurredat' => 1, 'receivedat' => 2,
                'sessionseconds' => 5, 'sessionstatus' => 'completed', 'deliveryid' => 'd3', 'eventid' => 'e3'],
        ];
        writer::$exports = [];
    }

    private function lang(string $lang): array {
        $string = [];
        require dirname(__DIR__) . "/lang/$lang/local_xentreeconnect.php";
        return $string;
    }

    public function test_metadata_declares_the_table_and_the_external_flow_with_translated_descriptions(): void {
        $collection = provider::get_metadata(new collection());
        $this->assertSame(['local_xentreeconnect_event'], array_keys($collection->tables));
        $this->assertSame(['xentree'], array_keys($collection->links));
        $this->assertSame(['userid', 'eventtype', 'occurredat', 'receivedat', 'sessionseconds', 'sessionstatus'],
            array_keys($collection->tables['local_xentreeconnect_event']['fields']));
        // The table's columns that identify a delivery are deliberately not part of what is declared/exported.
        $keys = [$collection->tables['local_xentreeconnect_event']['summary'], $collection->links['xentree']['summary']];
        foreach ([$collection->tables['local_xentreeconnect_event']['fields'], $collection->links['xentree']['fields']] as $fields) {
            $keys = array_merge($keys, array_values($fields));
        }
        foreach (['en', 'id'] as $lang) {
            $strings = $this->lang($lang);
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $strings, "$lang is missing $key");
            }
        }
    }

    public function test_the_old_claim_that_nothing_is_stored_is_gone(): void {
        $source = file_get_contents(dirname(__DIR__) . '/classes/privacy/provider.php');
        $this->assertStringNotContainsString('stores no personal data', $source);
        $this->assertStringNotContainsString('Nothing is stored in Moodle', $source);
        foreach (['en', 'id'] as $lang) {
            $this->assertStringNotContainsString('stores no personal data', $this->lang($lang)['privacy:metadata:xentree']);
        }
    }

    public function test_contexts_for_a_user_look_at_the_system_context_and_that_user_only(): void {
        $list = provider::get_contexts_for_userid(7);
        [$sql, $params] = $list->queries[0];
        $this->assertStringContainsString('local_xentreeconnect_event', $sql);
        $this->assertSame(['contextlevel' => CONTEXT_SYSTEM, 'userid' => 7], $params);
    }

    public function test_users_in_context_only_for_the_system_context(): void {
        $system = new userlist(\context_system::instance());
        provider::get_users_in_context($system);
        $this->assertCount(1, $system->queries);
        $this->assertSame('userid', $system->queries[0][0]);

        $other = new userlist(new \context_course());
        provider::get_users_in_context($other);
        $this->assertSame([], $other->queries);
    }

    public function test_export_writes_only_the_users_rows_oldest_first_without_internal_ids(): void {
        provider::export_user_data(new approved_contextlist((object) ['id' => 7], 'local_xentreeconnect', [1]));
        $this->assertCount(1, writer::$exports);
        $export = writer::$exports[0];
        $this->assertSame(1, $export['context']);
        $this->assertCount(2, $export['data']->activity);
        $first = $export['data']->activity[0];
        $this->assertSame(['eventtype', 'occurredat', 'receivedat', 'sessionseconds', 'sessionstatus'], array_keys((array) $first));
        $this->assertStringNotContainsString('d1', json_encode($export['data']));
        $this->assertStringNotContainsString('e1', json_encode($export['data']));
    }

    public function test_export_does_nothing_without_the_system_context_or_without_rows(): void {
        provider::export_user_data(new approved_contextlist((object) ['id' => 7], 'local_xentreeconnect', [55]));
        provider::export_user_data(new approved_contextlist((object) ['id' => 99], 'local_xentreeconnect', [1]));
        $this->assertSame([], writer::$exports);
    }

    public function test_delete_data_for_user_removes_only_that_user(): void {
        global $DB;
        provider::delete_data_for_user(new approved_contextlist((object) ['id' => 7], 'local_xentreeconnect', [55]));
        $this->assertCount(3, $DB->rows, 'a context that is not the system context is ignored');
        provider::delete_data_for_user(new approved_contextlist((object) ['id' => 7], 'local_xentreeconnect', [1]));
        $this->assertSame([3], array_keys($DB->rows));
    }

    public function test_delete_data_for_users_and_for_all_users(): void {
        global $DB;
        provider::delete_data_for_users(new approved_userlist(new \context_course(), 'local_xentreeconnect', [7, 8]));
        $this->assertCount(3, $DB->rows);
        provider::delete_data_for_users(new approved_userlist(\context_system::instance(), 'local_xentreeconnect', []));
        $this->assertCount(3, $DB->rows);
        provider::delete_data_for_users(new approved_userlist(\context_system::instance(), 'local_xentreeconnect', [8]));
        $this->assertSame([1, 2], array_keys($DB->rows));

        provider::delete_data_for_all_users_in_context(new \context_course());
        $this->assertCount(2, $DB->rows);
        provider::delete_data_for_all_users_in_context(\context_system::instance());
        $this->assertSame([], $DB->rows);
    }

    public function test_the_provider_implements_the_interfaces_moodle_requires(): void {
        $this->assertInstanceOf(\core_privacy\local\metadata\provider::class, new provider());
        $this->assertInstanceOf(\core_privacy\local\request\plugin\provider::class, new provider());
        $this->assertInstanceOf(\core_privacy\local\request\core_userlist_provider::class, new provider());
    }
}
