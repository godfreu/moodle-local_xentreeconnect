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
 * The admin status page of Xentree Connect.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xentreeconnect\output;

/**
 * The admin status page: configuration problems, the values to register in Xentree, the public JWKS, and the report of
 * the webhook receiver. It never contains a private key or a secret value.
 *
 * @package    local_xentreeconnect
 */
class status_page implements \renderable, \templatable {
    /** @var string[] Setting name => 'missing' or 'invalid'. */
    private array $problems;

    /** @var string[] Registration value name => configured value. */
    private array $registration;

    /** @var array|null Public JWKS, or null when it cannot be derived. */
    private ?array $jwks;

    /** @var string Why the JWKS is not available (empty when it is). */
    private string $jwkserror;

    /** @var array Result of {@see \local_xentreeconnect\status_report::build()}. */
    private array $report;

    /**
     * @param string[] $problems Setting name => 'missing' or 'invalid'. Empty when the plugin is ready.
     * @param string[] $registration Registration value name => configured value.
     * @param array|null $jwks Public JWKS, or null.
     * @param string $jwkserror Reason the JWKS is unavailable.
     * @param array $report Result of status_report::build().
     */
    public function __construct(array $problems, array $registration, ?array $jwks, string $jwkserror, array $report) {
        $this->problems = $problems;
        $this->registration = $registration;
        $this->jwks = $jwks;
        $this->jwkserror = $jwkserror;
        $this->report = $report;
    }

    /**
     * Collects the data for the current site.
     *
     * @return self
     */
    public static function for_site(): self {
        $launcher = \local_xentreeconnect\launcher::from_site_config();
        $jwks = null;
        $jwkserror = '';
        try {
            $jwks = $launcher->public_jwks();
        } catch (\RuntimeException $e) {
            $jwkserror = $e->getMessage();
        }
        $registration = [];
        foreach (['clientid', 'issuer', 'audience', 'organizationid'] as $key) {
            $registration[$key] = (string) get_config('local_xentreeconnect', $key);
        }
        $report = \local_xentreeconnect\status_report::build(
            \local_xentreeconnect\credentials::from_site_config(),
            new \local_xentreeconnect\moodle_store(),
            (new \moodle_url('/local/xentreeconnect/webhook.php'))->out(false),
            \local_xentreeconnect\retention::days(get_config('local_xentreeconnect', 'retentiondays')),
            time()
        );
        return new self($launcher->problems(), $registration, $jwks, $jwkserror, $report);
    }

    /**
     * Text of one receiver warning code (see status_report::build()).
     *
     * @param string $warning Code such as 'keyindatabase' or 'filefailed:key:unreadable'.
     * @return string
     */
    private function warning_text(string $warning): string {
        $parts = explode(':', $warning);
        if ($parts[0] === 'filefailed') {
            $settings = ['key' => 'privatekeypem', 'secret' => 'webhooksecret', 'previoussecret' => 'webhooksecretprev'];
            return get_string('warning_filefailed', 'local_xentreeconnect', [
                'name' => get_string('credential_' . $settings[$parts[1]], 'local_xentreeconnect'),
                'reason' => get_string('filereason_' . $parts[2], 'local_xentreeconnect'),
            ]);
        }
        return get_string('warning_' . $parts[0], 'local_xentreeconnect');
    }

    /**
     * Data for the templates/status_page mustache template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $component = 'local_xentreeconnect';
        $report = $this->report;
        $data = new \stdClass();

        $data->ready = $this->problems === [];
        $data->statusready = get_string('statusready', $component);
        $data->statusincomplete = get_string('statusincomplete', $component);
        $data->problems = [];
        foreach ($this->problems as $key => $reason) {
            $data->problems[] = [
                'label' => get_string($key, $component),
                'reason' => get_string('problem_' . $reason, $component),
            ];
        }
        $data->hasproblems = $data->problems !== [];
        $data->settingsurl = (new \moodle_url('/admin/settings.php', ['section' => 'local_xentreeconnect']))->out(false);
        $data->opensettings = get_string('opensettings', $component);
        $data->testlaunchurl = (new \moodle_url('/local/xentreeconnect/launch.php'))->out(false);
        $data->testlaunch = get_string('testlaunch', $component);

        $data->registerheading = get_string('registerheading', $component);
        $data->registerintro = get_string('registerintro', $component);
        $data->registration = [];
        foreach ($this->registration as $key => $value) {
            $data->registration[] = ['label' => get_string($key, $component), 'value' => $value === '' ? '—' : $value];
        }
        $data->publicjwks = get_string('publicjwks', $component);
        $data->hasjwks = $this->jwks !== null;
        $data->jwks = $this->jwks === null ? '' : json_encode($this->jwks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $data->jwkserror = $this->jwkserror;

        $data->receivingheading = get_string('statusreceiving', $component);
        $data->warnings = [];
        foreach ($report['warnings'] as $warning) {
            $data->warnings[] = ['text' => $this->warning_text($warning)];
        }
        $data->haswarnings = $data->warnings !== [];
        $data->receiverurllabel = get_string('receiverurl', $component);
        $data->receiverurl = $report['receiverurl'];
        $data->receiverurlhelp = get_string('receiverurlhelp', $component);

        // Where each credential comes from. Values are never shown.
        $data->credentialheading = get_string('credentialstatus', $component);
        $data->credentials = [];
        $names = ['privatekeypem' => 'key', 'webhooksecret' => 'secret', 'webhooksecretprev' => 'previoussecret'];
        foreach ($names as $setting => $name) {
            $info = $report[$name];
            $data->credentials[] = ['text' => get_string('credentialsource', $component, [
                'name' => get_string('credential_' . $setting, $component),
                'state' => get_string($info['configured'] ? 'credentialset' : 'credentialnotset', $component),
                'source' => get_string('source_' . preg_replace('/[^a-z]/', '', $info['source']), $component),
            ])];
        }

        $data->last24hheading = get_string('last24h', $component);
        $data->counters = [
            ['label' => get_string('events24h', $component), 'value' => (string) $report['events24h']],
            ['label' => get_string('rejected24h', $component), 'value' => (string) $report['rejected24h']],
        ];
        foreach ($report['outcomes'] as $outcome => $count) {
            $data->counters[] = [
                'label' => get_string('outcomedetail', $component) . ': ' . $outcome,
                'value' => (string) $count,
            ];
        }
        $data->retention = get_string('retentionnow', $component, $report['retentiondays']);

        $data->deleteheading = get_string('deletealldata', $component);
        $data->deletedesc = get_string('deletealldatadesc', $component);
        $data->deleteurl = (new \moodle_url('/local/xentreeconnect/index.php', ['deleteall' => 1]))->out(false);
        return $data;
    }
}
