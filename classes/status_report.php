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
 * Builds the data of the admin status page.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Pure PHP (no Moodle API): what the status page shows, built from injected data so it can be unit tested.

namespace local_xentreeconnect;

use local_xentreeconnect\webhook\receiver;
use local_xentreeconnect\webhook\store;

/**
 * Builds the data of the admin status page. It reports where each credential comes from and whether it is set, and
 * never contains a key or secret value.
 *
 * @package    local_xentreeconnect
 */
final class status_report {
    /** Window of the counters shown on the page. */
    public const WINDOW = DAYSECS;

    /**
     * @param credentials $credentials
     * @param store $store
     * @param string $receiverurl Address to register in Xentree.
     * @param int $retentiondays Configured retention (0 = never delete).
     * @param int $now
     * @return array{receiverurl:string,key:array,secret:array,previoussecret:array,events24h:int,rejected24h:int,outcomes:array,retentiondays:int,warnings:string[]}
     */
    public static function build(credentials $credentials, store $store, string $receiverurl, int $retentiondays, int $now): array {
        $key = self::describe($credentials->resolve('privatekeypem'));
        $secret = self::describe($credentials->resolve('webhooksecret'));
        $previous = self::describe($credentials->resolve('webhooksecretprev'));

        $since = $now - self::WINDOW;
        $outcomes = $store->outcome_counts_since($since);
        ksort($outcomes);
        $rejected = 0;
        foreach (receiver::REJECTIONS as $outcome) {
            $rejected += $outcomes[$outcome] ?? 0;
        }

        $warnings = [];
        if ($retentiondays === 0) {
            $warnings[] = 'retentionzero';
        }
        if ($key['source'] === credentials::FROM_DATABASE) {
            $warnings[] = 'keyindatabase';
        }
        if ($secret['source'] === credentials::FROM_DATABASE) {
            $warnings[] = 'secretindatabase';
        }
        if ($secret['source'] === credentials::NONE) {
            $warnings[] = 'secretmissing';
        }
        foreach (['key' => $key, 'secret' => $secret, 'previoussecret' => $previous] as $name => $item) {
            if ($item['problem'] !== null) {
                $warnings[] = 'filefailed:' . $name . ':' . $item['problem'];
            }
        }

        return [
            'receiverurl' => $receiverurl,
            'key' => $key,
            'secret' => $secret,
            'previoussecret' => $previous,
            'events24h' => $store->count_events_since($since),
            'rejected24h' => $rejected,
            'outcomes' => $outcomes,
            'retentiondays' => $retentiondays,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array{value:string,source:string,problem:?string} $resolved
     * @return array{configured:bool,source:string,problem:?string}
     */
    private static function describe(array $resolved): array {
        return [
            'configured' => $resolved['value'] !== '',
            'source' => $resolved['source'],
            'problem' => $resolved['problem'],
        ];
    }
}
