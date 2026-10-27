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
 * In-memory store implementation for standalone testing.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// In-memory stand-in for the Moodle database store, used by the standalone tests (not shipped as plugin code).

namespace local_xentreeconnect\tests;

use local_xentreeconnect\webhook\store;

final class memory_store implements store {
    /** @var array[] */
    public array $events = [];
    /** @var array<string,int> "hour|outcome" => count */
    public array $stats = [];
    /** @var int[] */
    public array $users;
    private int $nextid = 1;

    public function __construct(array $users = [7, 8, 9]) {
        $this->users = $users;
    }

    public function insert_event(array $record): bool {
        foreach ($this->events as $row) {
            if ($row['deliveryid'] === $record['deliveryid'] || $row['eventid'] === $record['eventid']) {
                return false;
            }
        }
        $this->events[$this->nextid++] = $record;
        return true;
    }

    public function user_exists(int $userid): bool {
        return in_array($userid, $this->users, true);
    }

    public function delete_user_events(int $userid): int {
        return $this->delete_where(fn($row) => $row['userid'] === $userid);
    }

    public function delete_received_before(int $timestamp): int {
        return $this->delete_where(fn($row) => $row['receivedat'] < $timestamp);
    }

    public function delete_all(): int {
        return $this->delete_where(fn($row) => true);
    }

    public function count_outcome(string $outcome, int $now): void {
        $key = ($now - ($now % 3600)) . '|' . $outcome;
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }

    public function outcome_counts_since(int $since): array {
        $counts = [];
        foreach ($this->stats as $key => $total) {
            [$hour, $outcome] = explode('|', $key, 2);
            if ((int) $hour >= $since - ($since % 3600)) {
                $counts[$outcome] = ($counts[$outcome] ?? 0) + $total;
            }
        }
        return $counts;
    }

    public function count_events_since(int $since): int {
        return count(array_filter($this->events, fn($row) => $row['receivedat'] >= $since));
    }

    public function delete_stats_before(int $timestamp): void {
        foreach (array_keys($this->stats) as $key) {
            if ((int) explode('|', $key, 2)[0] < $timestamp) {
                unset($this->stats[$key]);
            }
        }
    }

    /** Test helper: the total counted for an outcome, any hour. */
    public function counted(string $outcome): int {
        $total = 0;
        foreach ($this->stats as $key => $count) {
            if (explode('|', $key, 2)[1] === $outcome) {
                $total += $count;
            }
        }
        return $total;
    }

    private function delete_where(callable $match): int {
        $removed = 0;
        foreach ($this->events as $id => $row) {
            if ($match($row)) {
                unset($this->events[$id]);
                $removed++;
            }
        }
        return $removed;
    }
}
