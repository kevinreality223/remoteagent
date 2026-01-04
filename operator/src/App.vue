<template>
  <div class="container-fluid py-4">
    <div class="row g-3">
      <div class="col-lg-3">
        <SidebarPanel />
      </div>
      <div class="col-lg-9">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import SidebarPanel from './components/SidebarPanel.vue';
</script>
