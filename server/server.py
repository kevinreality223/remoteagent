"""Room-aware MQTT server.

The server listens for join/leave events from clients and relays messages
that arrive on the operator command channel. It only sends messages to
clients that have explicitly joined a room, mirroring WebSocket-style room
semantics.
"""
from __future__ import annotations

import argparse
import logging
from collections import defaultdict
from dataclasses import dataclass, field
from typing import Dict, Set

import paho.mqtt.client as mqtt

from common.mqtt_common import (
    CommandRouter,
    broker_settings,
    command_topic,
    direct_topic,
    join_topic,
    leave_topic,
    mqtt_client,
    parse_json,
    room_broadcast_topic,
    subscribe_each,
)


@dataclass
class RoomServer:
    """Keeps track of room membership and relays messages."""

    host: str
    port: int
    memberships: Dict[str, Set[str]] = field(default_factory=lambda: defaultdict(set))

    def __post_init__(self) -> None:
        self.logger = logging.getLogger("room-server")
        self.mqtt = mqtt_client(client_id="room-server")
        self.router = CommandRouter()
        self._register_handlers()
        self.mqtt.on_connect = self._on_connect
        self.mqtt.on_message = self._on_message

    def _register_handlers(self) -> None:
        self.router.register("send_room", self._handle_send_room)
        self.router.register("send_client", self._handle_send_client)

    def start(self) -> None:
        self.logger.info("Connecting to MQTT broker %s:%s", self.host, self.port)
        self.mqtt.connect(self.host, self.port)
        self.mqtt.loop_forever()

    # MQTT callbacks -----------------------------------------------------
    def _on_connect(self, client: mqtt.Client, userdata, flags, reason_code) -> None:  # type: ignore[override]
        if reason_code != 0:
            self.logger.error("Failed to connect: %s", reason_code)
            return
        self.logger.info("Connected to broker; subscribing to control topics")
        subscribe_each(
            client,
            [
                "rooms/+/join",
                "rooms/+/leave",
                command_topic(),
            ],
        )

    def _on_message(self, client: mqtt.Client, userdata, msg: mqtt.MQTTMessage) -> None:  # type: ignore[override]
        topic = msg.topic
        try:
            if topic.startswith("rooms/") and topic.endswith("/join"):
                self._handle_join(topic, msg.payload)
            elif topic.startswith("rooms/") and topic.endswith("/leave"):
                self._handle_leave(topic, msg.payload)
            elif topic == command_topic():
                self.router.dispatch(parse_json(msg.payload))
            else:
                self.logger.warning("Unhandled topic: %s", topic)
        except Exception:
            self.logger.exception("Error handling message on topic %s", topic)

    # Handlers -----------------------------------------------------------
    def _handle_join(self, topic: str, payload: bytes) -> None:
        room = topic.split("/")[1]
        client_id = payload.decode("utf-8")
        self.memberships[room].add(client_id)
        self.logger.info("Client %s joined room %s", client_id, room)

    def _handle_leave(self, topic: str, payload: bytes) -> None:
        room = topic.split("/")[1]
        client_id = payload.decode("utf-8")
        if client_id in self.memberships.get(room, set()):
            self.memberships[room].remove(client_id)
            if not self.memberships[room]:
                self.memberships.pop(room, None)
            self.logger.info("Client %s left room %s", client_id, room)
        else:
            self.logger.debug("Client %s left unknown room %s", client_id, room)

    def _handle_send_room(self, payload: Dict) -> None:
        room = payload["room"]
        message = payload["message"]
        members = self.memberships.get(room, set())
        if not members:
            self.logger.warning("No members in room %s; skipping broadcast", room)
            return
        topic = room_broadcast_topic(room)
        self.logger.info("Broadcasting to room %s (%d members)", room, len(members))
        self.mqtt.publish(topic, message, qos=1, retain=False)

    def _handle_send_client(self, payload: Dict) -> None:
        room = payload["room"]
        client_id = payload["client_id"]
        message = payload["message"]
        if client_id not in self.memberships.get(room, set()):
            self.logger.warning("Client %s is not in room %s; skipping direct send", client_id, room)
            return
        topic = direct_topic(client_id)
        self.logger.info("Sending direct message to %s (room %s)", client_id, room)
        self.mqtt.publish(topic, message, qos=1, retain=False)


# Entrypoint -------------------------------------------------------------

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Room-aware MQTT server")
    parser.add_argument("--host", default=None, help="MQTT broker host (overrides MQTT_HOST)")
    parser.add_argument("--port", type=int, default=None, help="MQTT broker port (overrides MQTT_PORT)")
    return parser.parse_args()


def main() -> None:
    logging.basicConfig(level=logging.INFO, format="[%(levelname)s] %(name)s: %(message)s")
    args = parse_args()
    env_host, env_port = broker_settings()
    server = RoomServer(host=args.host or env_host, port=args.port or env_port)
    server.start()


if __name__ == "__main__":
    main()
