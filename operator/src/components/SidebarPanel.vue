<template>
  <div class="glass-panel sidebar p-3 h-100 d-flex flex-column">
    <div class="d-flex align-items-center gap-2 mb-3">
      <div class="p-2 rounded-4" style="background: var(--gradient); width: 42px; height: 42px;"></div>
      <div>
        <div class="fw-bold brand-glow">Operator Console</div>
        <div class="small text-faded">Vue + Bootstrap</div>
      </div>
    </div>

    <div class="mb-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 text-uppercase small text-faded">Connection</h6>
        <button class="btn btn-sm btn-ghost" @click="store.loadClients" :disabled="store.clientsLoading">
          <span v-if="store.clientsLoading" class="spinner-border spinner-border-sm"></span>
          <span v-else><i class="bi-arrow-repeat me-1"></i>Sync</span>
        </button>
      </div>
      <div class="stacked-card">
        <div class="p-3 glass-panel">
          <div class="d-flex align-items-start gap-2 mb-2">
            <div class="icon-tile"><i class="bi-globe"></i></div>
            <div>
              <div class="fw-semibold">API endpoint</div>
              <div class="small text-faded">{{ store.settings.baseUrl }}</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-secondary-subtle text-dark">Operator token</span>
            <code class="text-light small">{{ store.settings.operatorToken }}</code>
          </div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-secondary-subtle text-dark">Admin token</span>
            <code class="text-light small">{{ store.settings.adminToken }}</code>
          </div>
          <p class="small text-faded mb-0">Defaults are baked in—no setup required.</p>
          <p class="small text-warning mt-2" v-if="store.clientsError">{{ store.clientsError }}</p>
        </div>
      </div>
    </div>

    <div class="flex-grow-1 overflow-auto">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 text-uppercase small text-faded">Clients</h6>
        <span class="badge bg-info-subtle text-dark" v-if="store.clients.length">{{ store.clients.length }}</span>
      </div>
      <div class="list-group list-group-flush">
        <router-link
          v-for="client in store.clients"
          :key="client.id"
          class="list-group-item list-group-item-action bg-transparent text-light d-flex justify-content-between align-items-start"
          :class="{ active: route.params.id === client.id }"
          :to="{ name: 'client', params: { id: client.id } }"
        >
          <div>
            <div class="fw-semibold">{{ client.name }}</div>
            <div class="small text-faded">{{ client.id }}</div>
            <div class="small text-info" v-if="client.nextPollAt">Next poll in {{ countdown(client.nextPollAt) }}</div>
          </div>
          <div class="text-end">
            <span
              class="badge"
              :class="client.status === 'online' ? 'badge-soft-success' : 'badge-soft-warning'"
            >
              {{ client.status }}
            </span>
            <div class="small text-faded" v-if="client.lastPolledAt">Last poll {{ formatDate(client.lastPolledAt) }}</div>
          </div>
        </router-link>
        <div v-if="!store.clients.length" class="placeholder-tile text-center text-faded mt-3">
          No clients yet. Click <span class="fw-semibold">Sync</span> to refresh.
        </div>
      </div>
    </div>

    <div class="mt-3">
      <router-link class="btn btn-ghost w-100" to="/master">
        <i class="bi-megaphone-fill me-2"></i> Broadcast to all
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useOperatorStore } from '../stores/operator';

const route = useRoute();
const store = useOperatorStore();
const now = ref(Date.now());

let timer;
let refresh;

const formatDate = (value) => (value ? new Date(value).toLocaleTimeString() : '—');
const countdown = (value) => {
  if (!value) return '—';
  const diff = Math.max(0, Math.round((new Date(value).getTime() - now.value) / 1000));
  const mins = Math.floor(diff / 60);
  const secs = diff % 60;
  if (mins) return `${mins}m ${secs.toString().padStart(2, '0')}s`;
  return `${secs}s`;
};

onMounted(() => {
  if (store.clients.length === 0) {
    store.loadClients();
  }
  timer = setInterval(() => (now.value = Date.now()), 1000);
  refresh = setInterval(() => store.loadClients(), 5000);
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
  if (refresh) clearInterval(refresh);
});
</script>
