<?php
/**
 * Demo launch page for Xentree Connect.
 *
 * This script is intentionally lightweight. It creates a signed JWT assertion,
 * posts it to Xentree, and shows the redirect target for demo purposes.
 */

define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../../config.php');

require_login();

$baseurl = get_config('local_xentreeconnect', 'baseurl');
$clientid = get_config('local_xentreeconnect', 'clientid');
$issuer = get_config('local_xentreeconnect', 'issuer');
$audience = get_config('local_xentreeconnect', 'audience');
$organizationid = get_config('local_xentreeconnect', 'organizationid');
$privatekeypem = get_config('local_xentreeconnect', 'privatekeypem');

if (empty($baseurl) || empty($clientid) || empty($issuer) || empty($audience) || empty($organizationid) || empty($privatekeypem)) {
    throw new moodle_exception('Plugin not configured. Fill in Xentree Connect settings first.', 'local_xentreeconnect');
}

$now = time();
$subject = 'moodle-user-' . $USER->id;
$email = $USER->email ?: $USER->username . '@example.com';
$jti = 'moodle-' . bin2hex(random_bytes(16));

$payload = [
    'iss' => $issuer,
    'aud' => $audience,
    'sub' => $subject,
    'email' => $email,
    'organization_id' => $organizationid,
    'iat' => $now,
    'exp' => $now + 300,
    'jti' => $jti,
];

$header = ['alg' => 'RS256', 'typ' => 'JWT'];

$encodedheader = rtrim(strtr(base64_encode(json_encode($header, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
$encodedpayload = rtrim(strtr(base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
$signinginput = $encodedheader . '.' . $encodedpayload;

$privatekey = openssl_pkey_get_private($privatekeypem);
if ($privatekey === false) {
    throw new moodle_exception('Private key is invalid or unreadable. Check the plugin setting.', 'local_xentreeconnect');
}

$signature = '';
if (!openssl_sign($signinginput, $signature, $privatekey, OPENSSL_ALGO_SHA256)) {
    throw new moodle_exception('Unable to sign the JWT assertion.', 'local_xentreeconnect');
}

$assertion = $signinginput . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

$target = rtrim($baseurl, '/') . '/connect/v1/launch/' . rawurlencode($clientid);
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $target,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['assertion' => $assertion], '', '&'),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => false,
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$location = null;
if (preg_match('/Location:\s*(.*)/i', $headers, $matches)) {
    $location = trim($matches[1]);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_xentreeconnect'));
$PAGE->set_heading(get_string('pluginname', 'local_xentreeconnect'));

echo $OUTPUT->header();

echo '<div class="box" style="max-width: 900px; margin: 2rem auto;">';

echo '<h2>' . get_string('launchdemo', 'local_xentreeconnect') . '</h2>';

echo '<p><strong>Subject:</strong> ' . s($subject) . '</p>';

echo '<p><strong>Target:</strong> ' . s($target) . '</p>';

echo '<p><strong>HTTP code:</strong> ' . s((string) $httpcode) . '</p>';

if (!empty($location)) {
    echo '<p><strong>Redirect target:</strong> <a href="' . s($location) . '">' . s($location) . '</a></p>';
} else {
    echo '<pre>' . s($body) . '</pre>';
}


echo '<p><a class="btn btn-primary" href="' . s($location ?: $target) . '">Proceed to Xentree</a></p>';

echo '</div>';


echo $OUTPUT->footer();
