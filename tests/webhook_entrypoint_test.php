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
 * Guards the entry script of the webhook.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect;

defined('MOODLE_INTERNAL') || die();

/**
 * The webhook entry script has no Moodle session, logs through debugging() and never logs more than ids.
 *
 * @package    local_xentreeconnect
 * @coversNothing
 */
final class webhook_entrypoint_test extends \basic_testcase {
    /**
     * Source of webhook.php.
     *
     * @return string
     */
    private function source(): string {
        return file_get_contents(__DIR__ . '/../webhook.php');
    }

    public function test_it_stays_public_and_sessionless(): void {
        $this->assertStringContainsString("define('NO_MOODLE_COOKIES', true);", $this->source());
        $this->assertStringContainsString("define('NO_DEBUG_DISPLAY', true);", $this->source());
        $this->assertStringNotContainsString('require_login', $this->source());
    }

    public function test_it_logs_through_debugging_and_not_error_log(): void {
        $this->assertStringNotContainsString('error_log(', $this->source());
        $this->assertSame(2, substr_count($this->source(), 'debugging('));
    }

    public function test_log_lines_carry_only_the_outcome_event_type_and_delivery_id(): void {
        $this->assertStringContainsString(
            'webhook {$outcome} type={$eventtype} delivery={$deliveryid}"',
            $this->source()
        );
        // The failure line names the exception class only, never its message.
        $this->assertStringContainsString("get_class(\$e), DEBUG_NORMAL", $this->source());
        $this->assertStringNotContainsString('getMessage', $this->source());
    }
}
