<?php

namespace local_xentreeconnect\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Daily retention of the activity received from Xentree.
 *
 * @package    local_xentreeconnect
 */
class purge_events extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_purge', 'local_xentreeconnect');
    }

    public function execute(): void {
        $days = \local_xentreeconnect\retention::days(get_config('local_xentreeconnect', 'retentiondays'));
        $deleted = \local_xentreeconnect\retention::purge(new \local_xentreeconnect\moodle_store(), $days, time());
        mtrace("local_xentreeconnect: removed {$deleted} activity row(s) older than {$days} day(s).");
    }
}
