<?php

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => \local_xentreeconnect\task\purge_events::class,
        'blocking' => 0,
        'minute' => '17',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
