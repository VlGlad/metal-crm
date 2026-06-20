<template>
  <aside class="app-sidebar">
    <nav class="nav">
      <RouterLink to="/master" class="nav-link">
        <span>Мастер</span>
      </RouterLink>

      <RouterLink to="/otk-controllers" class="nav-link">
        <span>Контролер ОТК</span>
      </RouterLink>

      <RouterLink to="/analytics" class="nav-link">
        <span>Аналитика</span>
      </RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>

<style scoped>
.app-sidebar {
  position: sticky;
  top: 0;
  height: 100vh;
  padding: 22px;
  background: #ffffff;
  color: #17202a;
  border-right: 1px solid #e1e7ef;
}

.nav {
  display: grid;
  gap: 8px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 12px;
  color: #607080;
  text-decoration: none;
  font-weight: 700;
}

.nav-link:hover {
  background: #f4f6f8;
  color: #17202a;
}

.nav-link.router-link-active {
  background: #eef4ff;
  color: #1f63b6;
}

.nav-icon {
  width: 22px;
  text-align: center;
}

@media (max-width: 768px) {
  .app-sidebar {
    position: static;
    height: auto;
    border-right: none;
    border-bottom: 1px solid #e1e7ef;
  }

  .nav {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>