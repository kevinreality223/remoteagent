from pathlib import Path

# Base URL of the server API (e.g., "http://localhost:8000")
BASE_URL = "http://127.0.0.1:8000"

# Optional friendly name for this client when first registering with the server
CLIENT_NAME = None

# Where to store the issued client credentials
CREDENTIALS_PATH = Path.home() / ".remoteagent" / "client_credentials.json"
