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
 * Guards the structure of the status page entry script.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect;

defined('MOODLE_INTERNAL') || die();

/**
 * The receiver report and the "Delete all" button once ended up inside the delete-confirmation branch of index.php,
 * so the normal status page never showed them. This test keeps the confirmation branch limited to confirm and delete.
 *
 * @package    local_xentreeconnect
 * @coversNothing
 */
final class index_structure_test extends \basic_testcase {
    /**
     * Returns the source of the if (optional_param('deleteall' ...)) { ... } block.
     *
     * @param string $source
     * @return array{0:int,1:int} Start and end byte offsets of the block body.
     */
    private function deleteall_block(string $source): array {
        $start = strpos($source, "if (optional_param('deleteall'");
        $this->assertNotFalse($start, 'The delete-all branch is missing from index.php.');
        $open = strpos($source, '{', $start);
        $depth = 0;
        $length = strlen($source);
        for ($i = $open; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } else if ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return [$open, $i];
                }
            }
        }
        $this->fail('Unbalanced braces in index.php.');
    }

    public function test_confirmation_branch_only_confirms_and_deletes(): void {
        $source = file_get_contents(__DIR__ . '/../index.php');
        [$from, $to] = $this->deleteall_block($source);
        $branch = substr($source, $from, $to - $from);

        $this->assertStringContainsString('require_sesskey()', $branch);
        $this->assertStringContainsString('delete_all()', $branch);
        $this->assertStringContainsString('->confirm(', $branch);
        $this->assertStringContainsString('exit', $branch);
        $this->assertStringNotContainsString('status_report::build', $branch);
        $this->assertStringNotContainsString("'deletealldata'", $branch);
    }

    public function test_normal_page_renders_the_status_page_that_holds_the_report_and_delete_button(): void {
        $source = file_get_contents(__DIR__ . '/../index.php');
        [$from, $to] = $this->deleteall_block($source);
        $branch = substr($source, $from, $to - $from);
        $page = substr($source, $to);

        $this->assertStringNotContainsString('status_page', $branch);
        $this->assertStringContainsString('output\\status_page::for_site()', $page);
        $this->assertStringContainsString('$OUTPUT->footer()', $page);

        $renderable = file_get_contents(__DIR__ . '/../classes/output/status_page.php');
        $this->assertStringContainsString('status_report::build', $renderable);
        $this->assertStringContainsString("['deleteall' => 1]", $renderable);
    }
}
