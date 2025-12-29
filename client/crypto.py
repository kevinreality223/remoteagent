from __future__ import annotations

import base64
import json
import secrets
from typing import Any, Dict, Optional

from cryptography.hazmat.primitives.ciphers.aead import AESGCM


class EncryptionError(Exception):
    """Raised when encryption or decryption fails."""


def _aes_key(key_b64: str) -> bytes:
    key = base64.b64decode(key_b64)
    if len(key) != 32:
        raise ValueError("personal_token must decode to 32 bytes (AES-256-GCM key)")
    return key


def encrypt_payload(key_b64: str, payload: Dict[str, Any], aad: Optional[Dict[str, Any]] = None) -> Dict[str, str]:
    key = _aes_key(key_b64)
    iv = secrets.token_bytes(12)
    aad_bytes = json.dumps(aad or {}, separators=(",", ":"), ensure_ascii=False).encode()
    plaintext = json.dumps(payload, separators=(",", ":"), ensure_ascii=False).encode()
    cipher = AESGCM(key).encrypt(iv, plaintext, aad_bytes if aad else None)
    ciphertext, tag = cipher[:-16], cipher[-16:]
    return {
        "ciphertext": base64.b64encode(ciphertext).decode(),
        "nonce": base64.b64encode(iv).decode(),
        "tag": base64.b64encode(tag).decode(),
        "aad": aad or {},
    }


def decrypt_payload(key_b64: str, message: Dict[str, Any], aad: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    key = _aes_key(key_b64)
    ciphertext = base64.b64decode(message["ciphertext"])
    nonce = base64.b64decode(message["nonce"])
    tag = base64.b64decode(message["tag"])
    aad_bytes = json.dumps(aad or {}, separators=(",", ":"), ensure_ascii=False).encode()
    combined = ciphertext + tag
    plaintext = AESGCM(key).decrypt(nonce, combined, aad_bytes if aad else None)
    return json.loads(plaintext.decode())
