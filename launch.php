<?php
/**
 * Starts a Xentree Connect launch for the logged-in user.
 *
 * Can be linked from anywhere (menu, custom HTML, block, theme): it signs the assertion and hands the browser to
 * Xentree with a form POST. First-time users see Xentree's consent screen.
 *
 * @package    local_xentreeconnect
 */

require_once(__DIR__ . '/../../config.php');

require_login(null, false);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/xentreeconnect/launch.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_xentreeconnect'));
$PAGE->set_heading(get_string('pluginname', 'local_xentreeconnect'));

if (isguestuser()) {
    $SESSION->wantsurl = $PAGE->url->out(false);
    redirect(get_login_url());
}

$launcher = \local_xentreeconnect\launcher::from_site_config();
$assertion = null;
$error = null;
try {
    $assertion = $launcher->build_assertion(\local_xentreeconnect\launcher::subject_for_user((int) $USER->id), $USER->email);
    $action = $launcher->launch_url();
} catch (\RuntimeException $e) {
    $error = $e->getMessage();
}

echo $OUTPUT->header();

if ($error !== null) {
    if (is_siteadmin()) {
        echo $OUTPUT->notification(s($error), 'warning');
        echo html_writer::link(new moodle_url('/local/xentreeconnect/index.php'), get_string('statuspage', 'local_xentreeconnect'),
            ['class' => 'btn btn-secondary']);
    } else {
        echo $OUTPUT->notification(get_string('launchunavailable', 'local_xentreeconnect'), 'error');
    }
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::tag('p', get_string('redirecting', 'local_xentreeconnect'));
echo html_writer::start_tag('form', ['id' => 'xentreeconnect-launch-form', 'method' => 'post', 'action' => $action]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'assertion', 'value' => $assertion]);
echo html_writer::tag('button', get_string('continuetoxentree', 'local_xentreeconnect'),
    ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');
$PAGE->requires->js_amd_inline("document.getElementById('xentreeconnect-launch-form').submit();");

echo $OUTPUT->footer();
