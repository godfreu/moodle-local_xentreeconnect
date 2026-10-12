<?php

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

    $ADMIN->add('localplugins', $settings);
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_xentreeconnect_status',
        get_string('statuspage', 'local_xentreeconnect'),
        new moodle_url('/local/xentreeconnect/index.php'),
        'moodle/site:config'
    ));
}
