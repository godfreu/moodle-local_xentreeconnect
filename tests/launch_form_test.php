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
 * Tests for the launch form renderable and its template and AMD module.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect;

defined('MOODLE_INTERNAL') || die();

use local_xentreeconnect\output\launch_form;

/**
 * Tests for the launch form renderable and its template and AMD module.
 *
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\output\launch_form
 */
final class launch_form_test extends \basic_testcase {
    /**
     * Launch endpoint used in the tests.
     */
    private const ACTION = 'https://my.xentree.ai/connect/v1/launch/example-client';
    /**
     * Assertion used in the tests.
     */
    private const ASSERTION = 'aaa.bbb.ccc';

    public function test_export_passes_the_action_and_assertion_unchanged(): void {
        $form = new launch_form(self::ACTION, self::ASSERTION, 'Redirecting', 'Continue');
        $data = $form->export_for_template(new \renderer_base());

        $this->assertSame(self::ACTION, $data->action);
        $this->assertSame(self::ASSERTION, $data->assertion);
        $this->assertSame('Redirecting', $data->redirecting);
        $this->assertSame('Continue', $data->continuelabel);
        $this->assertSame(launch_form::FORM_ID, $data->formid);
    }

    public function test_create_takes_the_texts_from_the_language_pack(): void {
        $data = launch_form::create(self::ACTION, self::ASSERTION)->export_for_template(new \renderer_base());

        $this->assertSame('[local_xentreeconnect:redirecting]', $data->redirecting);
        $this->assertSame('[local_xentreeconnect:continuetoxentree]', $data->continuelabel);
    }

    public function test_template_posts_the_assertion_and_keeps_a_visible_submit_button(): void {
        $template = file_get_contents(__DIR__ . '/../templates/launch_form.mustache');

        $this->assertMatchesRegularExpression('/<form id="\{\{formid\}\}" method="post" action="\{\{action\}\}">/', $template);
        $this->assertStringContainsString('<input type="hidden" name="assertion" value="{{assertion}}">', $template);
        // Works without JavaScript: the button submits the form, nothing hides it.
        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*>\{\{continuelabel\}\}<\/button>/', $template);
        $this->assertStringNotContainsString('<script', $template);
        $this->assertStringNotContainsString('onsubmit', $template);
    }

    public function test_pages_use_no_inline_javascript(): void {
        foreach (['launch.php', 'index.php'] as $file) {
            $source = file_get_contents(__DIR__ . '/../' . $file);
            $this->assertStringNotContainsString('js_amd_inline', $source, $file);
            $this->assertStringNotContainsString('<script', $source, $file);
        }
        $this->assertStringContainsString(
            "js_call_amd('local_xentreeconnect/launch', 'init'",
            file_get_contents(__DIR__ . '/../launch.php')
        );
    }

    public function test_amd_source_submits_the_form_by_id(): void {
        $source = file_get_contents(__DIR__ . '/../amd/src/launch.js');

        $this->assertStringContainsString('@module     local_xentreeconnect/launch', $source);
        $this->assertStringContainsString('document.getElementById(formId)', $source);
        $this->assertStringContainsString('form.submit()', $source);
    }
}
