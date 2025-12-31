# Operator Web Console

A Vue 3 + Bootstrap single-page application that talks directly to the existing Laravel messaging server API (`server/`). The app includes a sidebar client list, per-client messaging workspace, and a broadcast dashboard to send messages to all clients.

## Project structure
- `src/models` – simple Client/Message models for consistent formatting.
- `src/stores` – Pinia store that wraps the Laravel operator/admin endpoints.
- `src/router` – routes for the broadcast dashboard and client pages.
- `src/views` – page-level views (`MasterView`, `ClientView`).
- `src/components` – shared UI (sidebar, connection summary, client list).

## Getting started
1. Install Node 18+ and npm.
2. Install dependencies:
   ```bash
   cd operator
   npm install
   ```
3. Run the dev server:
   ```bash
   npm run dev
   ```
   The console is served at the printed localhost URL (default `http://127.0.0.1:5173`).
4. Build for static hosting:
   ```bash
   npm run build
   ```
   The production assets will be emitted to `operator/dist/`.

## API defaults
- The UI calls the Laravel API directly at `http://127.0.0.1:8000`.
- `X-Operator-Token` defaults to `changeme-operator`.
- `X-Admin-Token` defaults to `changeme-admin`.
- These values are defined in `src/stores/operator.js` if you need to change them.

## Using the console
- The **left sidebar** lists clients retrieved from `/api/v1/operators/clients`.
- Selecting a client opens its **client page**, where you can poll `/api/v1/operators/clients/{id}/messages` and publish to that client via `/api/v1/messages/publish`.
- The **Broadcast** button opens the master page, letting you send one payload to every loaded client at once.
- Message payloads must be valid JSON objects; the UI validates and surfaces any API errors.

## Notes
- All requests call the existing Laravel API directly—no Python or additional backend services are required.
- Ensure CORS on the Laravel server allows browser access from your chosen host/port.
