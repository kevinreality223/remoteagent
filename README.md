# Encrypted Short-Poll Messaging

This repository is split into two parts:

- `server/` — Laravel 11 implementation of the encrypted short-poll messaging service (see `server/README.md` for API docs and setup).
- `client/` — Python reference client that registers with the API, performs exponential-backoff polling, decrypts messages, and can send encrypted payloads back to the server.
- `operator/` — Python operator CLI for listing registered clients and their online/offline status using operator tokens.

To get started, bring up the Laravel API first, then run the Python client (or operator CLI) against it. Each folder contains its own README with detailed instructions.
