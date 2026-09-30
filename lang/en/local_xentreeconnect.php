<?php

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Xentree Connect';
$string['settingsheader'] = 'Xentree Connect settings';
$string['baseurl'] = 'Xentree base URL';
$string['baseurldesc'] = 'Base URL for the Xentree Connect endpoint, for example https://app.xentree.ai';
$string['clientid'] = 'Client ID';
$string['clientiddesc'] = 'Client ID registered with Xentree for this Moodle instance.';
$string['issuer'] = 'Issuer';
$string['issuerdesc'] = 'Issuer URL used in the signed launch assertion, for example https://moodle.example.com';
$string['audience'] = 'Audience';
$string['audiencedesc'] = 'Audience value required by Xentree. Typically xentree-connect.';
$string['organizationid'] = 'Organization ID';
$string['organizationiddesc'] = 'Organization ID in Xentree that owns the Moodle user membership.';
$string['privatekeypem'] = 'Private key PEM';
$string['privatekeypemdesc'] = 'Private key matching the public key in the JWKS sent to Xentree. Demo only.';
$string['jwksjson'] = 'JWKS JSON';
$string['jwksjsondesc'] = 'Public JWKS for the Moodle platform, as a JSON object with a "keys" array.';
$string['launchdemo'] = 'Launch Xentree Connect demo';
$string['launchdemodesc'] = 'Build a signed assertion and post it to the Xentree launch endpoint.';
$string['launchbutton'] = 'Connect with Xentree';
$string['settingssaved'] = 'Settings saved';
$string['manualtesting'] = 'Manual testing';
