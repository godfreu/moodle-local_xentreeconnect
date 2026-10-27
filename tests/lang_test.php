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
 * Guards language packs to stay in sync and valid.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_xentreeconnect;

/**
 * Guards the language packs: EN and ID stay in sync and every literal string key used in code exists.
 *
 * @package    local_xentreeconnect
 * @coversNothing
 */
final class lang_test extends \basic_testcase {
    private function strings(string $lang): array {
        $string = [];
        $file = dirname(__DIR__) . "/lang/$lang/local_xentreeconnect.php";
        if (!defined('MOODLE_INTERNAL')) {
            define('MOODLE_INTERNAL', true);
        }
        require $file;
        return $string;
    }

    public function test_english_and_indonesian_have_the_same_keys(): void {
        $en = array_keys($this->strings('en'));
        $id = array_keys($this->strings('id'));
        sort($en);
        sort($id);
        $this->assertSame($en, $id);
    }

    public function test_indonesian_is_translated_not_copied(): void {
        $en = $this->strings('en');
        $id = $this->strings('id');
        $same = array_keys(array_filter($id, fn($text, $key) => $text === $en[$key], ARRAY_FILTER_USE_BOTH));
        // Brand and technical terms may legitimately be identical.
        $allowed = ['pluginname', 'clientid', 'issuer', 'audience', 'kid'];
        $this->assertSame([], array_values(array_diff($same, $allowed)));
    }

    public function test_every_literal_string_key_used_in_code_is_defined(): void {
        $en = $this->strings('en');
        $root = dirname(__DIR__);
        $files = array_merge(glob("$root/*.php"), glob("$root/classes/*.php"), glob("$root/classes/output/*.php"), glob("$root/classes/privacy/*.php"));
        $missing = [];
        foreach ($files as $file) {
            preg_match_all("/get_string\(\s*'([a-z0-9_]+)'\s*,\s*'local_xentreeconnect'/", file_get_contents($file), $matches);
            foreach ($matches[1] as $key) {
                if (!array_key_exists($key, $en)) {
                    $missing[] = basename($file) . ':' . $key;
                }
            }
        }
        $this->assertSame([], $missing);
    }

    public function test_privacy_strings_declared_by_the_provider_exist(): void {
        $en = $this->strings('en');
        $source = file_get_contents(dirname(__DIR__) . '/classes/privacy/provider.php');
        preg_match_all("/'(privacy:metadata[a-z:_]*)'/", $source, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach (array_unique($matches[1]) as $key) {
            $this->assertArrayHasKey($key, $en, $key);
        }
    }

    public function test_dynamic_problem_keys_are_defined(): void {
        $en = $this->strings('en');
        foreach (launcher::REQUIRED as $setting) {
            $this->assertArrayHasKey($setting, $en, "problem label for $setting");
        }
        $this->assertArrayHasKey('problem_missing', $en);
        $this->assertArrayHasKey('problem_invalid', $en);
    }
}
