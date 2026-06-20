import { createRouter, createWebHistory } from 'vue-router'
import ShiftTasksView from '../components/ShiftTasks/ShiftTasksView.vue'
import OtkControllersView from '../components/OtkControllers/OtkControllersView.vue'
import AnalyticsView from '../components/Analytics/AnalyticsView.vue'

const routes = [
  {
    path: '/',
    redirect: '/master'
  },
  {
    path: '/master',
    name: 'master',
    component: ShiftTasksView,
    meta: {
      title: 'Мастер'
    }
  },
  {
    path: '/otk-controllers',
    name: 'otk-controllers',
    component: OtkControllersView,
    meta: {
      title: 'Контролеры ОТК'
    }
  },
  {
    path: '/analytics',
    name: 'analytics',
    component: AnalyticsView,
    meta: {
      title: 'Аналитика'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router