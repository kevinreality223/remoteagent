import { defineStore } from 'pinia';
import { Client } from '../models/Client';
import { Message } from '../models/Message';

const STORAGE_KEYS = {
  baseUrl: 'operator.baseUrl',
  operatorToken: 'operator.operatorToken',
  adminToken: 'operator.adminToken'
};

export const useOperatorStore = defineStore('operator', {
  state: () => ({
    settings: {
      baseUrl: localStorage.getItem(STORAGE_KEYS.baseUrl) || 'http://127.0.0.1:8000',
      operatorToken: localStorage.getItem(STORAGE_KEYS.operatorToken) || '',
      adminToken: localStorage.getItem(STORAGE_KEYS.adminToken) || ''
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
    persistSettings() {
      localStorage.setItem(STORAGE_KEYS.baseUrl, this.settings.baseUrl);
      localStorage.setItem(STORAGE_KEYS.operatorToken, this.settings.operatorToken);
      localStorage.setItem(STORAGE_KEYS.adminToken, this.settings.adminToken);
    },
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
        if (!this.settings.baseUrl) throw new Error('Base URL is required');
        if (!this.settings.operatorToken) throw new Error('Operator token is required');

        const response = await fetch(`${this.normalizedBaseUrl}/api/v1/operators/clients`, {
          headers: { 'X-Operator-Token': this.settings.operatorToken }
        });
        const body = await response.json();
        if (!response.ok) throw new Error(body.error || body.message || 'Unable to load clients');

        this.clients = (body.clients || []).map((c) => new Client(c));
        this.persistSettings();
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

        if (response.status === 204) return;

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
      if (!this.settings.adminToken) throw new Error('Admin token is required');
      if (!type) throw new Error('Message type is required');
      if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
        throw new Error('Payload must be a JSON object');
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
