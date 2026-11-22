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
 * Guards the wiring of the header (primary navigation) item.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect;

/**
 * The callback itself needs Moodle, so these checks read the source: the hook is registered, new installs default to the
 * header, the upgrade keeps what existing sites saved, the label goes through format_string and the launch itself is
 * untouched.
 *
 * @package    local_xentreeconnect
 * @coversNothing
 */
final class header_item_test extends \basic_testcase {
    /**
     * Source of a plugin file.
     *
     * @param string $relative Path inside the plugin.
     */
    private function source(string $relative): string {
        $source = file_get_contents(dirname(__DIR__) . '/' . $relative);
        $this->assertNotFalse($source, $relative);
        return $source;
    }

    public function test_primary_navigation_hook_is_registered(): void {
        $hooks = $this->source('db/hooks.php');
        $this->assertStringContainsString('\core\hook\navigation\primary_extend::class', $hooks);
        $this->assertMatchesRegularExpression(
            "/primary_extend::class,\s*'callback' => \[\\\\local_xentreeconnect\\\\hook_callbacks::class, 'primary_extend'\]/",
            $hooks
        );
        $this->assertStringContainsString(
            'public static function primary_extend(primary_extend $hook): void',
            $this->source('classes/hook_callbacks.php')
        );
    }

    public function test_callback_uses_the_same_checks_as_the_button_and_links_to_launch(): void {
        $callbacks = $this->source('classes/hook_callbacks.php');
        $start = strpos($callbacks, 'function primary_extend');
        $this->assertNotFalse($start);
        $body = substr($callbacks, $start);
        $this->assertStringContainsString('launcher::from_site_config()->is_configured()', $body);
        $this->assertStringContainsString('isloggedin()', $body);
        $this->assertStringContainsString('isguestuser()', $body);
        $this->assertStringContainsString('button_policy::header_item_visible', $body);
        $this->assertStringContainsString("'/local/xentreeconnect/launch.php'", $body);
        $this->assertStringContainsString("'menulabel'", $body);
        $this->assertStringContainsString("get_string('launchmenu', 'local_xentreeconnect')", $body);
        // The navigation prints the text unescaped, so the label must go through format_string().
        $this->assertMatchesRegularExpression('/format_string\(\s*button_policy::label\(/', $body);
    }

    public function test_new_installs_default_to_the_header_only(): void {
        $settings = $this->source('settings.php');
        $this->assertMatchesRegularExpression(
            "/'local_xentreeconnect\/placements',.*?\['header' => 1\],\s*\[\s*'header' => get_string\('placement_header'/s",
            $settings
        );
        $this->assertStringContainsString("'local_xentreeconnect/menulabel'", $settings);
    }

    public function test_upgrade_does_not_overwrite_saved_placements(): void {
        $upgrade = $this->source('db/upgrade.php');
        $start = strpos($upgrade, 'if ($oldversion < 2026112201)');
        $this->assertNotFalse($start);
        $block = substr($upgrade, $start);
        // The only write is for a missing value, and it stores the old behaviour (nothing placed).
        $this->assertSame(1, substr_count($block, 'set_config('));
        $this->assertStringContainsString("if (get_config('local_xentreeconnect', 'placements') === false) {", $block);
        $this->assertStringContainsString("set_config('placements', '', 'local_xentreeconnect');", $block);
    }

    public function test_version_is_above_the_candidate_that_has_no_header_item(): void {
        $version = $this->source('version.php');
        $this->assertSame(1, preg_match('/\$plugin->version\s*=\s*(\d{10});/', $version, $m));
        $this->assertGreaterThan(2026112200, (int) $m[1]);
        $this->assertStringContainsString("\$plugin->release = '0.2.2';", $version);
        // db/upgrade.php has to carry the same number or the step never runs.
        $this->assertStringContainsString('upgrade_plugin_savepoint(true, ' . $m[1] . ",", $this->source('db/upgrade.php'));
    }

    public function test_launch_flow_files_are_not_part_of_this_feature(): void {
        $this->assertStringNotContainsString('primary_extend', $this->source('launch.php'));
        $this->assertStringNotContainsString('primary_extend', $this->source('classes/launcher.php'));
    }

    public function test_strings_exist_in_english_and_indonesian(): void {
        foreach (['en', 'id'] as $lang) {
            $string = [];
            require(dirname(__DIR__) . "/lang/$lang/local_xentreeconnect.php");
            foreach (['launchmenu', 'menulabel', 'menulabeldesc', 'placement_header'] as $key) {
                $this->assertArrayHasKey($key, $string, "$lang:$key");
                $this->assertNotSame('', trim($string[$key]));
            }
            $this->assertLessThanOrEqual(
                80,
                mb_strlen($string['launchmenu']),
                "$lang: the default text must fit the label limit"
            );
        }
    }
}
