# Xentree Connect for Moodle (local_xentreeconnect)

Beta plugin (0.1.1) that lets a Moodle site act as a trusted platform for Xentree Connect: it signs a short-lived
JWT launch assertion for the logged-in Moodle user and sends the learner to Xentree.

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

## Tests

The launcher logic is plain PHP and is covered in `tests/launcher_test.php`. Inside Moodle use its PHPUnit setup.
Without a Moodle checkout, with a standalone PHPUnit:

```bash
phpunit --bootstrap tests/standalone/bootstrap.php --test-suffix _test.php tests
```

## Before production

This plugin is a demo-grade beta. Before using it with real learners:

- **Fix private key storage.** The key is currently saved unencrypted in the Moodle database (and shown to anyone who
  can open the plugin settings). Move it to a secret store, for example `config.php` or an environment variable read
  by the plugin, and restrict who can edit the settings.
- **Rotate keys deliberately.** Changing the key means registering the new JWKS in Xentree first, otherwise launches
  fail with HTTP 401. Use a new `kid` for each key.
- **Review the consent wording** shown by Xentree with your data-protection adviser. Activity and learning data is
  not sent to Moodle yet.
- Set a real policy link (`policy_url`) on the integration in Xentree; the consent screen otherwise says none was given.

## Privacy

The plugin stores no personal data in Moodle. When a user starts the Connect flow it sends their Moodle user ID (as
`moodle-user-<id>`) and, if valid, their e-mail address to Xentree. This is declared through Moodle's Privacy API
(`classes/privacy/provider.php`), so it appears in the site's privacy registry. There is nothing local to export or
delete; the provider implements the interfaces with empty methods on purpose.
