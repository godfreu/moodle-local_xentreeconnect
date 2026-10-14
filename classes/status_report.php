<?php
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
