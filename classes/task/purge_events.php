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
 * Daily retention task for activity received from Xentree.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


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
