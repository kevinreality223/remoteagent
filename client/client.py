"""MQTT room client that prints messages it receives."""
from __future__ import annotations

import argparse
import logging
import signal
import sys
from dataclasses import dataclass

import paho.mqtt.client as mqtt

from common.mqtt_common import (
    broker_settings,
    direct_topic,
    join_topic,
    leave_topic,
    mqtt_client,
    room_broadcast_topic,
    subscribe_each,
)


@dataclass
class RoomClient:
    client_id: str
    room: str
    host: str
    port: int

    def __post_init__(self) -> None:
        self.logger = logging.getLogger(f"client-{self.client_id}")
        self.mqtt = mqtt_client(client_id=self.client_id)
        self.mqtt.on_connect = self._on_connect
        self.mqtt.on_message = self._on_message
        self.mqtt.on_disconnect = self._on_disconnect

    def start(self) -> None:
        self.logger.info("Connecting to %s:%s", self.host, self.port)
        self.mqtt.will_set(leave_topic(self.room), self.client_id, qos=1, retain=False)
        self.mqtt.connect(self.host, self.port)
        self.mqtt.loop_forever()

    # MQTT callbacks -----------------------------------------------------
    def _on_connect(self, client: mqtt.Client, userdata, flags, reason_code) -> None:  # type: ignore[override]
        if reason_code != 0:
            self.logger.error("Failed to connect: %s", reason_code)
            return
        self.logger.info("Connected; joining room %s", self.room)
        subscribe_each(
            client,
            [
                room_broadcast_topic(self.room),
                direct_topic(self.client_id),
            ],
        )
        client.publish(join_topic(self.room), self.client_id, qos=1, retain=False)

    def _on_disconnect(self, client: mqtt.Client, userdata, reason_code) -> None:  # type: ignore[override]
        if reason_code != mqtt.MQTT_ERR_SUCCESS:
            self.logger.warning("Unexpected disconnect: %s", reason_code)

    def _on_message(self, client: mqtt.Client, userdata, msg: mqtt.MQTTMessage) -> None:  # type: ignore[override]
        text = msg.payload.decode("utf-8")
        self.logger.info("[%s] %s", msg.topic, text)
        print(f"{self.client_id} received on {msg.topic}: {text}")


# Entrypoint -------------------------------------------------------------

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="MQTT room client")
    parser.add_argument("--client-id", required=True, help="Unique client identifier")
    parser.add_argument("--room", required=True, help="Room to join")
    parser.add_argument("--host", default=None, help="MQTT broker host (overrides MQTT_HOST)")
    parser.add_argument("--port", type=int, default=None, help="MQTT broker port (overrides MQTT_PORT)")
    return parser.parse_args()


def main() -> None:
    logging.basicConfig(level=logging.INFO, format="[%(levelname)s] %(name)s: %(message)s")
    args = parse_args()
    env_host, env_port = broker_settings()
    client = RoomClient(
        client_id=args.client_id,
        room=args.room,
        host=args.host or env_host,
        port=args.port or env_port,
    )

    def _graceful_shutdown(signum, frame):
        client.logger.info("Leaving room %s", client.room)
        client.mqtt.publish(leave_topic(client.room), client.client_id, qos=1, retain=False)
        sys.exit(0)

    signal.signal(signal.SIGINT, _graceful_shutdown)
    signal.signal(signal.SIGTERM, _graceful_shutdown)

    client.start()


if __name__ == "__main__":
    main()
