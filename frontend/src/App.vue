<template>
  <RouterView v-if="isAuthPage" />

  <div v-else class="app-shell">
    <AppSidebar />

    <main class="app-content">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppSidebar from './components/AppSidebar.vue'

const route = useRoute()

const isAuthPage = computed(() => route.path === '/login')
</script>