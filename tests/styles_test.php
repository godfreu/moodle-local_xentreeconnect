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
 * Guards the stylesheet that keeps the Connect button below Boost's fixed navbar.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect;

/**
 * Guards the stylesheet that keeps the Connect button below Boost's fixed navbar.
 *
 * @package    local_xentreeconnect
 * @coversNothing
 */
final class styles_test extends \basic_testcase {
    /**
     * Reads a file of the plugin.
     *
     * @param string $path Path relative to the plugin root.
     */
    private function read(string $path): string {
        $file = dirname(__DIR__) . '/' . $path;
        $this->assertFileExists($file, $path);
        $content = file_get_contents($file);
        $this->assertNotFalse($content, $path);
        return $content;
    }

    /**
     * The stylesheet without its comments.
     */
    private function rules(): string {
        return trim(preg_replace('~/\*.*?\*/~s', '', $this->read('styles.css')));
    }

    public function test_stylesheet_is_balanced_and_not_empty(): void {
        $rules = $this->rules();
        $this->assertNotSame('', $rules);
        $this->assertSame(substr_count($rules, '{'), substr_count($rules, '}'));
        $this->assertSame(2, substr_count($rules, '{'));
        $this->assertStringNotContainsString('@import', $rules);
        $this->assertStringNotContainsString('url(', $rules);
        // Moodle's stylelint forbids it, and the point is to work with Bootstrap's !important utilities, not fight them.
        $this->assertStringNotContainsString('!important', $rules);
    }

    public function test_button_is_pushed_below_the_fixed_navbar_with_padding(): void {
        $rules = $this->rules();
        $this->assertMatchesRegularExpression(
            '~^body\.uses-drawers #page-wrapper > \.local-xentreeconnect \{\s*padding-top: 61px;\s*\}~',
            $rules
        );
        // The wrapper has the Bootstrap my-3 class, which sets margins with !important, so margin-top would lose.
        $this->assertStringNotContainsString('margin-top: 61px', $rules);
    }

    public function test_the_offset_boost_gives_the_page_is_removed_only_next_to_the_button(): void {
        $this->assertMatchesRegularExpression(
            '%body\.uses-drawers #page-wrapper > \.local-xentreeconnect ~ #page\.drawers \{\s*margin-top: 0;\s*\}%',
            $this->rules()
        );
    }

    public function test_selectors_match_the_markup_the_plugin_renders(): void {
        $template = $this->read('templates/connect_button.mustache');
        $this->assertStringContainsString('<div class="local-xentreeconnect ', $template);
        $this->assertStringContainsString('my-3', $template);
    }
}
