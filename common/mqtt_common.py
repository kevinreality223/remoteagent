"""Shared MQTT helpers for the room messaging demo."""
import json
import logging
import os
from typing import Callable, Dict, Iterable

import paho.mqtt.client as mqtt


def mqtt_client(client_id: str | None = None) -> mqtt.Client:
    """Create a configured MQTT client with logging enabled."""
    client = mqtt.Client(client_id=client_id, protocol=mqtt.MQTTv311)
    client.enable_logger(logging.getLogger("mqtt"))
    return client


def broker_settings() -> tuple[str, int]:
    """Read broker host and port from the environment."""
    host = os.getenv("MQTT_HOST", "localhost")
    port = int(os.getenv("MQTT_PORT", "1883"))
    return host, port


def publish_json(client: mqtt.Client, topic: str, payload: Dict) -> None:
    """Publish a JSON payload without retention."""
    client.publish(topic, json.dumps(payload), qos=1, retain=False)


def parse_json(payload: bytes) -> Dict:
    """Decode a JSON payload from MQTT."""
    return json.loads(payload.decode("utf-8"))


def join_topic(room: str) -> str:
    return f"rooms/{room}/join"


def leave_topic(room: str) -> str:
    return f"rooms/{room}/leave"


def room_broadcast_topic(room: str) -> str:
    return f"rooms/{room}/messages"


def direct_topic(client_id: str) -> str:
    return f"clients/{client_id}/direct"


def command_topic() -> str:
    return "server/commands"


def subscribe_each(client: mqtt.Client, topics: Iterable[str]) -> None:
    for topic in topics:
        client.subscribe(topic, qos=1)


CommandHandler = Callable[[Dict], None]


class CommandRouter:
    """Route operator commands to handler functions."""

    def __init__(self) -> None:
        self._handlers: Dict[str, CommandHandler] = {}

    def register(self, action: str, handler: CommandHandler) -> None:
        self._handlers[action] = handler

    def dispatch(self, payload: Dict) -> None:
        action = payload.get("action")
        if action not in self._handlers:
            raise ValueError(f"Unknown action: {action}")
        self._handlers[action](payload)
