<template>
  <aside class="app-sidebar">
    <nav class="nav">
      <RouterLink v-if="canSeeOrders" to="/orders" class="nav-link">
        <span>Заказы</span>
      </RouterLink>

      <RouterLink v-if="canSeeMonthlyPlans" to="/monthly-plans" class="nav-link">
        <span>Планирование</span>
      </RouterLink>

      <RouterLink v-if="canSeeWorkingDocuments" to="/working-documents" class="nav-link">
        <span>Рабочие документы</span>
      </RouterLink>

      <RouterLink v-if="canSeeProcurementRequests" to="/procurement-requests" class="nav-link">
        <span>Заявки на ТМЦ</span>
      </RouterLink>

      <RouterLink v-if="canSeeProduction" to="/production" class="nav-link">
        <span>Производство</span>
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
import { canAccessMonthlyPlans } from '../constants/monthlyPlanRoles.js'
import { canAccessWorkingDocuments } from '../constants/workingDocumentRoles.js'
import { canAccessProcurementRequests } from '../constants/procurementRequestRoles.js'
import { canAccessProductionProgress } from '../constants/productionProgressRoles.js'

const router = useRouter()

const roles = ref([])
const loggingOut = ref(false)

const canSeeProcurementRequests = computed(() => canAccessProcurementRequests(roles.value))
const canSeeWorkingDocuments = computed(() => canAccessWorkingDocuments(roles.value))
const canSeeMonthlyPlans = computed(() => canAccessMonthlyPlans(roles.value))
const canSeeOrders = computed(() => canAccessOrders(roles.value))
const isAdmin = computed(() => roles.value.includes('ROLE_ADMIN'))
const canSeeProduction = computed(() => canAccessProductionProgress(roles.value))

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