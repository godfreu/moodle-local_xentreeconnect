<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_xentreeconnect', get_string('pluginname', 'local_xentreeconnect'));

    $settings->add(new admin_setting_heading(
        'local_xentreeconnect/header',
        get_string('settingsheader', 'local_xentreeconnect'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_xentreeconnect/baseurl',
        get_string('baseurl', 'local_xentreeconnect'),
        get_string('baseurldesc', 'local_xentreeconnect'),
        'https://app.xentree.ai',
        PARAM_URL
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
        get_string('issuerdesc', 'local_xentreeconnect'),
        'https://moodle.example.com',
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

    $settings->add(new admin_setting_configtextarea(
        'local_xentreeconnect/privatekeypem',
        get_string('privatekeypem', 'local_xentreeconnect'),
        get_string('privatekeypemdesc', 'local_xentreeconnect'),
        "-----BEGIN PRIVATE KEY-----\n\n-----END PRIVATE KEY-----\n",
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_xentreeconnect/jwksjson',
        get_string('jwksjson', 'local_xentreeconnect'),
        get_string('jwksjsondesc', 'local_xentreeconnect'),
        '{"keys": []}',
        PARAM_RAW
    ));

    $ADMIN->add('localplugins', $settings);
}
