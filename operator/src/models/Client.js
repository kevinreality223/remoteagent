export class Client {
  constructor({
    id,
    name,
    status,
    last_seen_at: lastSeenAt,
    last_polled_at: lastPolledAt,
    next_poll_at: nextPollAt,
    poll_interval_seconds: pollIntervalSeconds,
  }) {
    this.id = id;
    this.name = name || 'Unnamed';
    this.status = status || 'unknown';
    this.lastSeenAt = lastSeenAt ? new Date(lastSeenAt) : null;
    this.lastPolledAt = lastPolledAt ? new Date(lastPolledAt) : null;
    this.nextPollAt = nextPollAt ? new Date(nextPollAt) : null;
    this.pollIntervalSeconds = pollIntervalSeconds || 3;
  }
}
