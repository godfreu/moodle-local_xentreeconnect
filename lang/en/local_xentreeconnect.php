<?php

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Xentree Connect';
$string['statuspage'] = 'Xentree Connect status';
$string['settingsheader'] = 'Xentree Connect settings';
$string['settingsheaderdesc'] = 'Values must match the integration registered in Xentree. The <a href="{$a}">status page</a> shows what to register there.';
$string['baseurl'] = 'Xentree base URL';
$string['baseurldesc'] = 'Where learners are sent, for example https://my.xentree.ai or your organisation\'s custom domain. Must use HTTPS.';
$string['baseurlinvalid'] = 'Enter an absolute HTTPS URL without credentials, query string or fragment.';
$string['clientid'] = 'Client ID';
$string['clientiddesc'] = 'Client ID registered with Xentree for this Moodle instance.';
$string['issuer'] = 'Issuer';
$string['issuerdesc'] = 'Issuer registered in Xentree. Usually this site\'s address: {$a}';
$string['audience'] = 'Audience';
$string['audiencedesc'] = 'Audience value registered in Xentree. Typically xentree-connect.';
$string['organizationid'] = 'Organization ID';
$string['organizationiddesc'] = 'ID of the Xentree organization that owns this integration.';
$string['kid'] = 'Key ID (kid)';
$string['kiddesc'] = 'Identifier of the signing key. It is sent in the JWT header and must match the "kid" in the JWKS registered in Xentree.';
$string['privatekeypem'] = 'Private key (PEM)';
$string['privatekeypemdesc'] = 'RSA private key used to sign launch assertions. The matching public key (JWKS) is shown on the status page. Demo only: the key is stored unencrypted in the Moodle database.';
$string['privatekeyinvalid'] = 'The private key could not be read. Paste a PEM-encoded RSA private key.';
$string['privatekeynotrsa'] = 'Only RSA private keys are supported.';
$string['problem_missing'] = 'not set';
$string['problem_invalid'] = 'invalid';
$string['statusincomplete'] = 'Xentree Connect is not ready. Fix the following settings:';
$string['statusready'] = 'Xentree Connect is configured.';
$string['opensettings'] = 'Open settings';
$string['registerheading'] = 'Register this site in Xentree';
$string['registerintro'] = 'Create a Connect integration in Xentree (organization settings) with these values and the public JWKS below.';
$string['publicjwks'] = 'Public JWKS';
