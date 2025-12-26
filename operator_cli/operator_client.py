"""Operator that sends commands to the MQTT room server."""
from __future__ import annotations

import argparse
import logging
import sys
from pathlib import Path

# Ensure the repository root is on sys.path when running from this folder
sys.path.append(str(Path(__file__).resolve().parents[1]))

import paho.mqtt.client as mqtt

from common.mqtt_common import broker_settings, command_topic, mqtt_client, publish_json


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Send commands to the room server")
    sub = parser.add_subparsers(dest="command", required=True)

    room_cmd = sub.add_parser("send-room", help="Send a broadcast to a room")
    room_cmd.add_argument("--room", required=True, help="Room name")
    room_cmd.add_argument("--message", required=True, help="Message to broadcast")

    client_cmd = sub.add_parser("send-client", help="Send a direct message to a client in a room")
    client_cmd.add_argument("--room", required=True, help="Room name")
    client_cmd.add_argument("--client-id", required=True, help="Target client id")
    client_cmd.add_argument("--message", required=True, help="Message text")

    parser.add_argument("--host", default=None, help="MQTT broker host (overrides MQTT_HOST)")
    parser.add_argument("--port", type=int, default=None, help="MQTT broker port (overrides MQTT_PORT)")
    return parser


def main() -> None:
    logging.basicConfig(level=logging.INFO, format="[%(levelname)s] %(name)s: %(message)s")
    parser = build_parser()
    args = parser.parse_args()

    env_host, env_port = broker_settings()
    host = args.host or env_host
    port = args.port or env_port

    client = mqtt_client(client_id="operator")
    client.connect(host, port)

    if args.command == "send-room":
        payload = {"action": "send_room", "room": args.room, "message": args.message}
    elif args.command == "send-client":
        payload = {
            "action": "send_client",
            "room": args.room,
            "client_id": args.client_id,
            "message": args.message,
        }
    else:
        parser.error("Unknown command")
        return

    topic = command_topic()
    logging.getLogger("operator").info("Publishing to %s: %s", topic, payload)
    publish_json(client, topic, payload)
    client.loop_write()  # flush the publish before exit
    client.disconnect()


if __name__ == "__main__":
    main()
