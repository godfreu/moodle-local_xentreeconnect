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
 * Retention logic for the activity received from Xentree.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_xentreeconnect;

use local_xentreeconnect\webhook\store;

/**
 * Retention of the activity received from Xentree.
 *
 * Pure PHP: the window and the store are passed in.
 *
 * @package    local_xentreeconnect
 */
final class retention {
    /** Default number of days activity is kept. Interim value pending a legal decision. */
    public const DEFAULT_DAYS = 365;

    /** Counter rows (no personal data) are kept this long. */
    public const STAT_DAYS = 30;

    /**
     * Normalises the configured number of days.
     *
     * @param mixed $configured Raw config value; null/'' means "not set" and yields the default.
     * @return int Days, or 0 meaning "never delete automatically".
     */
    public static function days($configured): int {
        if ($configured === null || $configured === false || $configured === '') {
            return self::DEFAULT_DAYS;
        }
        if (!is_numeric($configured) || (int) $configured < 0) {
            return self::DEFAULT_DAYS;
        }
        return (int) $configured;
    }

    /**
     * Deletes activity older than the window. Days of 0 deletes nothing.
     *
     * @return int Rows deleted.
     */
    public static function purge(store $store, int $days, int $now): int {
        $store->delete_stats_before($now - self::STAT_DAYS * DAYSECS);
        if ($days <= 0) {
            return 0;
        }
        return $store->delete_received_before($now - $days * DAYSECS);
    }
}
