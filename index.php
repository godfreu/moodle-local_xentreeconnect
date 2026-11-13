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
 * @copyright  2026 PT Global Otomat Digital
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

// Delete all Xentree data: a confirmation page first, then a sesskey-protected POST.
if (optional_param('deleteall', 0, PARAM_BOOL)) {
    if (optional_param('confirm', 0, PARAM_BOOL)) {
        require_sesskey();
        $deleted = $store->delete_all();
        redirect(
            $statusurl,
            get_string('deletealldone', 'local_xentreeconnect', $deleted),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('deleteallconfirm', 'local_xentreeconnect'),
        new moodle_url($statusurl, ['deleteall' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        $statusurl
    );
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->render(\local_xentreeconnect\output\status_page::for_site());
echo $OUTPUT->footer();
