<?php

namespace local_xentreeconnect\webhook;

/**
 * Persistence needed by the webhook receiver and the retention/privacy code.
 *
 * Kept as an interface so the receiver's logic is unit tested without a Moodle database.
 *
 * @package    local_xentreeconnect
 */
interface store {
    /**
     * Stores an activity row.
     *
     * @param array $record Keys: deliveryid, eventid, eventtype, userid, occurredat, receivedat, sessionseconds, sessionstatus.
     * @return bool True when stored; false when a row with the same deliveryid or eventid already exists.
     */
    public function insert_event(array $record): bool;

    /** Whether a Moodle user exists and is not deleted. */
    public function user_exists(int $userid): bool;

    /** Deletes every stored row of one user; returns how many. */
    public function delete_user_events(int $userid): int;

    /** Deletes rows received before the timestamp; returns how many. */
    public function delete_received_before(int $timestamp): int;

    /** Deletes every stored row; returns how many. */
    public function delete_all(): int;

    /** Counts one outcome of the receiver in the hour containing $now. */
    public function count_outcome(string $outcome, int $now): void;

    /** @return array<string,int> Outcome => total since the timestamp. */
    public function outcome_counts_since(int $since): array;

    /** Number of activity rows received since the timestamp. */
    public function count_events_since(int $since): int;

    /** Removes counter rows older than the timestamp. */
    public function delete_stats_before(int $timestamp): void;
}
