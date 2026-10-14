#!/usr/bin/env python3
"""Regenerates backend_deliveries.json with the REAL Xentree signing code (backend/app/services/webhook_delivery.py).

The PHP receiver tests verify these bytes and headers, so a change in how Xentree signs or shapes a delivery shows
up as a failing plugin test instead of a production surprise. Throwaway secrets only.

    cd backend && PYTHONPATH=./ DATABASE_URL=postgresql://x:x@127.0.0.1:1/x SECRET_KEY=x \
        python3 ../apps/xentree-moodle-plugin/tests/fixtures/generate_backend_fixtures.py
"""

import base64
import json
import os
import socket
import sys
import uuid
from datetime import datetime, timedelta, timezone
from pathlib import Path
from types import SimpleNamespace

os.environ.setdefault("WEBHOOK_SECRET_MASTER_KEY", base64.urlsafe_b64encode(b"fixture-master-key-0123456789abc").decode())

from sqlalchemy import create_engine  # noqa: E402
from sqlalchemy.orm import sessionmaker  # noqa: E402

from app import connect_models  # noqa: E402
from app.connect_models import WebhookDelivery, WebhookSubscription  # noqa: E402
from app.services import webhook_delivery as wd  # noqa: E402
from app.services.webhook_security import canonical_json_bytes, encrypt_webhook_secret  # noqa: E402

TIMESTAMP = 1790000000
SECRET_NEW = "whsec_" + "11" * 32
SECRET_OLD = "whsec_" + "22" * 32
OUT = Path(__file__).with_name("backend_deliveries.json")


def subscription(*, previous=None):
    return SimpleNamespace(
        id="sub-fixture", scope_type="connect_partner", secret_current_kid="whk_fixture",
        secret_current_ciphertext=encrypt_webhook_secret(SECRET_NEW),
        secret_previous_ciphertext=encrypt_webhook_secret(previous) if previous else None,
        previous_valid_until=(datetime.now(timezone.utc) + timedelta(hours=1)) if previous else None,
    )


def sign(sub, delivery_id, event_type, payload, name):
    body = canonical_json_bytes(payload)
    delivery = SimpleNamespace(id=delivery_id, event_type=event_type, idempotency_key=f"subscription:sub-fixture:{name}")
    headers = wd.build_delivery_headers(sub, delivery, body, TIMESTAMP)
    return {
        "name": name,
        "timestamp": TIMESTAMP,
        "secret_current": SECRET_NEW,
        "secret_previous": SECRET_OLD if sub.secret_previous_ciphertext else None,
        "headers": headers,
        "body": body.decode("utf-8"),
    }


def main() -> None:
    fixtures = []

    event = SimpleNamespace(
        id=str(uuid.uuid4()), event_type="erpi.session.completed",
        created_at=datetime(2026, 10, 14, 8, 30, tzinfo=timezone.utc),
        payload={"session_duration_seconds": 1260, "session_status": "completed", "conversation_id": "must-not-leak"},
    )
    payload = wd._partner_payload(event, "moodle-user-7")
    fixtures.append({**sign(subscription(), str(uuid.uuid4()), "erpi.session.completed", payload, "session_completed"),
                     "expect": {"userid": 7, "seconds": 1260, "status": "completed", "eventid": event.id}})

    event2 = SimpleNamespace(id=str(uuid.uuid4()), event_type="erpi.session.completed",
                             created_at=datetime(2026, 10, 14, 9, 0, 0, 123456, tzinfo=timezone(timedelta(hours=7))),
                             payload={"session_duration_seconds": 30, "session_status": "completed"})
    fixtures.append({**sign(subscription(previous=SECRET_OLD), str(uuid.uuid4()), "erpi.session.completed",
                            wd._partner_payload(event2, "moodle-user-8"), "session_completed_during_rotation"),
                     "expect": {"userid": 8, "seconds": 30, "status": "completed", "eventid": event2.id}})

    # The withdrawal notice, built by the real emit_consent_revoked on an in-memory database.
    connect_models.validate_webhook_endpoint = lambda url: SimpleNamespace(url=url)
    socket.getaddrinfo = lambda *a, **k: [(socket.AF_INET, socket.SOCK_STREAM, 6, "", ("93.184.216.34", 443))]
    engine = create_engine("sqlite://")
    WebhookSubscription.__table__.create(engine)
    WebhookDelivery.__table__.create(engine)
    session = sessionmaker(bind=engine)()
    session.add(WebhookSubscription(
        id="sub-fixture", connect_client_id="c1", organization_id="o1", scope_type="connect_partner",
        endpoint_url="https://moodle.example.com/local/xentreeconnect/webhook.php", event_type_filters=["erpi.session.completed"],
        secret_current_ciphertext=encrypt_webhook_secret(SECRET_NEW), secret_current_kid="whk_fixture", is_active=True,
    ))
    session.commit()
    binding = SimpleNamespace(id="b1", connect_client_id="c1", user_id="u1", organization_id="o1",
                              external_subject="moodle-user-7", revoked_at=datetime(2026, 10, 14, 9, 0))
    assert wd.emit_consent_revoked(session, binding, reason="admin") == 1
    session.commit()
    row = session.query(WebhookDelivery).one()
    fixtures.append({**sign(subscription(), row.id, row.event_type, row.payload, "consent_revoked"),
                     "expect": {"userid": 7, "reason": "admin"}})

    # The admin's test ping, built by the real enqueue_partner_test_ping.
    added = []
    ping = wd.enqueue_partner_test_ping(
        SimpleNamespace(add=added.append),
        SimpleNamespace(id="sub-fixture", organization_id="o1", connect_client_id="c1"), actor_user_id="admin",
    )
    fixtures.append({**sign(subscription(), str(uuid.uuid4()), ping.event_type, ping.payload, "test_ping"), "expect": {}})

    OUT.write_text(json.dumps({"generated_with": "backend/app/services/webhook_delivery.py", "deliveries": fixtures}, indent=2) + "\n")
    print(f"wrote {OUT} ({len(fixtures)} deliveries)")


if __name__ == "__main__":
    sys.exit(main())
