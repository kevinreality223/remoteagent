<template>
  <div class="glass-panel p-4 h-100" v-if="client">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <div class="badge text-bg-secondary border">Client</div>
        <h3 class="mt-2 mb-1">{{ client.name }}</h3>
        <p class="text-faded mb-0">{{ client.id }}</p>
      </div>
      <div class="text-end">
        <div class="small text-faded">Last seen</div>
        <div class="fw-semibold">{{ formatDate(client.lastSeenAt) }}</div>
        <div class="small text-info" v-if="client.nextPollAt">Next poll in {{ countdown(client.nextPollAt) }}</div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="glass-panel p-3 h-100">
          <h6 class="fw-semibold mb-3">Send message</h6>
          <div class="mb-3">
            <label class="form-label">Message type</label>
            <input v-model="messageType" class="form-control" />
          </div>
          <div class="mb-3">
            <div class="btn-group w-100 mb-2">
              <button class="btn" :class="sendMode === 'text' ? 'btn-primary' : 'btn-outline-primary'" @click="sendMode = 'text'">
                Plain text
              </button>
              <button class="btn" :class="sendMode === 'json' ? 'btn-primary' : 'btn-outline-primary'" @click="sendMode = 'json'">
                JSON body
              </button>
            </div>
            <label class="form-label" v-if="sendMode === 'json'">Payload (JSON)</label>
            <textarea
              v-if="sendMode === 'json'"
              v-model="rawPayload"
              class="form-control"
              rows="5"
              placeholder='{"message": "Hello"}'
            ></textarea>
            <label class="form-label" v-else>Payload (text)</label>
            <textarea
              v-else
              v-model="textPayload"
              class="form-control"
              rows="5"
              placeholder="Hi there"
            ></textarea>
          </div>
          <div class="d-grid gap-2">
            <button class="btn btn-success" @click="send">Send</button>
            <button class="btn btn-ghost" @click="refresh" :disabled="loading">Refresh messages</button>
            <p class="small text-warning" v-if="error">{{ error }}</p>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="glass-panel p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-semibold mb-0">Message history</h6>
            <span class="badge bg-success-subtle text-dark" v-if="polling">Live polling</span>
          </div>
          <div class="border rounded-4 p-3" style="height: 420px; overflow: auto; background: rgba(0,0,0,0.35);">
            <div v-if="messages.length === 0" class="text-center text-faded">No messages yet.</div>
            <div v-for="msg in messages" :key="msg.id" class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="badge bg-secondary">{{ msg.type }}</div>
                <small class="text-faded">{{ msg.directionLabel() }}</small>
              </div>
              <div class="d-flex justify-content-between small text-faded mb-1">
                <span>{{ msg.createdAt || 'unknown' }}</span>
              </div>
              <pre class="json-block mb-0">{{ formatPayload(msg.payload) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else class="glass-panel p-4 placeholder-tile">
    <p class="mb-0">Select a client from the left panel to get started.</p>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useOperatorStore } from '../stores/operator';

const store = useOperatorStore();
const route = useRoute();
const polling = ref(null);
const messageType = ref('event');
const rawPayload = ref('');
const textPayload = ref('');
const sendMode = ref('text');
const error = ref('');
const loading = ref(false);
const now = ref(Date.now());

let heartbeat;

const clientId = computed(() => route.params.id);
const client = computed(() => store.selectedClient(clientId.value));
const messages = computed(() => store.clientMessages(clientId.value));

const formatPayload = (payload) => JSON.stringify(payload || {}, null, 2);
const formatDate = (value) => (value ? new Date(value).toLocaleString() : 'unknown');
const countdown = (value) => {
  if (!value) return '—';
  const diff = Math.max(0, Math.round((new Date(value).getTime() - now.value) / 1000));
  const mins = Math.floor(diff / 60);
  const secs = diff % 60;
  if (mins) return `${mins}m ${secs.toString().padStart(2, '0')}s`;
  return `${secs}s`;
};

const refresh = async () => {
  if (!clientId.value) return;
  loading.value = true;
  error.value = '';
  try {
    await store.fetchMessages(clientId.value);
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

const send = async () => {
  if (!clientId.value) return;
  error.value = '';
  try {
    const parsed =
      sendMode.value === 'json'
        ? JSON.parse(rawPayload.value || '{}')
        : textPayload.value || '';
    await store.publish(messageType.value, parsed, [clientId.value]);
    rawPayload.value = '';
    textPayload.value = '';
    refresh();
  } catch (err) {
    error.value = err.message || 'Failed to send';
  }
};

const startPolling = () => {
  if (polling.value) clearInterval(polling.value);
  refresh();
  polling.value = setInterval(refresh, 4000);
};

watch(
  () => clientId.value,
  () => {
    if (clientId.value) {
      startPolling();
    } else if (polling.value) {
      clearInterval(polling.value);
      polling.value = null;
    }
  }
);

onMounted(() => {
  if (!store.clients.length) {
    store.loadClients();
  }
  heartbeat = setInterval(() => (now.value = Date.now()), 1000);
  if (clientId.value) startPolling();
});

onBeforeUnmount(() => {
  if (polling.value) clearInterval(polling.value);
  if (heartbeat) clearInterval(heartbeat);
});
</script>
