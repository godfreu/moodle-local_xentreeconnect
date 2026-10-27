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
 * Admin status page for Xentree Connect.
 *
 * Shows whether the plugin is ready and the values to register in Xentree. It never prints the private key.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 G. E. Bayu Alfian <79591609+godfreu@users.noreply.github.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/xentreeconnect/index.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('statuspage', 'local_xentreeconnect'));
$PAGE->set_heading(get_string('statuspage', 'local_xentreeconnect'));

$store = new \local_xentreeconnect\moodle_store();
$statusurl = new moodle_url('/local/xentreeconnect/index.php');

// "Delete all Xentree data": a confirmation page first, then a sesskey-protected POST.
if (optional_param('deleteall', 0, PARAM_BOOL)) {
    if (optional_param('confirm', 0, PARAM_BOOL)) {
        require_sesskey();
        $deleted = $store->delete_all();
        redirect($statusurl, get_string('deletealldone', 'local_xentreeconnect', $deleted), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('deleteallconfirm', 'local_xentreeconnect'),
        new moodle_url($statusurl, ['deleteall' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        $statusurl
    );
    echo $OUTPUT->heading(get_string('statusreceiving', 'local_xentreeconnect'), 3);
$report = \local_xentreeconnect\status_report::build(
    \local_xentreeconnect\credentials::from_site_config(),
    $store,
    (new moodle_url('/local/xentreeconnect/webhook.php'))->out(false),
    \local_xentreeconnect\retention::days(get_config('local_xentreeconnect', 'retentiondays')),
    time()
);
foreach ($report['warnings'] as $warning) {
    $parts = explode(':', $warning);
    if ($parts[0] === 'filefailed') {
        $text = get_string('warning_filefailed', 'local_xentreeconnect', [
            'name' => get_string('credential_' . ['key' => 'privatekeypem', 'secret' => 'webhooksecret', 'previoussecret' => 'webhooksecretprev'][$parts[1]], 'local_xentreeconnect'),
            'reason' => get_string('filereason_' . $parts[2], 'local_xentreeconnect'),
        ]);
    } else {
        $text = get_string('warning_' . $parts[0], 'local_xentreeconnect');
    }
    echo $OUTPUT->notification($text, 'warning');
}
echo html_writer::tag('p', html_writer::tag('strong', get_string('receiverurl', 'local_xentreeconnect')) . ': ' .
    html_writer::tag('code', s($report['receiverurl'])));
echo html_writer::tag('p', get_string('receiverurlhelp', 'local_xentreeconnect'), ['class' => 'text-muted']);

// Where each credential comes from. Values are never shown.
$items = '';
foreach (['privatekeypem' => 'key', 'webhooksecret' => 'secret', 'webhooksecretprev' => 'previoussecret'] as $setting => $name) {
    $info = $report[$name];
    $items .= html_writer::tag('li', get_string('credentialsource', 'local_xentreeconnect', [
        'name' => get_string('credential_' . $setting, 'local_xentreeconnect'),
        'state' => get_string($info['configured'] ? 'credentialset' : 'credentialnotset', 'local_xentreeconnect'),
        'source' => get_string('source_' . preg_replace('/[^a-z]/', '', $info['source']), 'local_xentreeconnect'),
    ]));
}
echo $OUTPUT->heading(get_string('credentialstatus', 'local_xentreeconnect'), 4);
echo html_writer::tag('ul', $items);

echo $OUTPUT->heading(get_string('last24h', 'local_xentreeconnect'), 4);
$table = new html_table();
$table->data = [
    [get_string('events24h', 'local_xentreeconnect'), (string) $report['events24h']],
    [get_string('rejected24h', 'local_xentreeconnect'), (string) $report['rejected24h']],
];
foreach ($report['outcomes'] as $outcome => $count) {
    $table->data[] = [s(get_string('outcomedetail', 'local_xentreeconnect') . ': ' . $outcome), (string) $count];
}
echo html_writer::table($table);
echo html_writer::tag('p', get_string('retentionnow', 'local_xentreeconnect', $report['retentiondays']));

echo $OUTPUT->heading(get_string('deletealldata', 'local_xentreeconnect'), 4);
echo html_writer::tag('p', get_string('deletealldatadesc', 'local_xentreeconnect'));
echo html_writer::link(new moodle_url($statusurl, ['deleteall' => 1]), get_string('deletealldata', 'local_xentreeconnect'),
    ['class' => 'btn btn-danger']);

echo $OUTPUT->footer();
    exit;
}

$launcher = \local_xentreeconnect\launcher::from_site_config();
$problems = $launcher->problems();
$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'local_xentreeconnect']);

$jwks = null;
$jwkserror = null;
try {
    $jwks = $launcher->public_jwks();
} catch (\RuntimeException $e) {
    $jwkserror = $e->getMessage();
}

echo $OUTPUT->header();

if ($problems) {
    $items = '';
    foreach ($problems as $key => $reason) {
        $items .= html_writer::tag('li', get_string($key, 'local_xentreeconnect') . ' — ' .
            get_string('problem_' . $reason, 'local_xentreeconnect'));
    }
    echo $OUTPUT->notification(
        get_string('statusincomplete', 'local_xentreeconnect') . html_writer::tag('ul', $items), 'warning');
} else {
    echo $OUTPUT->notification(get_string('statusready', 'local_xentreeconnect'), 'success');
}
$buttons = html_writer::link($settingsurl, get_string('opensettings', 'local_xentreeconnect'),
    ['class' => 'btn btn-secondary mr-2']);
if (!$problems) {
    $buttons .= html_writer::link(new moodle_url('/local/xentreeconnect/launch.php'),
        get_string('testlaunch', 'local_xentreeconnect'), ['class' => 'btn btn-primary']);
}
echo html_writer::div($buttons, 'mb-4');

echo $OUTPUT->heading(get_string('registerheading', 'local_xentreeconnect'), 3);
echo html_writer::tag('p', get_string('registerintro', 'local_xentreeconnect'));
$rows = [];
foreach (['clientid', 'issuer', 'audience', 'organizationid'] as $key) {
    $value = (string) get_config('local_xentreeconnect', $key);
    $rows[] = [get_string($key, 'local_xentreeconnect'), $value === '' ? '—' : s($value)];
}
$table = new html_table();
$table->data = $rows;
echo html_writer::table($table);

echo $OUTPUT->heading(get_string('publicjwks', 'local_xentreeconnect'), 4);
if ($jwks !== null) {
    echo html_writer::tag('pre', s(json_encode($jwks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
        ['class' => 'border p-3 bg-light']);
} else {
    echo $OUTPUT->notification(s($jwkserror), 'info');
}

echo $OUTPUT->footer();
