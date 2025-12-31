export class Message {
  constructor({ id, type, payload, created_at: createdAt, from_client_id: fromClientId, to_client_id: toClientId }) {
    this.id = id;
    this.type = type || 'event';
    this.payload = payload || {};
    this.createdAt = createdAt || '';
    this.fromClientId = fromClientId || 'server';
    this.toClientId = toClientId || 'server';
  }

  directionLabel() {
    if (this.fromClientId === this.toClientId) return 'loopback';
    return `${this.fromClientId || 'server'} → ${this.toClientId || 'server'}`;
  }
}
