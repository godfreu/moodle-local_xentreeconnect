# Xentree Connect for Moodle (local_xentreeconnect)

Beta plugin (0.2.0) that lets a Moodle site act as a trusted platform for Xentree Connect: it signs a short-lived
JWT launch assertion for the logged-in Moodle user and sends the learner to Xentree, and it can receive learning
activity back from Xentree for members who agreed to share it (see "Receiving activity from Xentree").

Requires Moodle 4.4 or later (tested on 5.2).

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

Copy the `local_xentreeconnect` folder into the Moodle `local/` directory (`public/local/` on Moodle 5.1+), make
it readable by the web server, then:

```bash
php admin/cli/upgrade.php --non-interactive
```

## Launch contract

Moodle POSTs `assertion=<JWT>` (form-encoded) to `{baseurl}/connect/v1/launch/{client_id}`. Header: `alg=RS256`,
`typ=JWT`, `kid`. Claims: `iss`, `aud`, `sub` (`moodle-user-<id>`), `organization_id`, `iat`, `exp` (5 minutes),
`jti` (single use), and `email` when the user has a valid address. Xentree validates the claims and the signature
against the registered JWKS, then redirects the browser to its consent screen.

Implemented by `backend/app/routes/connect.py` in the Xentree repository.

## Receiving activity from Xentree

Xentree sends two kinds of events to a webhook URL you register: `erpi.session.completed` (a member finished a
learning session) and `connect.consent.revoked` (a member withdrew consent, so their stored activity must go).
Payload and headers are specified in `docs/connect_partner_webhook_payload.md` of the Xentree repository.

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

Until Xentree publishes the consent text that tells members their activity is shared (consent version 2), Xentree
does not send `erpi.session.completed` at all. Withdrawal notices are sent regardless.

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
  after members agree to the newer consent text.
- Set a real policy link (`policy_url`) on the integration in Xentree; the consent screen otherwise says none was given.

## Privacy

Sent to Xentree when a user starts the Connect flow: the Moodle user ID (as `moodle-user-<id>`) and, if valid, the
e-mail address.

Stored in Moodle (from 0.2.0): the activity rows described above, linked to the Moodle user. Both flows are declared
through Moodle's Privacy API (`classes/privacy/provider.php`): the site's privacy registry lists them, and data
export and deletion requests cover the stored activity (it is also removed when Xentree reports that the member
withdrew consent, and by the retention task).
