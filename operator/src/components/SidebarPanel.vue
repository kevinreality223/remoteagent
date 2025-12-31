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
          <div class="mb-3">
            <label class="form-label fw-semibold">Base URL</label>
            <input v-model="store.settings.baseUrl" class="form-control form-control-sm" placeholder="http://127.0.0.1:8000" />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Operator Token</label>
            <input v-model="store.settings.operatorToken" class="form-control form-control-sm" />
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">Admin Token</label>
            <input v-model="store.settings.adminToken" class="form-control form-control-sm" />
          </div>
          <button class="btn btn-primary w-100" @click="persistAndLoad">
            <i class="bi-play-circle me-2"></i>Connect
          </button>
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
          </div>
          <span
            class="badge"
            :class="client.status === 'online' ? 'badge-soft-success' : 'badge-soft-warning'"
          >
            {{ client.status }}
          </span>
        </router-link>
        <div v-if="!store.clients.length" class="placeholder-tile text-center text-faded mt-3">
          No clients yet. Click <span class="fw-semibold">Connect</span> to load.
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
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useOperatorStore } from '../stores/operator';

const route = useRoute();
const router = useRouter();
const store = useOperatorStore();

const persistAndLoad = async () => {
  store.persistSettings();
  await store.loadClients();
  if (!store.clientsError && route.name === undefined && store.clients.length) {
    router.push({ name: 'client', params: { id: store.clients[0].id } });
  }
};

onMounted(() => {
  if (store.clients.length === 0 && store.settings.operatorToken) {
    store.loadClients();
  }
});
</script>
