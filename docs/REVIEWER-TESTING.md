# Testing Xentree Connect (for reviewers)

This plugin connects Moodle to **Xentree**, an external service (see
[External service requirements](../README.md#external-service-requirements)). The steps below let you check the plugin
in three stages: without any Xentree account, with the review credentials, and the learning-activity flow.

The credentials for stage 2 and 3 are sent through the review ticket (MMR-518). **They are never stored in this
repository.**

## 1. Without credentials

You need a Moodle 4.4 or later site and an administrator account.

1. Copy the plugin to `local/xentreeconnect` (`public/local/xentreeconnect` on Moodle 5.1+) and run the upgrade.
   Expected: two tables are created (`local_xentreeconnect_event`, `local_xentreeconnect_stat`) and nothing else changes.
2. **Site administration → Plugins → Local plugins → Xentree Connect.** The page starts with the external service
   disclosure (what is sent, what is needed, link to the README requirements).
3. **Site administration → Plugins → Local plugins → Xentree Connect status.** Expected: a warning that lists the
   missing settings, the webhook URL, the credential sources, the counters (all zero) and a **Delete all Xentree data**
   button. No private key or secret is ever shown.
4. Select a placement (Dashboard, Course pages or Login page) in the settings, then open that page.
   - As an administrator: a notice that the plugin is not configured, linking to the status page.
   - As an ordinary user (or logged out): **no button at all**.
5. Open `/local/xentreeconnect/launch.php` as an ordinary user. Expected: an error message that the connection is
   unavailable. As an administrator the message names what is missing. Nothing is sent to Xentree.
6. The webhook endpoint accepts only correctly signed requests. With any HTTP client (replace the host):

   | Request | Expected response |
   |---|---|
   | `GET /local/xentreeconnect/webhook.php` | `405 {"error":"method_not_allowed"}` |
   | `POST` while no webhook secret is configured | `503 {"error":"not_configured"}` |
   | `POST` without a valid signature, with a secret configured | `401 {"error":"bad_signature"}` |

7. Run the unit tests (no Moodle needed): from the plugin folder,
   `phpunit --bootstrap tests/standalone/bootstrap.php --test-suffix _test.php tests`. With PHPUnit 13 one data-provider
   error in `receiver_test` and a "provider_test cannot be found" warning are expected (that file needs Moodle). Inside
   Moodle, run the plugin's tests with Moodle's own PHPUnit setup.

## 2. With the review credentials (launch)

The ticket contains: the **Xentree base URL**, **Client ID**, **Issuer**, **Audience**, **Organization ID**, **key ID
(kid)**, the **private key** (PEM) and the **webhook signing secret**.

1. In the plugin settings, fill in the values from the ticket. Prefer the file option for the key and the secret
   (outside the web root); pasting them into the settings page also works, and the status page warns about it.
2. Open the status page. Expected: **ready**, and the public JWKS derived from the key. The Issuer must be this
   site's address (`https://<your-moodle-site>`) as registered in the review integration. The review site and its test
   access are provided separately through the Marketplace ticket.
3. Press **Test launch** (or open `/local/xentreeconnect/launch.php`). Expected: the browser is posted to
   `{base URL}/connect/v1/launch/{Client ID}` and Xentree shows its consent screen for the Moodle user.
   Without JavaScript the same page shows a **Continue to Xentree** button.
4. Accept the consent. Expected: you arrive in Xentree signed in as the linked member.
   The Xentree account and organisation for the review are provided separately through the Marketplace ticket.

## 3. Learning-activity events

Xentree sends `erpi.session.completed` when a member finishes a learning session, only for members who accepted
the consent text version 2 or later, and `connect.consent.revoked` when a member withdraws.
[VERIFIKASI PRODUCTION: status ketersediaan event ini di production]

The webhook's event list in Xentree offers only these two events. A test delivery (`webhook.test.ping`) is sent with the **Send Test Ping** button of the webhook in Xentree; it is not part of the event list, and the status page counts it as `ping`.

**3a. Through Xentree.** [ISI: langkah menyelesaikan satu sesi belajar di Xentree dengan akun reviewer, dan di mana
mendaftarkan/menguji webhook ke URL situs reviewer]. Then open the status page: the counters for the last 24 hours show
the delivery, and the member's row appears in the privacy export
(**Site administration → Users → Privacy and policies → Data requests**).

**3b. Without waiting for Xentree.** Your Moodle site must be reachable over HTTPS from the machine running the
command. Sign a test delivery yourself with the webhook secret from the ticket. A completed session for a Moodle user
(user ID 2 is the default administrator; use any existing user ID):

```bash
SECRET='<webhook secret from the ticket>'
URL='https://<your-moodle>/local/xentreeconnect/webhook.php'
T=$(date +%s)
BODY='{"schema_version":1,"event_id":"evt_review2","event_type":"erpi.session.completed","occurred_at":"2026-11-12T00:00:00Z","external_subject":"moodle-user-2","session_duration_seconds":600,"session_status":"completed"}'
SIG=$(printf '%s.%s' "$T" "$BODY" | openssl dgst -sha256 -hmac "$SECRET" -hex | awk '{print $NF}')
curl -i -X POST "$URL" \
  -H 'Content-Type: application/json' \
  -H 'X-Xentree-Delivery: review-0002' \
  -H 'X-Xentree-Event: erpi.session.completed' \
  -H "X-Xentree-Signature-V2: t=$T,v1=$SIG" \
  --data "$BODY"
```

Expected: `200 {"status":"ok"}`; the status page counts one accepted event and the data export of that user lists one
session. Sending the same command again (same event ID) answers `200 {"status":"duplicate"}` and stores nothing.
Use a new `event_id` and `X-Xentree-Delivery` value for each new delivery, and run it within 5 minutes of computing the
signature: older timestamps are rejected with `401 {"error":"stale_timestamp"}`.

Withdrawal of consent, which deletes that member's stored rows (use the same pattern, new ids):

```bash
BODY='{"schema_version":1,"event_id":"evt_review3","event_type":"connect.consent.revoked","occurred_at":"2026-11-12T00:00:00Z","external_subject":"moodle-user-2","reason":"user"}'
```

Sign and send it as above, with `X-Xentree-Event: connect.consent.revoked` and a new delivery id. Expected: `200`, and
the user's rows are gone. **Delete all Xentree data** on the status page removes every stored row.
