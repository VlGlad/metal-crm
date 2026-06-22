<template>
  <aside class="app-sidebar">
    <nav class="nav">
      <RouterLink v-if="canSeeOrders" to="/orders" class="nav-link">
        <span>Заказы</span>
      </RouterLink>

      <RouterLink v-if="canSeeMaster" to="/master" class="nav-link">
        <span>Мастер</span>
      </RouterLink>

      <RouterLink v-if="canSeeOtk" to="/otk-controllers" class="nav-link">
        <span>Контролер ОТК</span>
      </RouterLink>

      <RouterLink v-if="isAdmin" to="/users" class="nav-link">
        <span>Пользователи</span>
      </RouterLink>
    </nav>

    <button class="logout-button" :disabled="loggingOut" @click="handleLogout">
      {{ loggingOut ? 'Выход...' : 'Выйти' }}
    </button>
  </aside>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { getCurrentUser, logout } from '../services/auth.js'
import { canAccessOrders } from '../constants/orderRoles.js'

const router = useRouter()

const roles = ref([])
const loggingOut = ref(false)

const canSeeOrders = computed(() => canAccessOrders(roles.value))
const isAdmin = computed(() => roles.value.includes('ROLE_ADMIN'))
const canSeeMaster = computed(() => {
  return isAdmin.value || ['ROLE_MASTER', 'ROLE_CRO', 'ROLE_SSC', 'ROLE_CPO']
    .some(role => roles.value.includes(role))
})
const canSeeOtk = computed(() => {
  return isAdmin.value || roles.value.includes('ROLE_CONTROLLER_OTK')
})

onMounted(async () => {
  try {
    const user = await getCurrentUser()
    roles.value = Array.isArray(user?.roles) ? user.roles : []
  } catch {
    roles.value = []
  }
})

async function handleLogout() {
  loggingOut.value = true

  try {
    await logout()
  } finally {
    loggingOut.value = false
    await router.replace({ name: 'login' })
  }
}
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
  display: flex;
  flex-direction: column;
}

.nav {
  flex: 1;
  align-content: start;
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

.logout-button {
  width: 100%;
  padding: 11px 14px;
  border: 1px solid #f0caca;
  border-radius: 10px;
  background: #fff5f5;
  color: #b42318;
  font-weight: 700;
  cursor: pointer;
}

.logout-button:hover {
  background: #ffe9e9;
}

.logout-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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