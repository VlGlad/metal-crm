import { createRouter, createWebHistory } from 'vue-router'
import ShiftTasksView from '../components/ShiftTasks/ShiftTasksView.vue'
import OtkControllersView from '../components/OtkControllers/OtkControllersView.vue'
import AnalyticsView from '../components/Analytics/AnalyticsView.vue'
import LoginView from '../components/Auth/LoginView.vue'
import UsersView from '../components/Admin/UsersView.vue'
import { getCurrentUser, logout } from '../services/auth.js'

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
    path: '/master',
    name: 'master',
    component: ShiftTasksView,
    meta: {
      title: 'Мастер',
      requiresAuth: true
    }
  },
  {
    path: '/otk-controllers',
    name: 'otk-controllers',
    component: OtkControllersView,
    meta: {
      title: 'Контролеры ОТК',
      requiresAuth: true
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
        return '/master'
      }
    } catch {
      logout()
      return '/login'
    }
  }
})

export default router