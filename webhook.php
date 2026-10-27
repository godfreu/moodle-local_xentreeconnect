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
 * Receives webhooks from Xentree (learning activity and consent withdrawals).
 *
 * Public on purpose: there is no Moodle login or session here. Every request is authenticated by its HMAC
 * signature; see classes/webhook/receiver.php. Register this URL in Xentree.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 G. E. Bayu Alfian <79591609+godfreu@users.noreply.github.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
