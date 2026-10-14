# Changelog

## 0.2.0 — receives learning activity from Xentree

- **Webhook receiver** (`webhook.php`): verifies Xentree's HMAC signature (`X-Xentree-Signature-V2`, 5-minute
  tolerance, current + previous secret during rotation), idempotent by delivery id and event id, maps
  `moodle-user-<id>` to the Moodle user, answers 2xx for anything that must not be retried.
- **Stored data**: `erpi.session.completed` events go to the plugin's own table `local_xentreeconnect_event`
  (event type, when, duration, status). Nothing is written to grades or course completion.
  `connect.consent.revoked` deletes that member's rows.
- **Retention**: nightly task deletes activity older than the configured days (default 365, `0` = keep).
  The default is interim, pending your own legal decision.
- **Privacy API**: complete provider (metadata, contexts, users in context, export, deletion).
- **Credentials**: the private key and webhook secrets can come from `config.php` or a file outside the web root;
  the database is the fallback and the status page warns about it.
- **Status page**: webhook URL, credential sources, 24-hour counters, warnings, and "Delete all Xentree data".
- Upgrade note: creates two tables. Run the Moodle upgrade after copying the plugin.
- Not changed: consent wording (that is Xentree's) and the Connect button / launch behaviour.

## 0.1.1

Launch assertion signed with `kid`, configuration validation, button placement, Privacy API declaration of the data
sent to Xentree.

## 0.1.0

First demo version.
