import { createRouter, createWebHistory } from 'vue-router'
import ShiftTasksView from '../components/ShiftTasks/ShiftTasksView.vue'
import OtkControllersView from '../components/OtkControllers/OtkControllersView.vue'
import ProductionProgressView from '../components/ProductionProgress/ProductionProgressView.vue'
import AnalyticsView from '../components/Analytics/AnalyticsView.vue'
import LoginView from '../components/Auth/LoginView.vue'
import UsersView from '../components/Admin/UsersView.vue'
import OrdersView from '../components/Orders/OrdersView.vue'
import MonthlyPlansView from '../components/MonthlyPlans/MonthlyPlansView.vue'
import WorkingDocumentsView from '../components/WorkingDocuments/WorkingDocumentsView.vue'
import ProcurementRequestsView from '../components/ProcurementRequests/ProcurementRequestsView.vue'
import DocumentWorkflowsView from '../components/DocumentWorkflows/DocumentWorkflowsView.vue'
import TaskAssignmentsView from '../components/TaskAssignments/TaskAssignmentsView.vue'
import { canAccessProcurementRequests, PROCUREMENT_REQUEST_PARTICIPANT_ROLES } from '../constants/procurementRequestRoles.js'
import { canAccessWorkingDocuments, WORKING_DOCUMENT_PARTICIPANT_ROLES } from '../constants/workingDocumentRoles.js'
import { canAccessMonthlyPlans, MONTHLY_PLAN_PARTICIPANT_ROLES } from '../constants/monthlyPlanRoles.js'
import { canAccessOrders, ORDER_PARTICIPANT_ROLES } from '../constants/orderRoles.js'
import { canAccessProductionProgress, PRODUCTION_PROGRESS_ROLES } from '../constants/productionProgressRoles.js'
import { DOCUMENT_WORKFLOW_ROLES } from '../constants/documentWorkflowRoles.js'
import { TASK_ASSIGNMENT_ROLES } from '../constants/taskAssignmentRoles.js'
import { clearSession, getCurrentUser } from '../services/auth.js'

const routes = [
  {
    path: '/',
    redirect: '/production'
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView
  },
  {
    path: '/document-workflows',
    name: 'document-workflows',
    component: DocumentWorkflowsView,
    meta: {
      title: 'Документооборот',
      requiresAuth: true,
      roles: DOCUMENT_WORKFLOW_ROLES
    }
  },
  {
    path: '/task-assignments',
    name: 'task-assignments',
    component: TaskAssignmentsView,
    meta: {
      title: 'Поручения',
      requiresAuth: true,
      roles: TASK_ASSIGNMENT_ROLES
    }
  },
  {
    path: '/production',
    name: 'production',
    component: ProductionProgressView,
    meta: {
      title: 'Производство',
      requiresAuth: true,
      roles: PRODUCTION_PROGRESS_ROLES
    }
  },
  {
    path: '/procurement-requests',
    name: 'procurement-requests',
    component: ProcurementRequestsView,
    meta: {
      title: 'Заявки на ТМЦ',
      requiresAuth: true,
      roles: PROCUREMENT_REQUEST_PARTICIPANT_ROLES
    }
  },
  {
    path: '/working-documents',
    name: 'working-documents',
    component: WorkingDocumentsView,
    meta: {
      title: 'Рабочие документы',
      requiresAuth: true,
      roles: WORKING_DOCUMENT_PARTICIPANT_ROLES
    }
  },
  {
    path: '/monthly-plans',
    name: 'monthly-plans',
    component: MonthlyPlansView,
    meta: {
      title: 'Планирование',
      requiresAuth: true,
      roles: MONTHLY_PLAN_PARTICIPANT_ROLES
    }
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
  if (canAccessProductionProgress(roles)) return '/production'
  if (canAccessWorkingDocuments(roles)) return '/working-documents'
  if (canAccessOrders(roles)) return '/orders'
  if (canAccessMonthlyPlans(roles)) return '/monthly-plans'
  if (canAccessProcurementRequests(roles)) return '/procurement-requests'

  return '/analytics'
}

router.beforeEach(async (to) => {
  const isAuthenticated = Boolean(localStorage.getItem('access_token'))

  if (to.meta.requiresAuth && !isAuthenticated) {
    return '/login'
  }

  if (to.path === '/login' && isAuthenticated) {
    return '/production'
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
