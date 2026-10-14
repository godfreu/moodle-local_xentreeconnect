<?php

namespace local_xentreeconnect\privacy;

// Runs only inside a Moodle PHPUnit environment (it needs the real Privacy API and a database). In the standalone
// runner the framework class is absent and the file declares nothing.
if (!class_exists(\core_privacy\tests\provider_testcase::class)) {
    return;
}

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\tests\provider_testcase;

/**
 * Privacy provider against Moodle's real Privacy API and database.
 *
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\privacy\provider
 * @runTestsInSeparateProcesses
 */
final class provider_test extends provider_testcase {
    private function add_event(int $userid, string $id): void {
        global $DB;
        $DB->insert_record('local_xentreeconnect_event', (object) [
            'deliveryid' => "delivery-$id", 'eventid' => "event-$id", 'eventtype' => 'erpi.session.completed', 'userid' => $userid,
            'occurredat' => 1790000000, 'receivedat' => 1790000100, 'sessionseconds' => 600, 'sessionstatus' => 'completed',
        ]);
    }

    public function test_metadata(): void {
        $collection = provider::get_metadata(new collection('local_xentreeconnect'));
        $this->assertCount(2, $collection->get_collection());
    }

    public function test_contexts_users_export_and_delete(): void {
        global $DB;
        $this->resetAfterTest();
        $one = $this->getDataGenerator()->create_user();
        $two = $this->getDataGenerator()->create_user();
        $this->add_event($one->id, 'a1');
        $this->add_event($one->id, 'a2');
        $this->add_event($two->id, 'b1');
        $system = \context_system::instance();

        $contexts = provider::get_contexts_for_userid($one->id);
        $this->assertSame([(int) $system->id], array_map('intval', $contexts->get_contextids()));
        $this->assertEmpty(provider::get_contexts_for_userid($this->getDataGenerator()->create_user()->id)->get_contextids());

        $userlist = new userlist($system, 'local_xentreeconnect');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$one->id, $two->id], $userlist->get_userids());

        $this->export_context_data_for_user($one->id, $system, 'local_xentreeconnect');
        $data = \core_privacy\local\request\writer::with_context($system)->get_data([
            get_string('pluginname', 'local_xentreeconnect'), get_string('privacy:path:activity', 'local_xentreeconnect')]);
        $this->assertCount(2, $data->activity);

        provider::delete_data_for_user(new approved_contextlist($one, 'local_xentreeconnect', [$system->id]));
        $this->assertSame(0, $DB->count_records('local_xentreeconnect_event', ['userid' => $one->id]));
        $this->assertSame(1, $DB->count_records('local_xentreeconnect_event', ['userid' => $two->id]));

        provider::delete_data_for_users(new approved_userlist($system, 'local_xentreeconnect', [$two->id]));
        $this->assertSame(0, $DB->count_records('local_xentreeconnect_event'));

        $this->add_event($one->id, 'c1');
        provider::delete_data_for_all_users_in_context($system);
        $this->assertSame(0, $DB->count_records('local_xentreeconnect_event'));
    }
}
