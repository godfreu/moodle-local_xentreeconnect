<?php
// Pure PHP (no Moodle API): the whole decision logic of the webhook endpoint, testable without Moodle.

namespace local_xentreeconnect\webhook;

/**
 * Verifies and applies a Xentree webhook delivery.
 *
 * Contract (docs/connect_partner_webhook_payload.md in the Xentree repository): POST JSON, signed with
 * X-Xentree-Signature-V2 = "t=<unix>,v1=<hex hmac-sha256 of "<t>.<raw body>">[,v1=<second secret>]", a delivery id in
 * X-Xentree-Delivery, timestamps within 5 minutes, answers 2xx for anything that must not be retried.
 *
 * Nothing is parsed or stored before the signature has been verified.
 *
 * @package    local_xentreeconnect
 */
final class receiver {
    /** Largest body Xentree sends (256 KiB). */
    public const MAX_BODY = 262144;

    /** Allowed clock difference in seconds between Xentree's timestamp and ours. */
    public const TOLERANCE = 300;

    public const SUBJECT_PATTERN = '/^moodle-user-([1-9][0-9]{0,9})$/D';

    private const ID_PATTERN = '/^[A-Za-z0-9_-]{1,64}$/D';
    private const DELIVERY_PATTERN = '/^[A-Za-z0-9_-]{8,64}$/D';
    private const TIME_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})$/D';
    private const REASONS = ['user', 'admin', 'deprovision'];

    /** Outcomes that count as "rejected" on the status page. */
    public const REJECTIONS = ['bad_signature', 'stale_timestamp', 'bad_payload', 'too_large'];

    private store $store;
    /** @var string[] Secrets that may have signed the delivery (current first, previous during rotation). */
    private array $secrets;
    /** @var callable */
    private $clock;
    /** @var callable|null */
    private $logger;

    /**
     * @param store $store
     * @param string[] $secrets Non-empty secrets; accepting a signature from any of them supports rotation.
     * @param callable|null $clock Returns unix time.
     * @param callable|null $logger fn(string $outcome, string $eventtype, string $deliveryid): void, ids only.
     */
    public function __construct(store $store, array $secrets, ?callable $clock = null, ?callable $logger = null) {
        $this->store = $store;
        $this->secrets = array_values(array_filter($secrets, static fn($s) => is_string($s) && $s !== ''));
        $this->clock = $clock ?? 'time';
        $this->logger = $logger;
    }

    /**
     * Lower-cased HTTP headers from a PHP $_SERVER array.
     *
     * @return array<string,string>
     */
    public static function headers_from_server(array $server): array {
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (strpos($key, 'HTTP_') === 0) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            } else if ($key === 'CONTENT_LENGTH' || $key === 'CONTENT_TYPE') {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Reads at most MAX_BODY bytes from a stream.
     *
     * @param resource $stream
     * @return string|null The body, or null when it is larger than allowed.
     */
    public static function read_limited($stream): ?string {
        $body = stream_get_contents($stream, self::MAX_BODY + 1);
        if ($body === false || strlen($body) > self::MAX_BODY) {
            return null;
        }
        return $body;
    }

    /**
     * @param string $method HTTP method.
     * @param array<string,string> $headers Lower-cased header names.
     * @param callable $readbody fn(): ?string returning the body, null when it exceeds MAX_BODY. Called only after
     *        the cheap checks, so an oversized or misdirected request is never read.
     * @return array{status:int,body:array,headers:array} What to send back.
     */
    public function handle(string $method, array $headers, callable $readbody): array {
        if (strtoupper($method) !== 'POST') {
            return $this->reply(405, 'method_not_allowed', ['Allow' => 'POST']);
        }
        if ($this->secrets === []) {
            return $this->reject(503, 'not_configured', '', '');
        }
        $deliveryid = (string) ($headers['x-xentree-delivery'] ?? '');
        $declared = $headers['content-length'] ?? null;
        if ($declared !== null && ctype_digit((string) $declared) && (int) $declared > self::MAX_BODY) {
            return $this->reject(413, 'too_large', '', '');
        }
        $body = $readbody();
        if ($body === null) {
            return $this->reject(413, 'too_large', '', '');
        }

        // 1. Authenticate before looking at anything the sender controls.
        $now = (int) ($this->clock)();
        $signature = $this->parse_signature((string) ($headers['x-xentree-signature-v2'] ?? ''));
        if ($signature === null) {
            return $this->reject(401, 'bad_signature', '', $deliveryid);
        }
        if (abs($now - $signature['t']) > self::TOLERANCE) {
            return $this->reject(401, 'stale_timestamp', '', $deliveryid);
        }
        if (!$this->signature_matches($signature, $body)) {
            return $this->reject(401, 'bad_signature', '', $deliveryid);
        }

        // 2. Only now is the payload trusted to be from Xentree; still validate it strictly.
        if (preg_match(self::DELIVERY_PATTERN, $deliveryid) !== 1) {
            return $this->reject(400, 'bad_payload', '', '');
        }
        $payload = json_decode($body, true, 16);
        if (!is_array($payload) || array_is_list($payload)) {
            return $this->reject(400, 'bad_payload', '', $deliveryid);
        }
        $eventtype = $payload['event_type'] ?? null;
        $headertype = (string) ($headers['x-xentree-event'] ?? '');
        if (!is_string($eventtype) || $eventtype === '' || strlen($eventtype) > 64 ||
                ($headertype !== '' && $headertype !== $eventtype)) {
            return $this->reject(400, 'bad_payload', '', $deliveryid);
        }
        $schema = $payload['schema_version'] ?? null;
        if (!is_int($schema) || $schema < 1) {
            return $this->reject(400, 'bad_payload', $eventtype, $deliveryid);
        }
        if ($schema > 1) {
            // A newer layout than this plugin understands: acknowledge, so Xentree does not retry for days.
            return $this->accept('unsupported_schema', $eventtype, $deliveryid, $now);
        }

        switch ($eventtype) {
            case 'erpi.session.completed':
                return $this->session_completed($payload, $eventtype, $deliveryid, $now);
            case 'connect.consent.revoked':
                return $this->consent_revoked($payload, $eventtype, $deliveryid, $now);
            case 'webhook.test.ping':
                return $this->accept('ping', $eventtype, $deliveryid, $now);
            default:
                return $this->accept('unknown_event', $this->clean($eventtype), $deliveryid, $now);
        }
    }

    private function session_completed(array $payload, string $eventtype, string $deliveryid, int $now): array {
        $eventid = $payload['event_id'] ?? null;
        $subject = $payload['external_subject'] ?? null;
        $occurred = $this->timestamp($payload['occurred_at'] ?? null);
        if (!is_string($eventid) || preg_match(self::ID_PATTERN, $eventid) !== 1 || !is_string($subject) || $occurred === null) {
            return $this->reject(400, 'bad_payload', $eventtype, $deliveryid);
        }
        $userid = self::user_from_subject($subject);
        if ($userid === null || !$this->store->user_exists($userid)) {
            return $this->accept('unknown_subject', $eventtype, $deliveryid, $now);
        }
        $seconds = $payload['session_duration_seconds'] ?? null;
        $status = $payload['session_status'] ?? null;
        $stored = $this->store->insert_event([
            'deliveryid' => $deliveryid,
            'eventid' => $eventid,
            'eventtype' => $eventtype,
            'userid' => $userid,
            'occurredat' => $occurred,
            'receivedat' => $now,
            'sessionseconds' => (is_int($seconds) && $seconds >= 0 && $seconds <= 2147483647) ? $seconds : null,
            'sessionstatus' => (is_string($status) && preg_match('/^[A-Za-z0-9_.-]{1,32}$/D', $status) === 1) ? $status : null,
        ]);
        return $this->accept($stored ? 'accepted' : 'duplicate', $eventtype, $deliveryid, $now);
    }

    private function consent_revoked(array $payload, string $eventtype, string $deliveryid, int $now): array {
        $subject = $payload['external_subject'] ?? null;
        $reason = $payload['reason'] ?? null;
        if (!is_string($subject) || !is_string($payload['event_id'] ?? null) || $this->timestamp($payload['occurred_at'] ?? null) === null
                || !in_array($reason, self::REASONS, true)) {
            return $this->reject(400, 'bad_payload', $eventtype, $deliveryid);
        }
        $userid = self::user_from_subject($subject);
        if ($userid === null) {
            return $this->accept('unknown_subject', $eventtype, $deliveryid, $now);
        }
        // Idempotent: a repeated notice just finds nothing left. Works for deleted users too.
        $this->store->delete_user_events($userid);
        return $this->accept('revoked', $eventtype, $deliveryid, $now);
    }

    /**
     * @return int|null The Moodle user id of "moodle-user-<id>", or null for any other shape.
     */
    public static function user_from_subject(string $subject): ?int {
        if (preg_match(self::SUBJECT_PATTERN, $subject, $m) !== 1 || (int) $m[1] > 2147483647) {
            return null;
        }
        return (int) $m[1];
    }

    private function timestamp($value): ?int {
        if (!is_string($value) || preg_match(self::TIME_PATTERN, $value) !== 1) {
            return null;
        }
        try {
            return (new \DateTimeImmutable($value))->getTimestamp();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array{t:int,v1:string[]}|null
     */
    private function parse_signature(string $header): ?array {
        $t = null;
        $candidates = [];
        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                return null;
            }
            if ($pair[0] === 't' && $t === null) {
                if (preg_match('/^\d{1,12}$/D', $pair[1]) !== 1) {
                    return null;
                }
                $t = (int) $pair[1];
            } else if ($pair[0] === 'v1') {
                $candidates[] = $pair[1];
            }
        }
        return ($t === null || $candidates === []) ? null : ['t' => $t, 'v1' => $candidates];
    }

    private function signature_matches(array $signature, string $body): bool {
        $matched = false;
        foreach ($this->secrets as $secret) {
            $expected = hash_hmac('sha256', $signature['t'] . '.' . $body, $secret);
            // Compare against every candidate so timing does not reveal which one matched.
            foreach ($signature['v1'] as $candidate) {
                $matched = hash_equals($expected, strtolower($candidate)) || $matched;
            }
        }
        return $matched;
    }

    private function accept(string $outcome, string $eventtype, string $deliveryid, int $now): array {
        $this->store->count_outcome($outcome, $now);
        if (!in_array($outcome, ['accepted', 'revoked', 'ping'], true)) {
            $this->log($outcome, $eventtype, $deliveryid);
        }
        return $this->reply(200, $outcome === 'duplicate' ? 'duplicate' : 'ok');
    }

    private function reject(int $status, string $outcome, string $eventtype, string $deliveryid): array {
        $this->store->count_outcome($outcome, (int) ($this->clock)());
        $this->log($outcome, $eventtype, $deliveryid);
        return $this->reply($status, $outcome);
    }

    private function reply(int $status, string $code, array $headers = []): array {
        // Short machine codes only: nothing about the secret, the payload or the site.
        return ['status' => $status, 'body' => $status < 300 ? ['status' => $code] : ['error' => $code], 'headers' => $headers];
    }

    private function log(string $outcome, string $eventtype, string $deliveryid): void {
        if ($this->logger !== null) {
            ($this->logger)($outcome, $this->clean($eventtype), $this->clean($deliveryid));
        }
    }

    /** Ids and event names only, whatever the sender put in them. */
    private function clean(string $value): string {
        return substr(preg_replace('/[^A-Za-z0-9._-]/', '', $value), 0, 64);
    }
}
