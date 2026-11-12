# Xentree Connect for Moodle (local_xentreeconnect)

Beta plugin (0.2.1) that lets a Moodle site act as a trusted platform for Xentree Connect: it signs a short-lived
JWT launch assertion for the logged-in Moodle user and sends the learner to Xentree, and it can receive learning
activity back from Xentree for members who agreed to share it (see "Receiving activity from Xentree").

Requires Moodle 4.4 or later. Automated tests run on Moodle 4.4, 4.5 and 5.0.

## External service requirements

**Xentree is an external third-party service, and this plugin does not work without a Xentree organisation that has a
Connect integration for your Moodle site.** The plugin is only a connector to Xentree ([xentree.ai](https://www.xentree.ai));
Xentree is not part of the plugin and is not installed on your Moodle server.

| | |
|---|---|
| **Service** | Xentree, at the Xentree base URL set in the plugin (default `https://my.xentree.ai`, or your organisation's custom domain). |
| **What you need** | A Xentree **organisation** with the **Connect entitlement**, and a **Connect integration** registered for this Moodle site in that organisation: Client ID, Issuer, Audience, Organization ID, a key ID (`kid`) and the public JWKS of an RSA key pair you generate. Optionally a **webhook** with its signing secret, to receive learning activity. |
| **How to get access** | Self-service, without contacting Xentree. A user with the **Administrator** role in Xentree opens **Organizations** (<https://my.xentree.ai/admin/organizations>), clicks the **Connect Integrations** icon, then **+ Add Integration**, and uses either the interactive wizard (one command) or the manual form: Name, Client ID, Issuer, Audience, Policy URL (optional), Description (optional), the option "Use a seat automatically on first Connect", and the JWKS JSON. Which plan includes the Connect entitlement: [ISI: entitlement plan]. |
| **Cost** | Freemium. The **Basic** plan is free and **Pro** is for individuals; organisations use a monthly or yearly subscription on the **Team** or **Flexible** plan. Individuals do not pay to join an organisation. |
| **User accounts** | Every individual needs their own Xentree account. The Xentree account is linked to the Moodle account on the first launch, through a consent screen that the owner of the account accepts (Google sign-in or sign-up is available). Roles in Xentree: Member, Administrator, Team Manager, Billing Manager. What "Use a seat automatically on first Connect" does: [ISI: seat behaviour]. |
| **Privacy policy / terms** | [Privacy policy](https://www.xentree.ai/privacy) · [Terms of service](https://www.xentree.ai/terms) |

What the plugin sends to Xentree and receives from it is listed under [Data exchanged with Xentree](#data-exchanged-with-xentree).
Without a Xentree organisation, the plugin can be installed but the button stays hidden for users, the status page lists
what is missing, and no data leaves your site.

**Learning-activity events.** Xentree can send a `erpi.session.completed` event to this site when a member finishes a
learning session. [VERIFIKASI PRODUCTION: tersedia di production hanya bila sudah terbukti ter-deploy] It is sent only
when all of these are true: the organisation has the Connect entitlement, the session started from a Connect launch from
this site, a webhook with that event type is registered, and the member agreed to share activity by accepting the
consent text version 2 or later. Members who agreed earlier are asked again at their next launch and nothing is sent for
them until then. Withdrawal notices (`connect.consent.revoked`) are sent regardless.

## What an admin has to do

1. **Install** the plugin (below) and run the upgrade.
2. **Generate an RSA key pair** (outside Moodle) and choose a key ID (`kid`):
   ```bash
   openssl genrsa -out moodle-connect.pem 2048
   ```
3. **Fill the plugin settings** (Site administration → Plugins → Local plugins → Xentree Connect):

   | Setting | Value |
   |---|---|
   | Xentree base URL | `https://my.xentree.ai` or the organisation's custom domain (HTTPS only) |
   | Client ID | Must equal the Client ID registered in Xentree |
   | Issuer | Must equal the Issuer registered in Xentree (normally this site's address) |
   | Audience | Must equal the Audience registered in Xentree (default `xentree-connect`) |
   | Organization ID | The Xentree organization that owns the integration |
   | Key ID (kid) | Any identifier; must match the `kid` in the registered JWKS |
   | Private key (PEM) | Content of `moodle-connect.pem` |

4. **Open the status page** (Site administration → Plugins → Local plugins → Xentree Connect status). It lists any
   missing setting and prints the **public JWKS** derived from your private key.
5. **Register the site in Xentree** (organization settings → Connect integrations) with the same Client ID, Issuer,
   Audience and the JWKS from the status page. The organization needs the Connect entitlement.

## The Connect button

In the plugin settings choose where the button appears automatically: **Dashboard**, **Course pages**, **Login page**
(nothing is shown until at least one is selected). The button text and language follow the setting and the user's
language (EN/ID included). The Xentree base URL decides where learners go, so a custom domain just works.

To put the button anywhere else (a menu item, custom HTML, a theme), link to:

```
https://<your-moodle>/local/xentreeconnect/launch.php
```

`launch.php` signs the assertion for the logged-in user and sends the browser to Xentree with a form POST.
Logged-out visitors are asked to log in to Moodle first.

If the plugin is not fully configured, ordinary users never see the button; site admins see a notice that links to
the status page.

## Install

### From Git
Clone this repository into your Moodle installation under `local/xentreeconnect`:

```bash
git clone https://github.com/godfreu/moodle-local_xentreeconnect.git local/xentreeconnect
```

### From ZIP package
Extract the release ZIP archive into the Moodle `local/` directory so the plugin folder is `local/xentreeconnect` (or `public/local/xentreeconnect` on Moodle 5.1+).

Ensure permissions allow the web server to read the files, then run the upgrade:

```bash
php admin/cli/upgrade.php --non-interactive
```

## Launch contract

Moodle POSTs `assertion=<JWT>` (form-encoded) to `{baseurl}/connect/v1/launch/{client_id}`. Header: `alg=RS256`,
`typ=JWT`, `kid`. Claims: `iss`, `aud`, `sub` (`moodle-user-<id>`), `organization_id`, `iat`, `exp` (5 minutes),
`jti` (single use), and `email` when the user has a valid address. Xentree validates the claims and the signature
against the registered JWKS, then redirects the browser to its consent screen.

## Receiving activity from Xentree

Xentree sends two kinds of events to a webhook URL you register: `erpi.session.completed` (a member finished a
learning session) and `connect.consent.revoked` (a member withdrew consent, so their stored activity must go).
The headers and fields are listed under [Data exchanged with Xentree](#data-exchanged-with-xentree).

1. In the plugin settings, find **Receiving activity from Xentree**: it shows the URL
   (`https://<your-moodle>/local/xentreeconnect/webhook.php`). Register it as the webhook URL of your Connect client
   in Xentree (administrators of the organization, or the runbook, explain how).
2. Xentree shows the **signing secret once** when the webhook is created. Put it in **Webhook secret** (or, better, in
   `config.php` or a file; see below). When Xentree rotates the secret, put the old one in **Previous webhook secret**
   for the 24 hours both are valid, then clear it.
3. Open the status page and press Xentree's **test ping**: the counters on the page show it arrive.

Requirements for the URL: **HTTPS on port 443, a public domain name, the same host name as this site's address**
(Moodle redirects other host names, and Xentree treats a redirect as a failure). Moodle sites reachable only from an
intranet, or only by IP address, are not supported. If a reverse proxy or WAF is in front of Moodle, allow `POST`
from the internet to `webhook.php`, and rate-limit it there.

What happens to a delivery: the signature and a 5-minute timestamp window are checked before anything is read; the
body is capped at 256 KiB; the member is found from `moodle-user-<id>`; a repeated delivery (same delivery or event
id) is acknowledged and stored once; unknown events and users are acknowledged and counted, never retried. Replies
carry short codes only. Logs contain event types and delivery ids, never e-mail addresses or secrets.

Stored data: one row per completed session in `local_xentreeconnect_event` (event type, when it happened, when it
was received, duration, status). It is **not** written to grades or course completion. Activity is deleted after the
retention period (default 365 days, **an interim value**; `0` keeps it until you delete it, and the status page warns
about that). **Delete all Xentree data** on the status page removes everything, for example when the integration is
switched off and withdrawal notices will no longer arrive.

Xentree sends `erpi.session.completed` only for members who accepted the consent text version 2 or later (see
[External service requirements](#external-service-requirements)). Withdrawal notices are sent regardless.

## Tests

The launcher, receiver, retention, credential lookup and status logic are plain PHP, covered in `tests/*_test.php`.
Inside Moodle use its PHPUnit setup (`tests/privacy/provider_test.php` needs it: it exercises the real Privacy API).
Without a Moodle checkout, with a standalone PHPUnit:

```bash
phpunit --bootstrap tests/standalone/bootstrap.php --test-suffix _test.php tests
```

One runner warning ("Class provider_test cannot be found") is expected there: that file only runs inside Moodle.
`tests/privacy_logic_test.php` checks the provider's logic against stand-ins for the Privacy API, which does not
prove the real API signatures; run `tests/privacy/provider_test.php` in Moodle for that.

`tests/fixtures/backend_deliveries.json` holds deliveries produced by Xentree's real signing code, so the receiver
is checked against the bytes Xentree actually sends. Regenerate it with
`tests/fixtures/generate_backend_fixtures.py` (instructions at the top of the file) if Xentree changes its format.

## Before production

This plugin is a beta. Before using it with real learners:

- **Keep the private key and webhook secret out of the database.** The status page warns while they are stored there.
  In order of preference:
  1. `config.php` (forced setting, wins over everything):
     ```php
     $CFG->forced_plugin_settings['local_xentreeconnect'] = [
         'privatekeypem' => file_get_contents('/etc/moodle-secrets/xentree-connect.pem'),
         'webhooksecret' => trim(file_get_contents('/etc/moodle-secrets/xentree-webhook.secret')),
     ];
     ```
  2. A file outside the web root: set **Private key file** / **Webhook secret file** to its absolute path (readable by
     the web server user only, e.g. mode 0640). Files inside the web root are refused.
  3. The database (fallback): the secrets are masked in the settings page, but the private key field still shows the
     PEM to anyone who can open the settings, and both are in database backups.
- **Rotate keys deliberately.** Changing the launch key means registering the new JWKS in Xentree first, otherwise
  launches fail with HTTP 401. Use a new `kid` for each key.
- **Decide the retention period** with your data-protection adviser; 365 days is an interim default.
- **Review the consent wording** shown by Xentree with your data-protection adviser. Activity data flows to Moodle only
  after members accept consent text version 2 or later.
- Set a real policy link (`policy_url`) on the integration in Xentree; the consent screen otherwise says none was given.

## Data exchanged with Xentree

**Sent to Xentree** when a user starts the Connect flow (button or `launch.php`): a form POST of one field, `assertion`,
to `{base URL}/connect/v1/launch/{client ID}`. It is a JWT signed with your private key (RS256, header `kid`), valid for
5 minutes, and used once:

| Claim | Content |
|---|---|
| `iss`, `aud` | Issuer and Audience from the plugin settings |
| `sub` | `moodle-user-<Moodle user ID>` |
| `organization_id` | Xentree organization ID from the plugin settings |
| `iat`, `exp`, `jti` | Issue time, expiry (5 minutes later), unique id |
| `email` | The user's e-mail address, only when it is valid |

Nothing else is sent by the plugin. (The user's browser then talks to Xentree directly, under Xentree's own terms.)

**Received from Xentree** at `webhook.php`: `POST` with JSON, signed by Xentree with the webhook secret. Headers:
`X-Xentree-Signature-V2` (`t=<unix time>,v1=<HMAC-SHA256 hex of "<t>.<raw body>">`, one `v1` per valid secret),
`X-Xentree-Delivery` (delivery id) and `X-Xentree-Event` (event type). The body is at most 256 KiB and its fields are:

| Event | Fields |
|---|---|
| `erpi.session.completed` | `schema_version` (1), `event_id`, `event_type`, `occurred_at`, `external_subject` (`moodle-user-<ID>`), `session_duration_seconds`, `session_status` |
| `connect.consent.revoked` | `schema_version` (1), `event_id`, `event_type`, `occurred_at`, `external_subject`, `reason` (`user`, `admin` or `deprovision`) |
| `webhook.test.ping` | `schema_version` (1), `event_id`, `event_type`, `occurred_at`, `message`; counted, not stored |

Stored from `erpi.session.completed` only: delivery id, event id, event type, Moodle user, when it happened, when it was
received, duration and status. Unknown events, unknown users and newer schema versions are acknowledged, counted and
not stored.

## Privacy

Sent to Xentree when a user starts the Connect flow: the Moodle user ID (as `moodle-user-<id>`) and, if valid, the
e-mail address.

Stored in Moodle (from 0.2.0): the activity rows described above, linked to the Moodle user. Both flows are declared
through Moodle's Privacy API (`classes/privacy/provider.php`): the site's privacy registry lists them, and data
export and deletion requests cover the stored activity (it is also removed when Xentree reports that the member
withdrew consent, and by the retention task).

## Issues & Support

Report bugs, ask questions, or request features on GitHub:
https://github.com/godfreu/moodle-local_xentreeconnect/issues

## License

This plugin is free software: you can redistribute it and/or modify it under the terms of the [GNU General Public License](LICENSE) as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
