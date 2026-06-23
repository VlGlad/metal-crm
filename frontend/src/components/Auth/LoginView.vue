<template>
  <main class="login-page">
    <section class="login-card">
      <h1>Вход</h1>

      <form class="login-form" @submit.prevent="submit">
        <BaseAlert v-if="error" type="error" :message="error" />

        <label>
          Email
          <input
            v-model="form.email"
            type="email"
            autocomplete="email"
            placeholder="admin@example.com"
          />
        </label>

        <label>
          Пароль
          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            placeholder="Введите пароль"
          />
        </label>

        <button class="primary" :disabled="loading">
          {{ loading ? 'Вход...' : 'Войти' }}
        </button>
      </form>
    </section>
  </main>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getCurrentUser, login } from '../../services/auth.js'
import { canAccessOrders } from '../../constants/orderRoles.js'
import { canAccessMonthlyPlans } from '../../constants/monthlyPlanRoles.js'
import { canAccessWorkingDocuments } from '../../constants/workingDocumentRoles.js'
import { canAccessProcurementRequests } from '../../constants/procurementRequestRoles.js'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'

const router = useRouter()

const loading = ref(false)
const error = ref('')

const form = reactive({
  email: '',
  password: ''
})

async function submit() {
  error.value = ''

  if (!form.email.trim()) {
    error.value = 'Введите email.'
    return
  }

  if (!form.password.trim()) {
    error.value = 'Введите пароль.'
    return
  }

  loading.value = true

  try {
    error.value = null
    loading.value = true

    try {
        await login(form.email, form.password)
        const user = await getCurrentUser()
        const roles = user?.roles || []
        const routeName = roles.includes('ROLE_ADMIN')
          ? 'users'
          : roles.includes('ROLE_CONTROLLER_OTK')
            ? 'otk-controllers'
            : ['ROLE_MASTER', 'ROLE_CRO', 'ROLE_SSC', 'ROLE_CPO'].some(role => roles.includes(role))
              ? 'master'
              : canAccessWorkingDocuments(roles)
                ? 'working-documents'
                : canAccessOrders(roles)
                  ? 'orders'
                : canAccessMonthlyPlans(roles)
                  ? 'monthly-plans'
                  : canAccessProcurementRequests(roles)
                    ? 'procurement-requests'
                    : 'master'
        await router.push({ name: routeName })
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }

  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background: #f4f6f8;
}

.login-card {
  width: 100%;
  max-width: 420px;
  padding: 28px;
  border: 1px solid #e1e7ef;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 12px 30px rgba(18, 38, 63, 0.06);
}

h1 {
  margin: 0 0 20px;
  color: #17202a;
  font-size: 28px;
}

.login-form {
  display: grid;
  gap: 16px;
}

button {
  height: 44px;
}
</style>