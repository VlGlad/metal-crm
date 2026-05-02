<template>
  <div class="app-shell">
    <AppSidebar />

    <main class="app-content">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
import AppSidebar from './components/AppSidebar.vue'
</script>