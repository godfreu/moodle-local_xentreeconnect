<?php

namespace local_xentreeconnect;

use local_xentreeconnect\tests\memory_store;

/**
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\retention
 */
final class retention_test extends \basic_testcase {
    private const NOW = 1790000000;

    private function row(string $id, int $receivedat, int $userid = 7): array {
        return [
            'deliveryid' => "d-$id", 'eventid' => "e-$id", 'eventtype' => 'erpi.session.completed', 'userid' => $userid,
            'occurredat' => $receivedat, 'receivedat' => $receivedat, 'sessionseconds' => 60, 'sessionstatus' => 'completed',
        ];
    }

    public function test_days_defaults_and_validation(): void {
        $this->assertSame(365, retention::days(null));
        $this->assertSame(365, retention::days(''));
        $this->assertSame(365, retention::days(false));
        $this->assertSame(365, retention::days('abc'));
        $this->assertSame(365, retention::days('-5'));
        $this->assertSame(0, retention::days('0'));
        $this->assertSame(30, retention::days('30'));
        $this->assertSame(90, retention::days(90));
    }

    public function test_purge_removes_only_rows_older_than_the_window(): void {
        $store = new memory_store();
        $store->insert_event($this->row('old', self::NOW - 400 * DAYSECS));
        $store->insert_event($this->row('edge', self::NOW - 366 * DAYSECS));
        $store->insert_event($this->row('keep', self::NOW - 364 * DAYSECS));
        $store->insert_event($this->row('new', self::NOW - DAYSECS));

        $this->assertSame(2, retention::purge($store, 365, self::NOW));
        $this->assertSame(['d-keep', 'd-new'], array_values(array_map(fn($r) => $r['deliveryid'], $store->events)));
        $this->assertSame(0, retention::purge($store, 365, self::NOW), 'running again is a no-op');
    }

    public function test_zero_days_never_deletes_activity(): void {
        $store = new memory_store();
        $store->insert_event($this->row('ancient', 1000));
        $this->assertSame(0, retention::purge($store, 0, self::NOW));
        $this->assertCount(1, $store->events);
    }

    public function test_counter_rows_are_pruned_after_thirty_days_regardless(): void {
        $store = new memory_store();
        $store->count_outcome('accepted', self::NOW - 40 * DAYSECS);
        $store->count_outcome('accepted', self::NOW - DAYSECS);
        retention::purge($store, 0, self::NOW);
        $this->assertSame(1, $store->counted('accepted'));
    }
}
