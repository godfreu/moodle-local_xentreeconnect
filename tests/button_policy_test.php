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
 * Tests for the Connect button visibility rules.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 G. E. Bayu Alfian <79591609+godfreu@users.noreply.github.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_xentreeconnect;

/**
 * Tests for the Connect button visibility rules.
 *
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\button_policy
 */
final class button_policy_test extends \basic_testcase {
    public function test_configured_plugin_shows_button_to_everyone(): void {
        $this->assertSame(button_policy::BUTTON, button_policy::decide(true, false));
        $this->assertSame(button_policy::BUTTON, button_policy::decide(true, true));
    }

    public function test_incomplete_configuration_is_reported_to_admins_only(): void {
        $this->assertSame(button_policy::ADMIN_NOTICE, button_policy::decide(false, true));
        $this->assertSame(button_policy::HIDDEN, button_policy::decide(false, false));
    }

    public function test_pagetype_to_placement(): void {
        $expected = [
            'my-index' => 'dashboard',
            'course-view-topics' => 'course',
            'course-view-weeks' => 'course',
            'course-view-singleactivity' => 'course',
            'login-index' => 'login',
            'site-index' => null,
            'mod-quiz-view' => null,
            'my-index-extra' => null,
            'admin-setting-local_xentreeconnect' => null,
            '' => null,
        ];
        foreach ($expected as $pagetype => $placement) {
            $this->assertSame($placement, button_policy::placement_for_pagetype((string) $pagetype), "pagetype '$pagetype'");
        }
    }

    public function test_enabled_placements_parsing(): void {
        $this->assertSame([], button_policy::enabled_placements(false));
        $this->assertSame([], button_policy::enabled_placements(null));
        $this->assertSame([], button_policy::enabled_placements(''));
        $this->assertSame(['dashboard'], button_policy::enabled_placements('dashboard'));
        $this->assertSame(['dashboard', 'course', 'login'], button_policy::enabled_placements('login,course,dashboard'));
        $this->assertSame(['course'], button_policy::enabled_placements(' course , unknown,<script>'));
    }

    public function test_should_render_requires_the_placement_to_be_enabled(): void {
        $this->assertTrue(button_policy::should_render('my-index', ['dashboard']));
        $this->assertFalse(button_policy::should_render('my-index', ['course', 'login']));
        $this->assertFalse(button_policy::should_render('my-index', []));
        $this->assertFalse(button_policy::should_render('site-index', ['dashboard', 'course', 'login']));
        $this->assertTrue(button_policy::should_render('login-index', ['login']));
        $this->assertTrue(button_policy::should_render('course-view-topics', ['course']));
    }

    public function test_label_uses_default_when_custom_text_is_empty(): void {
        $this->assertSame('Default', button_policy::label('', 'Default'));
        $this->assertSame('Default', button_policy::label('   ', 'Default'));
        $this->assertSame('Default', button_policy::label(false, 'Default'));
        $this->assertSame('Default', button_policy::label(null, 'Default'));
    }

    public function test_label_uses_and_limits_custom_text(): void {
        $this->assertSame('Link my Xentree', button_policy::label('  Link my Xentree  ', 'Default'));
        $long = str_repeat('é', 200);
        $this->assertSame(button_policy::MAX_LABEL_LENGTH, mb_strlen(button_policy::label($long, 'Default')));
    }
}
