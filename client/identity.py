from __future__ import annotations

import hashlib
import platform
import socket
import uuid
from pathlib import Path
from typing import Dict


def _mac_address() -> str:
    mac_int = uuid.getnode()
    return f"{mac_int:012x}"


def _home_device_hint() -> str:
    return str(Path.home())


def fingerprint_components() -> Dict[str, str]:
    return {
        "hostname": socket.gethostname(),
        "platform": platform.platform(),
        "machine": platform.machine(),
        "mac": _mac_address(),
        "home": _home_device_hint(),
    }


def machine_fingerprint() -> str:
    parts = fingerprint_components()
    joined = "|".join(f"{key}:{value}" for key, value in sorted(parts.items()))
    return hashlib.sha256(joined.encode("utf-8")).hexdigest()
