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
 * Settings definition for local_xentreeconnect.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_xentreeconnect', get_string('pluginname', 'local_xentreeconnect'));

    $settings->add(new admin_setting_heading(
        'local_xentreeconnect/header',
        get_string('settingsheader', 'local_xentreeconnect'),
        get_string('settingsheaderdesc', 'local_xentreeconnect', (new moodle_url('/local/xentreeconnect/index.php'))->out(false))
    ));

    $settings->add(new \local_xentreeconnect\admin_setting_httpsurl(
        'local_xentreeconnect/baseurl',
        get_string('baseurl', 'local_xentreeconnect'),
        get_string('baseurldesc', 'local_xentreeconnect'),
        'https://my.xentree.ai'
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/clientid',
        get_string('clientid', 'local_xentreeconnect'),
        get_string('clientiddesc', 'local_xentreeconnect'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/issuer',
        get_string('issuer', 'local_xentreeconnect'),
        get_string('issuerdesc', 'local_xentreeconnect', $CFG->wwwroot),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/audience',
        get_string('audience', 'local_xentreeconnect'),
        get_string('audiencedesc', 'local_xentreeconnect'),
        'xentree-connect',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/organizationid',
        get_string('organizationid', 'local_xentreeconnect'),
        get_string('organizationiddesc', 'local_xentreeconnect'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/kid',
        get_string('kid', 'local_xentreeconnect'),
        get_string('kiddesc', 'local_xentreeconnect'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new \local_xentreeconnect\admin_setting_privatekey(
        'local_xentreeconnect/privatekeypem',
        get_string('privatekeypem', 'local_xentreeconnect'),
        get_string('privatekeypemdesc', 'local_xentreeconnect')
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/privatekeyfile',
        get_string('privatekeyfile', 'local_xentreeconnect'),
        get_string('credentialfiledesc', 'local_xentreeconnect'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_heading(
        'local_xentreeconnect/webhookheader',
        get_string('webhookheader', 'local_xentreeconnect'),
        get_string('webhookheaderdesc', 'local_xentreeconnect', (new moodle_url('/local/xentreeconnect/webhook.php'))->out(false))
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_xentreeconnect/webhooksecret',
        get_string('webhooksecret', 'local_xentreeconnect'),
        get_string('webhooksecretdesc', 'local_xentreeconnect'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_xentreeconnect/webhooksecretprev',
        get_string('webhooksecretprev', 'local_xentreeconnect'),
        get_string('webhooksecretprevdesc', 'local_xentreeconnect'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/webhooksecretfile',
        get_string('webhooksecretfile', 'local_xentreeconnect'),
        get_string('credentialfiledesc', 'local_xentreeconnect'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/webhooksecretprevfile',
        get_string('webhooksecretprevfile', 'local_xentreeconnect'),
        get_string('credentialfiledesc', 'local_xentreeconnect'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_heading(
        'local_xentreeconnect/buttonheader',
        get_string('buttonheader', 'local_xentreeconnect'),
        get_string('buttonheaderdesc', 'local_xentreeconnect', (new moodle_url('/local/xentreeconnect/launch.php'))->out(false))
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/buttontext',
        get_string('buttontext', 'local_xentreeconnect'),
        get_string('buttontextdesc', 'local_xentreeconnect'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configmulticheckbox(
        'local_xentreeconnect/placements',
        get_string('placements', 'local_xentreeconnect'),
        get_string('placementsdesc', 'local_xentreeconnect'),
        [],
        [
            'dashboard' => get_string('placement_dashboard', 'local_xentreeconnect'),
            'course' => get_string('placement_course', 'local_xentreeconnect'),
            'login' => get_string('placement_login', 'local_xentreeconnect'),
        ]
    ));

    $settings->add(new admin_setting_heading(
        'local_xentreeconnect/dataheader',
        get_string('dataheader', 'local_xentreeconnect'),
        get_string('dataheaderdesc', 'local_xentreeconnect')
    ));

    $settings->add(new \local_xentreeconnect\admin_setting_retentiondays(
        'local_xentreeconnect/retentiondays',
        get_string('retentiondays', 'local_xentreeconnect'),
        get_string('retentiondaysdesc', 'local_xentreeconnect'),
        (string) \local_xentreeconnect\retention::DEFAULT_DAYS
    ));

    $ADMIN->add('localplugins', $settings);
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_xentreeconnect_status',
        get_string('statuspage', 'local_xentreeconnect'),
        new moodle_url('/local/xentreeconnect/index.php'),
        'moodle/site:config'
    ));
}
