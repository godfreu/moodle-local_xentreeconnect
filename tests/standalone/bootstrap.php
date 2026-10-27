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
 * Standalone PHPUnit test bootstrap.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 G. E. Bayu Alfian <79591609+godfreu@users.noreply.github.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
