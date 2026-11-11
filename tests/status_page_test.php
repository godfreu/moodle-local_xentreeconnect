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
 * Tests for the status page renderable and its template.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect;

use local_xentreeconnect\output\status_page;

/**
 * Tests for the status page renderable and its template.
 *
 * @package    local_xentreeconnect
 * @covers     \local_xentreeconnect\output\status_page
 */
final class status_page_test extends \basic_testcase {
    /**
     * A status_report::build() result with every kind of warning.
     *
     * @return array
     */
    private function report(): array {
        $item = ['configured' => true, 'source' => 'database', 'problem' => null];
        return [
            'receiverurl' => 'https://moodle.example.org/local/xentreeconnect/webhook.php',
            'key' => $item,
            'secret' => ['configured' => false, 'source' => 'none', 'problem' => null],
            'previoussecret' => ['configured' => false, 'source' => 'none', 'problem' => 'unreadable'],
            'events24h' => 4,
            'rejected24h' => 2,
            'outcomes' => ['accepted' => 4, 'bad_signature' => 2],
            'retentiondays' => 0,
            'warnings' => ['retentionzero', 'keyindatabase', 'filefailed:previoussecret:unreadable'],
        ];
    }

    /**
     * Exports a status page for the given state.
     */
    private function export(array $problems = [], ?array $jwks = ['keys' => []], string $jwkserror = ''): \stdClass {
        $registration = ['clientid' => 'example-client', 'issuer' => '', 'audience' => 'xentree-connect',
            'organizationid' => 'org-1'];
        $page = new status_page($problems, $registration, $jwks, $jwkserror, $this->report());
        return $page->export_for_template($this->createStub(\renderer_base::class));
    }

    public function test_ready_page_offers_the_test_launch(): void {
        $data = $this->export();

        $this->assertTrue($data->ready);
        $this->assertFalse($data->hasproblems);
        $this->assertSame([], $data->problems);
        $this->assertStringContainsString('/local/xentreeconnect/launch.php', $data->testlaunchurl);
    }

    public function test_problems_are_listed_and_block_the_test_launch(): void {
        $data = $this->export(['clientid' => 'missing', 'baseurl' => 'invalid']);

        $this->assertFalse($data->ready);
        $this->assertTrue($data->hasproblems);
        $this->assertCount(2, $data->problems);
        $this->assertSame(get_string('clientid', 'local_xentreeconnect'), $data->problems[0]['label']);
        $this->assertSame(get_string('problem_missing', 'local_xentreeconnect'), $data->problems[0]['reason']);
    }

    public function test_receiver_report_and_delete_button_are_part_of_the_page(): void {
        $data = $this->export();

        $this->assertSame('https://moodle.example.org/local/xentreeconnect/webhook.php', $data->receiverurl);
        $this->assertCount(3, $data->warnings);
        $this->assertCount(3, $data->credentials);
        $this->assertSame(get_string('events24h', 'local_xentreeconnect'), $data->counters[0]['label']);
        $this->assertSame('4', $data->counters[0]['value']);
        $this->assertSame('2', $data->counters[1]['value']);
        // Two further rows: one per outcome, sorted by the report.
        $this->assertCount(4, $data->counters);
        $this->assertStringContainsString('deleteall=1', $data->deleteurl);
        $this->assertStringNotContainsString('sesskey', $data->deleteurl);
    }

    public function test_unset_registration_values_show_a_dash(): void {
        $data = $this->export();

        $this->assertSame('example-client', $data->registration[0]['value']);
        $this->assertSame('—', $data->registration[1]['value']);
    }

    public function test_jwks_or_the_reason_it_is_missing(): void {
        $ok = $this->export([], ['keys' => [['kid' => 'k1']]]);
        $this->assertTrue($ok->hasjwks);
        $this->assertStringContainsString('"kid": "k1"', $ok->jwks);

        $missing = $this->export(['privatekeypem' => 'missing'], null, 'Private key is not configured.');
        $this->assertFalse($missing->hasjwks);
        $this->assertSame('', $missing->jwks);
        $this->assertSame('Private key is not configured.', $missing->jwkserror);
    }

    public function test_the_export_never_contains_secret_values(): void {
        $report = $this->report();
        $report['key']['value'] = 'SECRET-PEM';
        $page = new status_page([], [], null, '', $report);
        $json = json_encode($page->export_for_template($this->createStub(\renderer_base::class)));

        $this->assertStringNotContainsString('SECRET-PEM', $json);
    }

    public function test_every_template_tag_is_provided_by_the_export(): void {
        $template = file_get_contents(__DIR__ . '/../templates/status_page.mustache');
        $template = preg_replace('/\{\{!.*?\}\}/s', '', $template);
        $data = (array) $this->export(['clientid' => 'missing']);

        // Walk the sections: a name inside {{#list}} must be a key of the list items.
        $scope = [array_keys($data)];
        preg_match_all('/\{\{([#^\/]?)\s*([a-z0-9]+)\s*\}\}/', $template, $tags, PREG_SET_ORDER);
        foreach ($tags as [, $kind, $name]) {
            if ($kind === '/') {
                array_pop($scope);
                continue;
            }
            $this->assertContains($name, end($scope), "Template uses {{{$name}}} but the export does not provide it.");
            if ($kind === '#' || $kind === '^') {
                $children = is_array($data[$name] ?? null) && $data[$name] !== [] && is_array(reset($data[$name]))
                    ? array_keys(reset($data[$name])) : end($scope);
                $scope[] = $children;
            }
        }
        $this->assertCount(1, $scope, 'Unbalanced sections in the template.');
    }
}
