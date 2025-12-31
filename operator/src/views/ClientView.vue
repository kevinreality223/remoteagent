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
        <div class="fw-semibold">{{ client.lastSeenAt }}</div>
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
            <label class="form-label">Payload (JSON)</label>
            <textarea v-model="rawPayload" class="form-control" rows="5" placeholder='{"message": "Hello"}'></textarea>
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
            <button class="btn btn-sm btn-ghost" @click="togglePolling">
              <i :class="polling ? 'bi-pause-fill' : 'bi-play-fill'" class="me-1"></i>
              {{ polling ? 'Stop' : 'Start' }} polling
            </button>
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
const error = ref('');
const loading = ref(false);

const clientId = computed(() => route.params.id);
const client = computed(() => store.selectedClient(clientId.value));
const messages = computed(() => store.clientMessages(clientId.value));

const formatPayload = (payload) => JSON.stringify(payload || {}, null, 2);

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
    const parsed = JSON.parse(rawPayload.value || '{}');
    await store.publish(messageType.value, parsed, [clientId.value]);
    rawPayload.value = '';
    refresh();
  } catch (err) {
    error.value = err.message || 'Failed to send';
  }
};

const togglePolling = () => {
  if (polling.value) {
    clearInterval(polling.value);
    polling.value = null;
  } else {
    refresh();
    polling.value = setInterval(refresh, 4000);
  }
};

watch(
  () => clientId.value,
  () => {
    if (polling.value) {
      clearInterval(polling.value);
      polling.value = null;
    }
    if (clientId.value) {
      refresh();
    }
  }
);

onMounted(() => {
  if (!store.clients.length && store.settings.operatorToken) {
    store.loadClients();
  }
  if (clientId.value) refresh();
});

onBeforeUnmount(() => {
  if (polling.value) clearInterval(polling.value);
});
</script>
