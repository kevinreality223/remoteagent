<template>
  <div class="glass-panel p-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <div class="badge text-bg-dark border">Broadcast</div>
        <h3 class="mt-2 mb-1">Send to all clients</h3>
        <p class="text-faded mb-0">Publish a message to every registered client in one action.</p>
      </div>
      <div class="text-end">
        <div class="small text-faded">Connected clients</div>
        <div class="display-6 fw-bold">{{ store.clients.length }}</div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="glass-panel p-3 h-100">
          <label class="form-label fw-semibold">Message type</label>
          <input v-model="messageType" class="form-control mb-3" placeholder="event" />

          <div class="btn-group w-100 mb-2">
            <button class="btn" :class="mode === 'text' ? 'btn-primary' : 'btn-outline-primary'" @click="mode = 'text'">
              Plain text
            </button>
            <button class="btn" :class="mode === 'json' ? 'btn-primary' : 'btn-outline-primary'" @click="mode = 'json'">
              JSON body
            </button>
          </div>

          <label class="form-label fw-semibold" v-if="mode === 'json'">Payload (JSON)</label>
          <textarea
            v-if="mode === 'json'"
            v-model="rawPayload"
            class="form-control"
            rows="6"
            placeholder='{"message": "Hello everyone"}'
          ></textarea>
          <label class="form-label fw-semibold" v-else>Payload (text)</label>
          <textarea
            v-else
            v-model="textPayload"
            class="form-control"
            rows="6"
            placeholder="Hello everyone"
          ></textarea>
        </div>
      </div>
      <div class="col-lg-5 d-flex flex-column gap-3">
        <div class="glass-panel p-3 flex-grow-1">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
              <div class="fw-semibold">Audience</div>
              <small class="text-faded">{{ store.clients.length ? 'All registered clients' : 'No clients loaded' }}</small>
            </div>
            <span class="badge bg-info-subtle text-dark" v-if="store.clients.length">{{ store.clients.length }}</span>
          </div>
          <div class="placeholder-tile" v-if="!store.clients.length">
            Load clients to enable broadcasting.
          </div>
          <ul v-else class="list-unstyled mb-0 small text-faded" style="max-height: 220px; overflow: auto;">
            <li v-for="client in store.clients" :key="client.id" class="d-flex justify-content-between py-1">
              <span>{{ client.name }}</span>
              <span class="text-muted">{{ client.status }}</span>
            </li>
          </ul>
        </div>
        <div class="glass-panel p-3">
          <button class="btn btn-success w-100" :disabled="store.clientsLoading" @click="broadcast">
            <span v-if="store.clientsLoading" class="spinner-border spinner-border-sm"></span>
            <span v-else><i class="bi-megaphone me-2"></i>Publish to all</span>
          </button>
          <p class="small text-warning mt-2" v-if="error">{{ error }}</p>
          <p class="small text-success mt-2" v-if="store.alerts.publish">{{ store.alerts.publish.message }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useOperatorStore } from '../stores/operator';

const store = useOperatorStore();
const messageType = ref('event');
const rawPayload = ref('');
const textPayload = ref('');
const mode = ref('text');
const error = ref('');

const broadcast = async () => {
  error.value = '';
  store.resetAlerts();
  try {
    const parsed = mode.value === 'json' ? JSON.parse(rawPayload.value || '{}') : textPayload.value || '';
    await store.broadcast(messageType.value, parsed);
  } catch (err) {
    error.value = err.message || 'Failed to broadcast';
  }
};
</script>
