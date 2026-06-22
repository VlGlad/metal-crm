<template>
  <main class="page">
    <TaskHeader
      :saving="saving"
      :loading="loading"
      @refresh="loadTasks"
      @save="saveTask"
    />

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="layout">
      <TaskSidebar
        :tasks="tasks"
        :active-task-id="form.id"
        :loading="loading"
        @new-task="createNewTask"
        @select-task="selectTask"
      />

      <section class="card editor">
        <TaskMainForm v-model="form" />
        <SectionsEditor v-model:sections="form.sections" @error="showError" />
      </section>
    </section>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import TaskHeader from './TaskHeader.vue'
import BaseAlert from './BaseAlert.vue'
import TaskSidebar from './TaskSidebar.vue'
import TaskMainForm from './TaskMainForm.vue'
import SectionsEditor from './SectionsEditor.vue'
import { createShiftTask, getShiftTasks, updateShiftTask } from '../../services/ShiftTasks/shift-tasks.service.js'
import { emptyTask, normalizeTask, payloadFromTask } from '../../utils/ShiftTasks/shiftTaskFactory.js'
import { validateTask } from '../../utils/ShiftTasks/shiftTaskValidation.js'
import { getCurrentUser } from '../../services/auth.js'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const tasks = ref([])
const assignedWorkshop = ref('')

const form = reactive(emptyTask())

function setMessage(type, text) {
  error.value = ''
  success.value = ''

  if (type === 'error') error.value = text
  if (type === 'success') success.value = text
}

function showError(message) {
  setMessage('error', message)
}

function replaceForm(task) {
  Object.assign(form, normalizeTask(task))
  form.workshop = assignedWorkshop.value
}

function createNewTask() {
  replaceForm(emptyTask())
  setMessage('', '')
}

function selectTask(task) {
  replaceForm(task)
  setMessage('', '')
}

async function loadTasks() {
  loading.value = true
  setMessage('', '')

  try {
    const data = await getShiftTasks()
    tasks.value = Array.isArray(data) ? data.map(normalizeTask) : []
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось загрузить список заданий.'))
  } finally {
    loading.value = false
  }
}

async function loadCurrentUser() {
  try {
    const user = await getCurrentUser()
    assignedWorkshop.value = user?.workshop || ''
    form.workshop = assignedWorkshop.value
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось определить цех пользователя.'))
  }
}

async function saveTask() {
  const validationError = validateTask(form)

  if (validationError) {
    setMessage('error', validationError)
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    const payload = payloadFromTask(form)
    const data = form.id
      ? await updateShiftTask(form.id, payload)
      : await createShiftTask(payload)

    replaceForm(data)
    setMessage('success', 'Задание сохранено.')
    await loadTasks()
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось сохранить задание.'))
  } finally {
    saving.value = false
  }
}

function getErrorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

onMounted(async () => {
  await loadCurrentUser()
  await loadTasks()

  if (!assignedWorkshop.value) {
    setMessage('error', 'Для вашей роли не настроен цех.')
  }
})
</script>

<style>
* {
  box-sizing: border-box;
}

body {
  margin: 0;
}

.page {
  min-height: 100vh;
  padding: 32px;
  background: #f4f6f8;
  color: #17202a;
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.layout {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 20px;
}

.card {
  background: #ffffff;
  border: 1px solid #e1e7ef;
  border-radius: 18px;
  box-shadow: 0 12px 30px rgba(18, 38, 63, 0.05);
}

.editor {
  padding: 20px;
}

.card-title-row,
.section-toolbar,
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-top: 18px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 7px;
  font-size: 13px;
  font-weight: 700;
  color: #3c4856;
}

input,
select,
textarea {
  width: 100%;
  border: 1px solid #d7dde5;
  border-radius: 10px;
  padding: 10px 12px;
  background: #ffffff;
  color: #17202a;
  font: inherit;
  outline: none;
}

textarea {
  resize: vertical;
  min-width: 280px;
}

input:focus,
select:focus,
textarea:focus {
  border-color: #2f80ed;
  box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.12);
}

button {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 700;
  cursor: pointer;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.primary {
  background: #2f80ed;
  color: #ffffff;
}

.secondary {
  background: #eef4ff;
  color: #1f63b6;
}

.danger,
.icon-danger {
  background: #fff0f0;
  color: #b42318;
}

.icon-danger {
  width: 36px;
  height: 36px;
  padding: 0;
  font-size: 20px;
}

.link {
  padding: 0;
  background: transparent;
  color: #2f80ed;
}

.muted,
.empty {
  color: #607080;
}

@media (max-width: 1024px) {
  .layout,
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
