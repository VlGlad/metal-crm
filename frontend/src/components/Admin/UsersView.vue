<template>
  <main class="users-page">
    <header class="users-header">
      <div>
        <p class="eyebrow">Администрирование</p>
        <h1>Пользователи и роли</h1>
        <p class="subtitle">Создание учетных записей и управление правами доступа.</p>
      </div>

      <button class="secondary" :disabled="loading" @click="loadUsers(form.id)">
        Обновить
      </button>
    </header>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="users-layout">
      <aside class="users-list card">
        <div class="list-header">
          <h2>Пользователи</h2>
          <button class="link" @click="startCreating">+ Новый</button>
        </div>

        <p v-if="loading" class="muted">Загрузка...</p>
        <p v-else-if="!users.length" class="muted">Пользователей пока нет.</p>

        <button
          v-for="user in users"
          :key="user.id"
          class="user-list-item"
          :class="{ active: user.id === form.id }"
          @click="selectUser(user)"
        >
          <span class="user-name-row">
            <strong>{{ user.fullName }}</strong>
            <i :class="user.isActive ? 'status-active' : 'status-disabled'">
              {{ user.isActive ? 'Активен' : 'Отключен' }}
            </i>
          </span>
          <small>{{ user.email }}</small>
          <span class="role-summary">{{ getRoleSummary(user.roles) }}</span>
        </button>
      </aside>

      <section class="user-editor card">
        <div>
          <h2>{{ form.id ? 'Редактирование пользователя' : 'Новый пользователь' }}</h2>
          <p class="muted">
            {{ form.id ? 'Оставьте пароль пустым, если менять его не нужно.' : 'Задайте временный пароль не короче 8 символов.' }}
          </p>
        </div>

        <form class="user-form" @submit.prevent="saveUser">
          <div class="form-grid">
            <label>
              ФИО
              <input v-model="form.fullName" type="text" autocomplete="name" />
            </label>

            <label>
              Email
              <input v-model="form.email" type="email" autocomplete="email" />
            </label>

            <label>
              Должность
              <input v-model="form.position" type="text" />
            </label>

            <label>
              Подразделение
              <input v-model="form.department" type="text" />
            </label>

            <label class="full-width">
              {{ form.id ? 'Новый пароль' : 'Временный пароль' }}
              <input
                v-model="form.password"
                type="password"
                :autocomplete="form.id ? 'new-password' : 'off'"
                :placeholder="form.id ? 'Не менять' : 'Минимум 8 символов'"
              />
            </label>
          </div>

          <label class="active-switch">
            <input v-model="form.isActive" type="checkbox" />
            Учетная запись активна
          </label>

          <fieldset>
            <legend>Роли</legend>

            <div class="roles-grid">
              <label
                v-for="role in availableRoles"
                :key="role.value"
                class="role-option"
              >
                <input
                  v-model="form.roles"
                  type="checkbox"
                  :value="role.value"
                />
                <span>
                  <strong>{{ role.label }}</strong>
                  <small>{{ role.value }}</small>
                </span>
              </label>
            </div>
          </fieldset>

          <div class="form-actions">
            <button type="button" class="secondary" @click="startCreating">
              Очистить
            </button>
            <button type="submit" class="primary" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </section>
    </section>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'
import { createUser, getUsers, updateUser } from '../../services/Admin/users.service.js'

const users = ref([])
const availableRoles = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const form = reactive(emptyUser())

function emptyUser() {
  return {
    id: null,
    email: '',
    fullName: '',
    position: '',
    department: '',
    password: '',
    roles: [],
    isActive: true
  }
}

function normalizeUser(user = {}) {
  return {
    id: user.id ?? null,
    email: user.email ?? '',
    fullName: user.fullName ?? '',
    position: user.position ?? '',
    department: user.department ?? '',
    password: '',
    roles: Array.isArray(user.roles) ? [...user.roles] : [],
    isActive: user.isActive ?? true
  }
}

function setMessage(type, message) {
  error.value = type === 'error' ? message : ''
  success.value = type === 'success' ? message : ''
}

function startCreating() {
  Object.assign(form, emptyUser())
  setMessage('', '')
}

function selectUser(user) {
  Object.assign(form, normalizeUser(user))
  setMessage('', '')
}

async function loadUsers(selectedId = null) {
  loading.value = true

  try {
    const data = await getUsers()
    users.value = Array.isArray(data?.users) ? data.users.map(normalizeUser) : []
    availableRoles.value = Array.isArray(data?.availableRoles) ? data.availableRoles : []

    if (selectedId) {
      const selected = users.value.find(user => user.id === selectedId)

      if (selected) {
        selectUser(selected)
      }
    }
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось загрузить пользователей.'))
  } finally {
    loading.value = false
  }
}

function validateForm() {
  if (!form.fullName.trim()) {
    return 'Укажите ФИО пользователя.'
  }

  if (!form.email.trim()) {
    return 'Укажите email.'
  }

  if (!form.id && form.password.length < 8) {
    return 'Временный пароль должен содержать не менее 8 символов.'
  }

  if (form.password && form.password.length < 8) {
    return 'Пароль должен содержать не менее 8 символов.'
  }

  return ''
}

async function saveUser() {
  const validationError = validateForm()

  if (validationError) {
    setMessage('error', validationError)
    return
  }

  saving.value = true
  setMessage('', '')

  const payload = {
    email: form.email.trim(),
    fullName: form.fullName.trim(),
    position: form.position.trim(),
    department: form.department.trim(),
    password: form.password,
    roles: [...form.roles],
    isActive: form.isActive
  }

  try {
    const saved = form.id
      ? await updateUser(form.id, payload)
      : await createUser(payload)

    Object.assign(form, normalizeUser(saved))
    await loadUsers(saved.id)
    setMessage('success', 'Пользователь сохранен.')
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось сохранить пользователя.'))
  } finally {
    saving.value = false
  }
}

function getRoleSummary(roles) {
  if (!roles?.length) {
    return 'Без назначенных ролей'
  }

  return roles
    .map(role => availableRoles.value.find(option => option.value === role)?.label || role)
    .join(', ')
}

function getErrorMessage(exception, fallback) {
  return exception?.response?.data?.message || exception?.message || fallback
}

onMounted(() => loadUsers())
</script>

<style scoped>
.users-page {
  min-height: 100vh;
  padding: 32px;
  background: #f4f6f8;
  color: #17202a;
}

.users-header,
.list-header,
.user-name-row,
.form-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.users-header {
  align-items: flex-start;
  margin-bottom: 24px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #2f80ed;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

h1,
h2,
p {
  margin-top: 0;
}

h1 {
  margin-bottom: 8px;
}

.subtitle,
.muted {
  color: #607080;
}

.users-layout {
  display: grid;
  grid-template-columns: 340px minmax(0, 1fr);
  gap: 20px;
}

.card {
  border: 1px solid #e1e7ef;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 12px 30px rgba(18, 38, 63, 0.05);
}

.users-list,
.user-editor {
  padding: 20px;
}

.users-list {
  align-self: start;
  display: grid;
  gap: 10px;
}

.user-list-item {
  display: grid;
  gap: 6px;
  width: 100%;
  padding: 13px;
  border: 1px solid #e1e7ef;
  border-radius: 12px;
  background: #ffffff;
  color: inherit;
  text-align: left;
}

.user-list-item.active {
  border-color: #2f80ed;
  background: #eef4ff;
}

.user-list-item small,
.role-summary {
  color: #607080;
}

.role-summary {
  font-size: 12px;
  line-height: 1.35;
}

.user-name-row i {
  padding: 4px 7px;
  border-radius: 999px;
  font-size: 10px;
  font-style: normal;
}

.status-active {
  background: #eaf8ef;
  color: #16703b;
}

.status-disabled {
  background: #fff0f0;
  color: #b42318;
}

.user-form {
  display: grid;
  gap: 22px;
  margin-top: 22px;
}

.form-grid,
.roles-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.full-width {
  grid-column: 1 / -1;
}

fieldset {
  margin: 0;
  padding: 16px;
  border: 1px solid #e1e7ef;
  border-radius: 14px;
}

legend {
  padding: 0 8px;
  font-weight: 800;
}

.role-option,
.active-switch {
  flex-direction: row;
  align-items: center;
}

.role-option {
  padding: 12px;
  border: 1px solid #e1e7ef;
  border-radius: 12px;
}

.role-option input,
.active-switch input {
  width: auto;
}

.role-option span {
  display: grid;
  gap: 3px;
}

.role-option small {
  color: #607080;
  font-weight: 500;
}

.form-actions {
  justify-content: flex-end;
}

@media (max-width: 900px) {
  .users-layout,
  .form-grid,
  .roles-grid {
    grid-template-columns: 1fr;
  }

  .full-width {
    grid-column: auto;
  }
}
</style>
