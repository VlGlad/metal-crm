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

      <RouterLink to="/document-workflows" class="nav-link">
        <span>Документооборот</span>
        <span v-if="notificationCounts.documentWorkflows > 0" class="nav-badge">{{ formatBadge(notificationCounts.documentWorkflows) }}</span>
      </RouterLink>

      <RouterLink to="/task-assignments" class="nav-link">
        <span>Поручения</span>
        <span v-if="notificationCounts.taskAssignments > 0" class="nav-badge">{{ formatBadge(notificationCounts.taskAssignments) }}</span>
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
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { getCurrentUser, logout } from '../services/auth.js'
import { getNotificationCounts } from '../services/notifications.service.js'
import { canAccessOrders } from '../constants/orderRoles.js'
import { canAccessMonthlyPlans } from '../constants/monthlyPlanRoles.js'
import { canAccessWorkingDocuments } from '../constants/workingDocumentRoles.js'
import { canAccessProcurementRequests } from '../constants/procurementRequestRoles.js'
import { canAccessProductionProgress } from '../constants/productionProgressRoles.js'

const router = useRouter()
const route = useRoute()

const roles = ref([])
const loggingOut = ref(false)
const notificationCounts = reactive({ documentWorkflows: 0, taskAssignments: 0 })
let refreshTimer = null

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
    await refreshNotifications()
  } catch {
    roles.value = []
  }

  refreshTimer = window.setInterval(refreshNotifications, 60000)
  window.addEventListener('focus', refreshNotifications)
  window.addEventListener('app:notifications-refresh', refreshNotifications)
})

onBeforeUnmount(() => {
  if (refreshTimer) window.clearInterval(refreshTimer)
  window.removeEventListener('focus', refreshNotifications)
  window.removeEventListener('app:notifications-refresh', refreshNotifications)
})

watch(() => route.fullPath, () => {
  refreshNotifications()
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

async function refreshNotifications() {
  try {
    const counts = await getNotificationCounts()
    notificationCounts.documentWorkflows = Number(counts?.documentWorkflows ?? 0)
    notificationCounts.taskAssignments = Number(counts?.taskAssignments ?? 0)
  } catch {
    notificationCounts.documentWorkflows = 0
    notificationCounts.taskAssignments = 0
  }
}

function formatBadge(value) {
  return value > 99 ? '99+' : String(value)
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

.nav-link span:first-child {
  min-width: 0;
  flex: 1;
}

.nav-badge {
  flex: 0 0 auto;
  min-width: 22px;
  height: 22px;
  padding: 0 7px;
  border-radius: 999px;
  background: #f59e0b;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 900;
  line-height: 1;
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
