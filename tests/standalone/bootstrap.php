<?php
// Lets the pure-PHP tests run with a plain PHPUnit, without a Moodle checkout (not shipped in the plugin zip):
//   phpunit --bootstrap tests/standalone/bootstrap.php tests
// Inside Moodle, use its own PHPUnit setup instead.

// Moodle constants the plugin's pure classes use.
if (!defined('DAYSECS')) {
    define('DAYSECS', 86400);
}

if (!class_exists('basic_testcase')) {
    class basic_testcase extends \PHPUnit\Framework\TestCase {
    }
}

spl_autoload_register(function (string $class): void {
    $prefix = 'local_xentreeconnect\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $file = dirname(__DIR__, 2) . '/classes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/memory_store.php';
require_once __DIR__ . '/moodle_stubs.php';
