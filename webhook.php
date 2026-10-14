<?php
/**
 * Receives webhooks from Xentree (learning activity and consent withdrawals).
 *
 * Public on purpose: there is no Moodle login or session here. Every request is authenticated by its HMAC
 * signature; see classes/webhook/receiver.php. Register this URL in Xentree.
 *
 * @package    local_xentreeconnect
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__ . '/../../config.php');

$credentials = \local_xentreeconnect\credentials::from_site_config();
$receiver = new \local_xentreeconnect\webhook\receiver(
    new \local_xentreeconnect\moodle_store(),
    [$credentials->value('webhooksecret'), $credentials->value('webhooksecretprev')],
    null,
    static function (string $outcome, string $eventtype, string $deliveryid): void {
        error_log("local_xentreeconnect webhook {$outcome} type={$eventtype} delivery={$deliveryid}");
    }
);

try {
    $response = $receiver->handle(
        (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
        \local_xentreeconnect\webhook\receiver::headers_from_server($_SERVER),
        static function () {
            $stream = fopen('php://input', 'rb');
            $body = \local_xentreeconnect\webhook\receiver::read_limited($stream);
            fclose($stream);
            return $body;
        }
    );
} catch (\Throwable $e) {
    // A database problem is worth a retry; say nothing about it.
    error_log('local_xentreeconnect webhook failure: ' . get_class($e));
    $response = ['status' => 503, 'body' => ['error' => 'unavailable'], 'headers' => []];
}

http_response_code($response['status']);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
foreach ($response['headers'] as $name => $value) {
    header($name . ': ' . $value);
}
echo json_encode($response['body']);
