# MQTT Room Messaging Demo

This repository provides a minimal MQTT-powered setup with three roles, each in its own directory:

- **`server/`**: tracks room memberships and relays messages only to subscribed clients.
- **`client/`**: joins a room and prints any messages delivered to it.
- **`operator_cli/`**: sends commands to the server to broadcast to a room or target specific clients.

All components share the same MQTT broker connection. By default, they point to `localhost:1883` but you can change the host and port with the `MQTT_HOST` and `MQTT_PORT` environment variables.

## Quick start

Install dependencies:

```bash
pip install -r requirements.txt
```

Run the server (in its own terminal):

```bash
python server/server.py
```

Start one or more clients, each with its own ID and room:

```bash
python client/client.py --client-id alice --room lobby
python client/client.py --client-id bob --room lobby
python client/client.py --client-id charlie --room ops
```

Send a room broadcast:

```bash
python operator_cli/operator_client.py send-room --room lobby --message "Hello lobby!"
```

Target a single client that belongs to a room:

```bash
python operator_cli/operator_client.py send-client --room lobby --client-id bob --message "Private hello"
```

Clients print any messages they receive. The server will only deliver room messages to clients that joined that room.

## Topics used

- `rooms/<room>/join` and `rooms/<room>/leave`: clients announce membership changes.
- `rooms/<room>/messages`: server broadcasts to all members of a room.
- `clients/<client_id>/direct`: server sends per-client messages when allowed by membership.
- `server/commands`: operator publishes JSON commands for the server to process.

## Environment variables

- `MQTT_HOST` (default `localhost`)
- `MQTT_PORT` (default `1883`)

## Notes

- The scripts avoid retaining messages; messages are only delivered to connected, subscribed clients.
- If you restart the server, clients should re-send their join events (they already do this on startup).
- The design mirrors WebSocket-style rooms but uses MQTT topics for fan-out and direct messaging.
