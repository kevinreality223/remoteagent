# Operator Web Console

A Vue 3 + Bootstrap single-page application that talks directly to the existing Laravel messaging server API (`server/`). The app includes a sidebar client list with live polling timers, a per-client messaging workspace, and a broadcast dashboard to send messages to all clients.

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
- The UI calls the Laravel API directly at `http://localhost:8000`.
- `X-Operator-Token` is set to `changeme-operator`.
- `X-Admin-Token` is set to the live admin secret `changeme-admin` baked into the store.
- These values live in `src/stores/operator.js` so they can be changed centrally if the server is reconfigured.

## Using the console
- The **left sidebar** lists clients retrieved from `/api/v1/operators/clients`.
- Selecting a client opens its **client page**, where polling starts automatically and shows the server-tracked next poll ETA so you can see when a sent message should arrive. Publishing uses `/api/v1/messages/publish`.
- The **Broadcast** button opens the master page, letting you send one payload to every loaded client at once.
- Payloads can be plain text (wrapped server-side) or JSON objects; the UI surfaces any API errors inline.

## Notes
- All requests call the existing Laravel API directly—no Python or additional backend services are required.
- Ensure CORS on the Laravel server allows browser access from your chosen host/port.
- Run the new migration for tracking poll timers: `php artisan migrate` inside `server/`.
