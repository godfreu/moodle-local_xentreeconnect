# Changelog

## 0.2.2 — Marketplace review fixes

- **Status page fix:** since 0.2.0 the receiver report (webhook URL, credential sources, counters, retention) and the
  "Delete all Xentree data" button sat inside the delete-confirmation branch of `index.php` and could not be reached.
  They are now on the normal status page; the confirmation branch only confirms and deletes.
- **Templates, Output API and AMD:** the launch form and the status page are rendered with Mustache templates and
  renderables; the auto-submit of the launch form moved to the AMD module `amd/src/launch.js`.
- **External service disclosure:** the README and the plugin settings state that Xentree is an external third-party
  service, what is required (organisation, Connect entitlement, integration), how to get it, cost and account
  requirements, and what data is exchanged. Reviewer testing guide in `docs/REVIEWER-TESTING.md`.
- **CI:** GitHub Actions with moodle-plugin-ci on Moodle 4.4, 4.5 and 5.0.
- **Privacy API:** the metadata now also declares `deliveryid` and `eventid` of the stored activity.
- **Button placement:** on pages that use Boost's drawers layout (dashboard, course) the automatically placed button sat
  behind the fixed navbar. A small `styles.css` now pushes it below the navbar. Pages without that layout, such as the
  login page, are not affected. From the lg breakpoint (992px) the button also moves clear of an open course index or
  block drawer (this part uses `:has()`; a browser without it only skips those two rules). Moodle adds plugin
  stylesheets to the theme CSS, so the theme cache is rebuilt when the site is upgraded to this build.
- **Header item:** a "Learn with AI" item in the primary navigation (the header) that opens the Xentree launch for
  logged-in users (not guests) once the plugin is configured. It is a new choice, "Header (primary navigation)", in
  the placements setting and the default for new installs; the text can be changed in the new "Menu item text"
  setting. Sites that already have the plugin keep their saved placements, and every box may stay empty (no item and
  no button, for example when you link to `launch.php` from your own menu).
- **Settings help and secrets:** the help text of the credential file settings names the right item, and the help text of
  the webhook secret now says plainly that a value saved on the settings page is stored as plain text in the database
  (masked, but an administrator can reveal it). README "Before production" says the same; `config.php` or a file outside
  the web root is preferred. Encrypting the stored value is planned for 0.3.0.
- **Receiver:** extra optional fields in an event are ignored and never stored; a ping without `external_subject` and an
  event without the optional fields are accepted; new tests cover these and unknown event types (answered 2xx).
- **Learning-activity events:** the README and the reviewer guide describe when Xentree sends `erpi.session.completed`
  (members who agreed to share their activity, sessions of about one minute or longer, a webhook that subscribes to it)
  and how to verify the receiver with the test ping and signed requests. A session deleted in Xentree
  (`session.deleted`) is acknowledged but its stored row stays until retention; handling it is planned for 0.3.0, see
  https://github.com/godfreu/moodle-local_xentreeconnect/issues/5. Deep links to specific Xentree material are discussed there too.
- **Logging:** webhook outcomes are logged through `debugging()` instead of `error_log()`; they reach the server log
  only while debugging is turned on. The status page counters are unaffected.
- Moodle coding style applied (no behaviour change).

## 0.2.1 — repository rename

- Documentation only: the repository is now `godfreu/moodle-local_xentreeconnect`; README links updated.
- Test fixtures use example domains only. No code changes.

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
