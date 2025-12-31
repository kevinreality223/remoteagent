import { defineStore } from 'pinia';
import { Client } from '../models/Client';
import { Message } from '../models/Message';

const DEFAULT_BASE_URL = 'http://localhost:8000';
const DEFAULT_OPERATOR_TOKEN = 'changeme-operator';
const DEFAULT_ADMIN_TOKEN = 'changeme-admin';

export const useOperatorStore = defineStore('operator', {
  state: () => ({
    settings: {
      baseUrl: DEFAULT_BASE_URL,
      operatorToken: DEFAULT_OPERATOR_TOKEN,
      adminToken: DEFAULT_ADMIN_TOKEN
    },
    clients: [],
    clientsLoading: false,
    clientsError: null,
    messages: {},
    messageLoading: {},
    alerts: {
      publish: null
    }
  }),
  getters: {
    normalizedBaseUrl: (state) => (state.settings.baseUrl || '').replace(/\/+$/, ''),
    selectedClient: (state) => (id) => state.clients.find((c) => c.id === id) || null,
    clientMessages: (state) => (id) => state.messages[id]?.items || []
  },
  actions: {
    resetAlerts() {
      this.alerts.publish = null;
    },
    ensureMessageState(clientId) {
      if (!this.messages[clientId]) {
        this.messages[clientId] = { items: [], cursor: null };
      }
      return this.messages[clientId];
    },
    async loadClients() {
      this.clientsLoading = true;
      this.clientsError = null;
      try {
        const response = await fetch(`${this.normalizedBaseUrl}/api/v1/operators/clients`, {
          headers: { 'X-Operator-Token': this.settings.operatorToken }
        });
        const body = await response.json();
        if (!response.ok) throw new Error(body.error || body.message || 'Unable to load clients');

        this.clients = (body.clients || []).map((c) => new Client(c));
      } catch (error) {
        this.clientsError = error.message;
      } finally {
        this.clientsLoading = false;
      }
    },
    async fetchMessages(clientId) {
      if (!clientId) return;
      const state = this.ensureMessageState(clientId);
      this.messageLoading[clientId] = true;
      try {
        const url = new URL(`${this.normalizedBaseUrl}/api/v1/operators/clients/${clientId}/messages`);
        if (state.cursor) url.searchParams.set('cursor', state.cursor);
        const response = await fetch(url, { headers: { 'X-Operator-Token': this.settings.operatorToken } });

        if (response.status === 204) {
          this.messageLoading[clientId] = false;
          return;
        }

        const body = await response.json();
        if (!response.ok) throw new Error(body.error || body.message || 'Unable to fetch messages');

        const incoming = (body.messages || []).map((m) => new Message(m));
        if (incoming.length) {
          state.cursor = incoming[incoming.length - 1].id;
          state.items = [...state.items, ...incoming];
        }
      } catch (error) {
        this.messageLoading[clientId] = false;
        throw error;
      }
      this.messageLoading[clientId] = false;
    },
    async publish(type, payload, toClientIds) {
      if (!type) throw new Error('Message type is required');
      if (payload === undefined || payload === null) {
        throw new Error('Payload is required');
      }
      if (typeof payload !== 'object' || Array.isArray(payload)) {
        payload = { message: String(payload) };
      }
      if (!Array.isArray(toClientIds) || toClientIds.length === 0) {
        throw new Error('Select at least one client to publish to');
      }
      const response = await fetch(`${this.normalizedBaseUrl}/api/v1/messages/publish`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Admin-Token': this.settings.adminToken
        },
        body: JSON.stringify({
          to_client_ids: toClientIds,
          type,
          payload
        })
      });
      const body = await response.json();
      if (!response.ok) throw new Error(body.error || body.message || 'Failed to publish message');
      this.alerts.publish = { type: 'success', message: `Queued for ${body.queued} client(s).` };
      return body;
    },
    async broadcast(type, payload) {
      if (!this.clients.length) await this.loadClients();
      const ids = this.clients.map((c) => c.id);
      return this.publish(type, payload, ids);
    }
  }
});
