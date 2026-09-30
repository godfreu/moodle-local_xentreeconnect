# Xentree Moodle demo plugin

This folder contains a minimal Moodle plugin scaffold for a client-side demo of the Xentree Connect flow.

Important:
- This is a demo scaffold, not a production-ready integration.
- In production, keep private keys in a secure secret store, not in the repository.
- Use this only to show the flow to a client during a staged walkthrough.

## Goal

Show how Moodle can act as a trusted platform and send a signed launch assertion to Xentree Connect. The plugin demonstrates the client-side flow used by Xentree Connect:

1. Moodle builds a signed JWT assertion.
2. Moodle POSTs it to Xentree `/connect/v1/launch/{client_id}`.
3. Xentree validates `iss`, `aud`, `sub`, `organization_id`, `iat`, `exp`, and `jti`.
4. Xentree redirects the browser to a consent screen.
5. After consent, Xentree creates the identity binding and signs the user into the org.

## Demo flow

1. Admin configures the plugin in Moodle.
2. Plugin generates a JWT using the Moodle-side RSA keypair.
3. Plugin sends the request to Xentree.
4. Xentree validates the assertion and returns a redirect with a session ticket.
5. The browser continues through the consent page and receives Xentree session cookies.

## Files

- `version.php` — Moodle plugin metadata
- `settings.php` — plugin settings page
- `index.php` — demo launch page and request sender
- `lang/en/local_xentreeconnect.php` — strings

## Required config for demo

Set these values in Moodle admin:

- Xentree base URL: `https://app.xentree.ai`
- Client ID: the same value registered in Xentree for this Moodle instance
- Issuer: `https://moodle.example.com`
- Audience: `xentree-connect`
- Organization ID: the org id in Xentree
- Private key PEM: the matching private key for the Moodle JWKS public key
- JWKS JSON: public JWKS object with `keys` array

## Example JWT payload

```json
{
  "iss": "https://moodle.example.com",
  "aud": "xentree-connect",
  "sub": "moodle-user-123",
  "email": "user@example.com",
  "organization_id": "org-demo-123",
  "iat": 1758200000,
  "exp": 1758203600,
  "jti": "moodle-demo-9b7d5f3c"
}
```

## How to install in Moodle

1. Copy this folder into `moodle/local/xentreeconnect/`.
2. Run admin upgrade notifications.
3. Open Site administration → Plugins → Local plugins → Xentree Connect.
4. Fill in values.
5. Open the demo page or add a button to the Moodle login or dashboard.

## Production notes

- Never store the private key in git.
- Use Moodle config.php or a secret manager.
- Keep `kid` aligned with the JWKS published to Xentree.
- Use staging values first, then production.
- For real deployment, add a server-side verification layer and a full consent UX.

## Related Xentree contract

This plugin follows the Xentree Connect contract implemented in:

- `backend/app/routes/connect.py`
- `backend/app/routes/admin_connect.py`
- `backend/tests/test_connect_contract.py`

## Demo command sequence

```bash
# From Moodle app root
# copy plugin into local/xentreeconnect
php admin/cli/upgrade.php --non-interactive
```

Then open the plugin page from the Moodle admin or custom link.
