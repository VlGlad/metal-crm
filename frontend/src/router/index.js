import { createRouter, createWebHistory } from 'vue-router'
import ShiftTasksView from '../components/ShiftTasks/ShiftTasksView.vue'
import OtkControllersView from '../components/OtkControllers/OtkControllersView.vue'
import AnalyticsView from '../components/Analytics/AnalyticsView.vue'
import LoginView from '../components/Auth/LoginView.vue'
import UsersView from '../components/Admin/UsersView.vue'
import OrdersView from '../components/Orders/OrdersView.vue'
import { canAccessOrders, ORDER_PARTICIPANT_ROLES } from '../constants/orderRoles.js'
import { clearSession, getCurrentUser } from '../services/auth.js'

const routes = [
  {
    path: '/',
    redirect: '/master'
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView
  },
  {
    path: '/orders',
    name: 'orders',
    component: OrdersView,
    meta: {
      title: 'Заказы',
      requiresAuth: true,
      roles: ORDER_PARTICIPANT_ROLES
    }
  },
  {
    path: '/master',
    name: 'master',
    component: ShiftTasksView,
    meta: {
      title: 'Мастер',
      requiresAuth: true,
      roles: ['ROLE_MASTER', 'ROLE_CRO', 'ROLE_SSC', 'ROLE_CPO', 'ROLE_ADMIN']
    }
  },
  {
    path: '/otk-controllers',
    name: 'otk-controllers',
    component: OtkControllersView,
    meta: {
      title: 'Контролеры ОТК',
      requiresAuth: true,
      roles: ['ROLE_CONTROLLER_OTK', 'ROLE_ADMIN']
    }
  },
  {
    path: '/users',
    name: 'users',
    component: UsersView,
    meta: {
      title: 'Пользователи',
      requiresAuth: true,
      roles: ['ROLE_ADMIN']
    }
  },
  {
    path: '/analytics',
    name: 'analytics',
    component: AnalyticsView,
    meta: {
      title: 'Аналитика',
      requiresAuth: true
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

function getDefaultRoute(roles = []) {
  if (roles.includes('ROLE_ADMIN')) return '/users'
  if (roles.includes('ROLE_CONTROLLER_OTK')) return '/otk-controllers'
  if (['ROLE_MASTER', 'ROLE_CRO', 'ROLE_SSC', 'ROLE_CPO'].some(role => roles.includes(role))) {
    return '/master'
  }
  if (canAccessOrders(roles)) return '/orders'

  return '/analytics'
}

router.beforeEach(async (to) => {
  const isAuthenticated = Boolean(localStorage.getItem('access_token'))

  if (to.meta.requiresAuth && !isAuthenticated) {
    return '/login'
  }

  if (to.path === '/login' && isAuthenticated) {
    return '/master'
  }

  if (to.meta.roles?.length) {
    try {
      const user = await getCurrentUser()
      const hasRequiredRole = to.meta.roles.some(role => user?.roles?.includes(role))

      if (!hasRequiredRole) {
        return getDefaultRoute(user?.roles)
      }
    } catch {
      clearSession()
      return '/login'
    }
  }
})

export default router