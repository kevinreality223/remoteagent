export class Client {
  constructor({ id, name, status, last_seen_at: lastSeenAt }) {
    this.id = id;
    this.name = name || 'Unnamed';
    this.status = status || 'unknown';
    this.lastSeenAt = lastSeenAt || 'never';
  }
}
