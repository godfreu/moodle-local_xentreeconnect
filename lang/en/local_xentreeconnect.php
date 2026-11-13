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
 * English language strings for local_xentreeconnect.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['audience'] = 'Audience';
$string['audiencedesc'] = 'Audience value registered in Xentree. Typically xentree-connect.';
$string['baseurl'] = 'Xentree base URL';
$string['baseurldesc'] = 'Where learners are sent, for example https://my.xentree.ai or your organisation\'s custom domain. Must use HTTPS.';
$string['baseurlinvalid'] = 'Enter an absolute HTTPS URL without credentials, query string or fragment.';
$string['buttonheader'] = 'Connect button';
$string['buttonheaderdesc'] = 'Choose where the button appears automatically. To place it anywhere else (a menu item, custom HTML, a theme), link to <code>{$a}</code>.';
$string['buttonnotconfigured'] = 'The Xentree Connect button is enabled but the plugin is not fully configured, so users do not see it.';
$string['buttontext'] = 'Button text';
$string['buttontextdesc'] = 'Leave empty to use the default, which follows the user\'s language.';
$string['clientid'] = 'Client ID';
$string['clientiddesc'] = 'Client ID registered with Xentree for this Moodle instance.';
$string['continuetoxentree'] = 'Continue to Xentree';
$string['credential_privatekeypem'] = 'Private key (launch signing)';
$string['credential_webhooksecret'] = 'Webhook secret';
$string['credential_webhooksecretprev'] = 'Previous webhook secret';
$string['credentialfiledesc'] = 'Absolute path of a file <strong>outside the web root</strong> that contains the value. Preferred over saving it in the database. A value forced in config.php wins over the file, and the file wins over the database. Example for config.php: <code>$CFG->forced_plugin_settings[\'local_xentreeconnect\'] = [\'privatekeypem\' => file_get_contents(\'/etc/moodle/xentree.pem\')];</code>';
$string['credentialnotset'] = 'not set';
$string['credentialset'] = 'set';
$string['credentialsource'] = '{$a->name}: {$a->state} (source: {$a->source})';
$string['credentialstatus'] = 'Credentials';
$string['dataheader'] = 'Learning activity from Xentree';
$string['dataheaderdesc'] = 'When Xentree sends learning activity of members who agreed to share it, it is stored in a plugin table only. It is never written to grades or course completion.';
$string['deleteallconfirm'] = 'Delete all learning activity received from Xentree? This cannot be undone.';
$string['deletealldata'] = 'Delete all Xentree data';
$string['deletealldatadesc'] = 'Removes every activity record received from Xentree. Use it when the integration is switched off and withdrawal notices will no longer arrive.';
$string['deletealldone'] = 'Deleted {$a} activity record(s).';
$string['events24h'] = 'Activity records received';
$string['externalserviceheader'] = 'External service: Xentree';
$string['externalservicenotice'] = 'Xentree is an external third-party service, and this plugin does not work without a Xentree organisation that has a Connect integration for this site. The organisation needs the Connect entitlement and an integration registered there for this site (Client ID, Issuer, Audience, Organization ID, key ID and the public JWKS of your key). When a user starts the Connect flow, the plugin sends their Moodle user ID and, if valid, their e-mail address to Xentree. If you register a webhook, Xentree sends learning-activity events and consent withdrawals for members who agreed to share them, and this site stores them as described under Privacy. How to get access, cost and account requirements: <a href="{$a}" target="_blank" rel="noopener">External service requirements</a> in the plugin README.';
$string['filereason_empty'] = 'it is empty';
$string['filereason_insidewebroot'] = 'it is inside the web root';
$string['filereason_unreadable'] = 'it cannot be read';
$string['issuer'] = 'Issuer';
$string['issuerdesc'] = 'Issuer registered in Xentree. Usually this site\'s address: {$a}';
$string['kid'] = 'Key ID (kid)';
$string['kiddesc'] = 'Identifier of the signing key. It is sent in the JWT header and must match the "kid" in the JWKS registered in Xentree.';
$string['last24h'] = 'Last 24 hours';
$string['launchbutton'] = 'Connect with Xentree';
$string['launchunavailable'] = 'Xentree Connect is not available right now. Please contact your administrator.';
$string['opensettings'] = 'Open settings';
$string['organizationid'] = 'Organization ID';
$string['organizationiddesc'] = 'ID of the Xentree organization that owns this integration.';
$string['outcomedetail'] = 'All outcomes';
$string['placement_course'] = 'Course pages';
$string['placement_dashboard'] = 'Dashboard';
$string['placement_login'] = 'Login page';
$string['placements'] = 'Show the button on';
$string['placementsdesc'] = 'Pages where the button is added automatically. Nothing is shown anywhere until you pick at least one.';
$string['pluginname'] = 'Xentree Connect';
$string['privacy:metadata:event'] = 'Learning activity received from Xentree for members who agreed to share it. One row per completed learning session. It is kept in this plugin\'s own table only and is never written to grades or course completion.';
$string['privacy:metadata:event:deliveryid'] = 'The identifier of the delivery in which Xentree sent the activity, used to store each delivery once.';
$string['privacy:metadata:event:eventid'] = 'The identifier Xentree gave the event, used to store each event once.';
$string['privacy:metadata:event:eventtype'] = 'The kind of activity, for example a completed learning session.';
$string['privacy:metadata:event:occurredat'] = 'When Xentree recorded the activity.';
$string['privacy:metadata:event:receivedat'] = 'When this site received it.';
$string['privacy:metadata:event:sessionseconds'] = 'How long the learning session lasted, in seconds.';
$string['privacy:metadata:event:sessionstatus'] = 'The status of the learning session when it ended.';
$string['privacy:metadata:event:userid'] = 'The Moodle user the activity belongs to.';
$string['privacy:metadata:xentree'] = 'When a user starts the Connect flow (for example with the "Connect with Xentree" button), this plugin sends the data below to Xentree, an external learning service.';
$string['privacy:metadata:xentree:email'] = 'The e-mail address of the user, sent only when it is a valid address.';
$string['privacy:metadata:xentree:userid'] = 'The Moodle user ID, sent as the identifier "moodle-user-<ID>".';
$string['privacy:path:activity'] = 'Xentree learning activity';
$string['privatekeyfile'] = 'Private key file (optional)';
$string['privatekeyinvalid'] = 'The private key could not be read. Paste a PEM-encoded RSA private key.';
$string['privatekeynotrsa'] = 'Only RSA private keys are supported.';
$string['privatekeypem'] = 'Private key (PEM)';
$string['privatekeypemdesc'] = 'RSA private key used to sign launch assertions. The matching public key (JWKS) is shown on the status page. Demo only: the key is stored unencrypted in the Moodle database.';
$string['problem_invalid'] = 'invalid';
$string['problem_missing'] = 'not set';
$string['publicjwks'] = 'Public JWKS';
$string['receiverurl'] = 'Webhook URL to register in Xentree';
$string['receiverurlhelp'] = 'Must be reachable from the internet over HTTPS on port 443 with a public domain name. Moodle sites reachable only from an intranet or by IP address are not supported.';
$string['redirecting'] = 'Taking you to Xentree…';
$string['registerheading'] = 'Register this site in Xentree';
$string['registerintro'] = 'Create a Connect integration in Xentree (organization settings) with these values and the public JWKS below.';
$string['rejected24h'] = 'Requests rejected (bad signature, stale time, bad payload, too large)';
$string['retentiondays'] = 'Keep activity for (days)';
$string['retentiondaysdesc'] = 'Activity older than this is deleted every night. 0 keeps it until you delete it yourself. Interim default of 365 days: confirm the retention period with your data-protection adviser.';
$string['retentiondaysinvalid'] = 'Enter a whole number of days, 0 or more.';
$string['retentionnow'] = 'Activity is kept for {$a} days.';
$string['settingsheader'] = 'Xentree Connect settings';
$string['settingsheaderdesc'] = 'Values must match the integration registered in Xentree. The <a href="{$a}">status page</a> shows what to register there.';
$string['source_configphp'] = 'config.php (forced setting)';
$string['source_database'] = 'Moodle database';
$string['source_file'] = 'file outside the web root';
$string['source_none'] = 'none';
$string['statusincomplete'] = 'Xentree Connect is not ready. Fix the following settings:';
$string['statuspage'] = 'Xentree Connect status';
$string['statusready'] = 'Xentree Connect is configured.';
$string['statusreceiving'] = 'Receiving from Xentree';
$string['task_purge'] = 'Delete old Xentree activity';
$string['testlaunch'] = 'Test launch';
$string['warning_filefailed'] = '{$a->name}: the configured file could not be used ({$a->reason}).';
$string['warning_keyindatabase'] = 'The private key is stored in the Moodle database. Move it to config.php or a file outside the web root.';
$string['warning_retentionzero'] = 'Automatic deletion is off (retention 0): activity from Xentree is kept until you delete it. Set a retention period after confirming it with your data-protection adviser.';
$string['warning_secretindatabase'] = 'The webhook secret is stored in the Moodle database. Prefer config.php or a file outside the web root.';
$string['warning_secretmissing'] = 'No webhook secret is set: Xentree deliveries are refused with HTTP 503 until you set it.';
$string['webhookheader'] = 'Receiving activity from Xentree';
$string['webhookheaderdesc'] = 'Register this address as the webhook URL in Xentree: <code>{$a}</code>. It must be reachable from the internet over HTTPS on port 443 with a public domain name, and use the same host name as this site\'s address.';
$string['webhooksecret'] = 'Webhook secret';
$string['webhooksecretdesc'] = 'The signing secret Xentree showed when the webhook was created. Not displayed again after saving.';
$string['webhooksecretfile'] = 'Webhook secret file (optional)';
$string['webhooksecretprev'] = 'Previous webhook secret (during rotation)';
$string['webhooksecretprevdesc'] = 'After rotating the secret in Xentree the old one stays valid there for 24 hours. Put it here so deliveries signed with either secret are accepted, then clear it.';
$string['webhooksecretprevfile'] = 'Previous webhook secret file (optional)';
