<?php
/**
 * Admin status page for Xentree Connect.
 *
 * Shows whether the plugin is ready and the values to register in Xentree. It never prints the private key.
 *
 * @package    local_xentreeconnect
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
echo html_writer::div(html_writer::link($settingsurl, get_string('opensettings', 'local_xentreeconnect'),
    ['class' => 'btn btn-secondary']), 'mb-4');

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
