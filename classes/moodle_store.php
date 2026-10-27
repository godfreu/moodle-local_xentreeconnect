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
 * Database store implementation for Moodle.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_xentreeconnect;

use local_xentreeconnect\webhook\store;

defined('MOODLE_INTERNAL') || die();

/**
 * {@see store} on Moodle's database API.
 *
 * @package    local_xentreeconnect
 */
class moodle_store implements store {
    private const TABLE = 'local_xentreeconnect_event';
    private const STAT = 'local_xentreeconnect_stat';

    public function insert_event(array $record): bool {
        global $DB;
        if ($DB->record_exists(self::TABLE, ['deliveryid' => $record['deliveryid']]) ||
                $DB->record_exists(self::TABLE, ['eventid' => $record['eventid']])) {
            return false;
        }
        try {
            $DB->insert_record(self::TABLE, (object) $record, false);
        } catch (\dml_write_exception $e) {
            // Two requests raced past the check: the unique indexes decide, and the loser is a duplicate.
            if ($DB->record_exists(self::TABLE, ['deliveryid' => $record['deliveryid']]) ||
                    $DB->record_exists(self::TABLE, ['eventid' => $record['eventid']])) {
                return false;
            }
            throw $e;
        }
        return true;
    }

    public function user_exists(int $userid): bool {
        global $DB;
        return $DB->record_exists('user', ['id' => $userid, 'deleted' => 0]);
    }

    public function delete_user_events(int $userid): int {
        global $DB;
        $count = $DB->count_records(self::TABLE, ['userid' => $userid]);
        $DB->delete_records(self::TABLE, ['userid' => $userid]);
        return $count;
    }

    public function delete_received_before(int $timestamp): int {
        global $DB;
        $count = $DB->count_records_select(self::TABLE, 'receivedat < :ts', ['ts' => $timestamp]);
        $DB->delete_records_select(self::TABLE, 'receivedat < :ts', ['ts' => $timestamp]);
        return $count;
    }

    public function delete_all(): int {
        global $DB;
        $count = $DB->count_records(self::TABLE);
        $DB->delete_records(self::TABLE);
        return $count;
    }

    public function count_outcome(string $outcome, int $now): void {
        global $DB;
        $hour = $now - ($now % 3600);
        $params = ['hourstart' => $hour, 'outcome' => $outcome];
        $row = $DB->get_record(self::STAT, $params);
        if ($row) {
            $DB->execute('UPDATE {' . self::STAT . '} SET cnt = cnt + 1 WHERE id = :id', ['id' => $row->id]);
            return;
        }
        try {
            $DB->insert_record(self::STAT, (object) ($params + ['cnt' => 1]), false);
        } catch (\dml_write_exception $e) {
            $DB->execute('UPDATE {' . self::STAT . '} SET cnt = cnt + 1 WHERE hourstart = :hourstart AND outcome = :outcome', $params);
        }
    }

    public function outcome_counts_since(int $since): array {
        global $DB;
        $rows = $DB->get_records_sql(
            'SELECT outcome, SUM(cnt) AS total FROM {' . self::STAT . '} WHERE hourstart >= :since GROUP BY outcome',
            ['since' => $since - ($since % 3600)]
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row->outcome] = (int) $row->total;
        }
        return $counts;
    }

    public function count_events_since(int $since): int {
        global $DB;
        return $DB->count_records_select(self::TABLE, 'receivedat >= :since', ['since' => $since]);
    }

    public function delete_stats_before(int $timestamp): void {
        global $DB;
        $DB->delete_records_select(self::STAT, 'hourstart < :ts', ['ts' => $timestamp]);
    }
}
