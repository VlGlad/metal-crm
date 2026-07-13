<template>
  <main class="requests-page">
    <header class="requests-header">
      <div>
        <p class="eyebrow">Четвёртый этап</p>
        <h1>Заявки на приобретение ТМЦ</h1>
        <p class="subtitle">Формирование заявок по одному или нескольким заказам.</p>
      </div>

      <div class="header-actions">
        <button class="secondary" :disabled="loading" @click="loadRequests(form.id)">
          Обновить
        </button>
        <button v-if="canCreate" class="primary" @click="startCreating">
          Новая заявка
        </button>
      </div>
    </header>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="requests-layout">
      <aside class="request-list card">
        <div class="list-header">
          <h2>Заявки</h2>
          <span class="counter">{{ requests.length }}</span>
        </div>

        <p v-if="loading" class="muted">Загрузка...</p>
        <p v-else-if="!requests.length" class="muted">Заявок пока нет.</p>

        <button
          v-for="item in requests"
          :key="item.id"
          class="request-item"
          :class="{ active: item.id === form.id }"
          @click="selectRequest(item)"
        >
          <span class="request-title">
            <strong>{{ item.displayName }}</strong>
            <i :class="['status-badge', `status-${item.status}`]">{{ statusLabel(item.status) }}</i>
          </span>
          <span>{{ orderSummary(item.orders) }}</span>
          <small>{{ item.files.length }} файл(ов) · обновлена {{ formatDateTime(item.updatedAt) }}</small>
        </button>
      </aside>

      <section class="request-editor card">
        <div class="editor-heading">
          <div>
            <h2>{{ form.id ? form.displayName : 'Новая заявка на ТМЦ' }}</h2>
            <p class="muted">Выберите месяц и все заказы, входящие в заявку.</p>
          </div>

          <div v-if="form.id" class="workflow-actions">
            <span :class="['status-badge', `status-${form.status}`]">{{ statusLabel(form.status) }}</span>
            <button
              v-if="nextAction"
              class="primary"
              type="button"
              :disabled="saving"
              @click="runWorkflowAction"
            >
              {{ nextAction.label }}
            </button>
          </div>
        </div>

        <form class="request-form" @submit.prevent="saveRequest">
          <label class="month-field">
            Месяц
            <MonthPicker v-model="form.month" :disabled="!canEditForm" />
          </label>

          <fieldset>
            <legend>Заказы</legend>

            <p v-if="!orderOptions.length" class="muted">
              Сначала создайте хотя бы один заказ.
            </p>

            <div v-else class="orders-grid">
              <label
                v-for="order in orderOptions"
                :key="order.id"
                class="order-option"
              >
                <input
                  v-model="form.orderIds"
                  type="checkbox"
                  :value="order.id"
                  :disabled="!canEditForm"
                />
                <span>
                  <strong>{{ order.number || 'Без номера' }}</strong>
                  <small>{{ order.name }}</small>
                </span>
              </label>
            </div>
          </fieldset>

          <div v-if="canEditForm" class="save-row">
            <button class="primary" type="submit" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить заявку' }}
            </button>
          </div>
        </form>

        <section v-if="form.id" class="workflow-panel">
          <div class="workflow-grid">
            <div v-for="step in workflowSteps" :key="step.key" class="workflow-step">
              <span>{{ step.label }}</span>
              <strong>{{ formatDateTime(form.workflow[`${step.key}At`]) }}</strong>
              <small>{{ form.workflow[`${step.key}By`] || '—' }}</small>
            </div>
          </div>

          <div class="history">
            <div class="history-header">
              <h3>История обработки</h3>
              <button class="secondary history-toggle" type="button" @click="historyOpen = !historyOpen">
                {{ historyOpen ? 'Скрыть историю' : 'Показать историю' }}
              </button>
            </div>

            <template v-if="historyOpen">
              <p v-if="!form.events.length" class="muted">Действий по заявке пока нет.</p>
              <div v-else class="history-list">
                <div v-for="event in form.events" :key="event.id" class="history-item">
                  <span class="history-dot"></span>
                  <div>
                    <strong>{{ statusLabel(event.toStatus) }}</strong>
                    <p>{{ formatDateTime(event.createdAt) }} · {{ event.createdBy || 'Пользователь не указан' }}</p>
                    <small v-if="event.comment">{{ event.comment }}</small>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </section>

        <article class="document-card">
          <div class="document-header">
            <div>
              <h3>Заявка на приобретение ТМЦ</h3>
              <p>Документы для приобретения материалов по выбранным заказам.</p>
            </div>
            <span class="file-count">{{ form.files.length }}</span>
          </div>

          <div v-if="form.files.length" class="file-list">
            <div v-for="file in form.files" :key="file.id" class="file-row">
              <button class="file-name" type="button" @click="download(file)">
                {{ file.name }}
              </button>
              <span>{{ formatFileSize(file.size) }}</span>
              <span>{{ formatDateTime(file.uploadedAt) }}</span>
              <button
                v-if="form.permissions.canEdit"
                class="remove-file"
                type="button"
                title="Удалить файл"
                @click="removeFile(file)"
              >
                ×
              </button>
            </div>
          </div>

          <p v-else class="muted">Файлы ещё не загружены.</p>

          <label
            v-if="form.permissions.canEdit || (!form.id && canCreate)"
            class="upload-control"
            :class="{ disabled: !form.id || uploading }"
          >
            <span>{{ form.id ? 'Добавить файлы' : 'Сначала сохраните заявку' }}</span>
            <input
              type="file"
              multiple
              :disabled="!form.id || uploading"
              @change="upload($event)"
            />
          </label>
        </article>
      </section>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'
import MonthPicker from './MonthPicker.vue'
import {
  createProcurementRequest,
  deleteProcurementRequestFile,
  downloadProcurementRequestFile,
  getProcurementRequests,
  transitionProcurementRequest,
  updateProcurementRequest,
  uploadProcurementRequestFiles
} from '../../services/ProcurementRequests/procurement-requests.service.js'

const STATUS_LABELS = {
  draft: 'Черновик',
  submitted: 'Передана в работу',
  accepted: 'Принята в работу',
  purchased: 'Материал закуплен',
  received: 'Поступило на склад'
}

const workflowSteps = [
  { key: 'submitted', label: 'Передана в работу' },
  { key: 'accepted', label: 'Принята в работу' },
  { key: 'purchased', label: 'Материал закуплен' },
  { key: 'received', label: 'Поступило на склад' }
]

const requests = ref([])
const orderOptions = ref([])
const canCreate = ref(false)
const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const error = ref('')
const success = ref('')
const form = reactive(emptyRequest())
const historyOpen = ref(false)

const canEditForm = computed(() => {
  return !form.id ? canCreate.value : Boolean(form.permissions.canEdit)
})

const nextAction = computed(() => {
  if (form.permissions.canSubmit) return { action: 'submit', label: 'Отдать в работу' }
  if (form.permissions.canAccept) return { action: 'accept', label: 'Принять в работу' }
  if (form.permissions.canMarkPurchased) return { action: 'mark-purchased', label: 'Материал закуплен' }
  if (form.permissions.canMarkReceived) return { action: 'mark-received', label: 'Поступило на склад' }
  return null
})

function currentMonth() {
  return new Date().toISOString().slice(0, 7)
}

function emptyRequest() {
  return {
    id: null,
    month: currentMonth(),
    displayName: '',
    orderIds: [],
    orders: [],
    status: 'draft',
    workflow: {
      submittedAt: null,
      submittedBy: null,
      acceptedAt: null,
      acceptedBy: null,
      purchasedAt: null,
      purchasedBy: null,
      receivedAt: null,
      receivedBy: null
    },
    events: [],
    files: [],
    permissions: {
      canEdit: false,
      canSubmit: false,
      canAccept: false,
      canMarkPurchased: false,
      canMarkReceived: false
    }
  }
}

function normalizeRequest(item = {}) {
  return {
    ...emptyRequest(),
    ...item,
    orderIds: Array.isArray(item.orders) ? item.orders.map(order => order.id) : [],
    orders: Array.isArray(item.orders) ? item.orders : [],
    workflow: {
      ...emptyRequest().workflow,
      ...(item.workflow ?? {})
    },
    events: Array.isArray(item.events) ? item.events : [],
    files: Array.isArray(item.files) ? item.files : [],
    permissions: {
      ...emptyRequest().permissions,
      ...(item.permissions ?? {})
    }
  }
}

function setMessage(type, message) {
  error.value = type === 'error' ? message : ''
  success.value = type === 'success' ? message : ''
}

function startCreating() {
  Object.assign(form, emptyRequest())
  form.permissions.canEdit = canCreate.value
  historyOpen.value = false
  setMessage('', '')
}

function selectRequest(item) {
  Object.assign(form, normalizeRequest(item))
  historyOpen.value = false
  setMessage('', '')
}

async function loadRequests(selectedId = null) {
  loading.value = true

  try {
    const data = await getProcurementRequests()
    requests.value = Array.isArray(data?.requests) ? data.requests.map(normalizeRequest) : []
    orderOptions.value = Array.isArray(data?.orderOptions) ? data.orderOptions : []
    canCreate.value = Boolean(data?.canCreate)

    if (selectedId) {
      const selected = requests.value.find(item => item.id === selectedId)
      if (selected) selectRequest(selected)
    }
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось загрузить заявки.'))
  } finally {
    loading.value = false
  }
}

async function saveRequest() {
  if (!form.month) {
    setMessage('error', 'Укажите месяц.')
    return
  }

  if (!form.orderIds.length) {
    setMessage('error', 'Выберите хотя бы один заказ.')
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    const payload = {
      month: form.month,
      orderIds: [...form.orderIds]
    }

    const saved = form.id
      ? await updateProcurementRequest(form.id, payload)
      : await createProcurementRequest(payload)

    Object.assign(form, normalizeRequest(saved))
    await loadRequests(saved.id)
    setMessage('success', 'Заявка сохранена.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось сохранить заявку.'))
  } finally {
    saving.value = false
  }
}

async function runWorkflowAction() {
  if (!nextAction.value || !form.id) return

  const comment = window.prompt('Комментарий к действию, если нужен:', '')
  if (comment === null) return

  saving.value = true
  setMessage('', '')

  try {
    const updated = await transitionProcurementRequest(form.id, nextAction.value.action, { comment })
    Object.assign(form, normalizeRequest(updated))
    await loadRequests(updated.id)
    setMessage('success', 'Статус заявки обновлён.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось обновить статус заявки.'))
  } finally {
    saving.value = false
  }
}

async function upload(event) {
  const files = Array.from(event.target.files ?? [])
  event.target.value = ''

  if (!form.id || !files.length) return

  uploading.value = true
  setMessage('', '')

  try {
    const updated = await uploadProcurementRequestFiles(form.id, files)
    Object.assign(form, normalizeRequest(updated))
    await loadRequests(updated.id)
    setMessage('success', 'Файлы загружены.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось загрузить файлы.'))
  } finally {
    uploading.value = false
  }
}

async function removeFile(file) {
  if (!window.confirm(`Удалить файл «${file.name}»?`)) return

  uploading.value = true
  setMessage('', '')

  try {
    const updated = await deleteProcurementRequestFile(form.id, file.id)
    Object.assign(form, normalizeRequest(updated))
    await loadRequests(updated.id)
    setMessage('success', 'Файл удалён.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось удалить файл.'))
  } finally {
    uploading.value = false
  }
}

async function download(file) {
  try {
    await downloadProcurementRequestFile(file)
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось скачать файл.'))
  }
}

function statusLabel(status) {
  return STATUS_LABELS[status] || status || '—'
}

function orderSummary(orders) {
  if (!orders?.length) return 'Без заказов'

  return orders
    .map(order => order.number || order.name)
    .join(', ')
}

function formatDateTime(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('ru-RU')
}

function formatFileSize(bytes) {
  if (bytes < 1024) return `${bytes} Б`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} КБ`
  return `${(bytes / 1024 / 1024).toFixed(1)} МБ`
}

function errorMessage(exception, fallback) {
  return exception?.response?.data?.message || exception?.message || fallback
}

onMounted(loadRequests)
</script>

<style scoped>
.requests-page {
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  padding: 32px;
  background: #f4f6f8;
  color: #17202a;
}

.requests-header,
.header-actions,
.list-header,
.editor-heading,
.save-row,
.document-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.requests-header,
.editor-heading {
  align-items: flex-start;
}

.requests-header {
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

h1, h2, h3, p {
  margin-top: 0;
}

h1 {
  margin-bottom: 8px;
}

.subtitle,
.muted {
  color: #607080;
}

.requests-layout {
  display: grid;
  grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
  gap: 20px;
}

.card {
  min-width: 0;
  padding: 20px;
  border: 1px solid #e1e7ef;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(18, 38, 63, 0.05);
}

.request-list {
  align-self: start;
  display: grid;
  gap: 10px;
}

.counter,
.file-count {
  padding: 4px 8px;
  border-radius: 999px;
  background: #eef4ff;
  color: #1f63b6;
  font-size: 12px;
  font-weight: 800;
}

.request-item {
  display: grid;
  gap: 6px;
  width: 100%;
  padding: 13px;
  border: 1px solid #e1e7ef;
  border-radius: 12px;
  background: #fff;
  color: inherit;
  text-align: left;
}

.request-item.active {
  border-color: #2f80ed;
  background: #eef4ff;
}

.request-title {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.request-item span,
.request-item small {
  overflow-wrap: anywhere;
}

.request-item small {
  color: #607080;
}

.workflow-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  width: max-content;
  padding: 5px 9px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #526170;
  font-size: 11px;
  font-style: normal;
  font-weight: 800;
  white-space: nowrap;
}

.status-submitted {
  background: #fff7e6;
  color: #9a5b00;
}

.status-accepted {
  background: #eef4ff;
  color: #1f63b6;
}

.status-purchased {
  background: #f0eaff;
  color: #6941c6;
}

.status-received {
  background: #eaf8ef;
  color: #16703b;
}

.request-form {
  display: grid;
  gap: 18px;
  margin-top: 22px;
}

.month-field {
  max-width: 260px;
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

.orders-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.order-option {
  flex-direction: row;
  align-items: center;
  min-width: 0;
  padding: 12px;
  border: 1px solid #e1e7ef;
  border-radius: 10px;
}

.order-option input {
  width: auto;
}

.order-option span {
  display: grid;
  min-width: 0;
  gap: 3px;
}

.order-option small {
  overflow-wrap: anywhere;
  color: #607080;
}

.save-row {
  justify-content: flex-end;
}

.workflow-panel {
  display: grid;
  gap: 18px;
  margin-top: 24px;
  padding: 18px;
  border: 1px solid #e1e7ef;
  border-radius: 14px;
  background: #fbfcfe;
}

.workflow-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.workflow-step {
  display: grid;
  gap: 5px;
  min-width: 0;
  padding: 12px;
  border: 1px solid #e1e7ef;
  border-radius: 12px;
  background: #fff;
}

.workflow-step span,
.workflow-step small {
  color: #607080;
}

.workflow-step strong,
.workflow-step small {
  overflow-wrap: anywhere;
}

.history-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.history h3 {
  margin-bottom: 0;
}

.history-toggle {
  width: auto;
  padding: 8px 12px;
}

.history-list {
  display: grid;
  gap: 10px;
}

.history-item {
  display: grid;
  grid-template-columns: 10px minmax(0, 1fr);
  gap: 10px;
}

.history-dot {
  width: 10px;
  height: 10px;
  margin-top: 5px;
  border-radius: 999px;
  background: #2f80ed;
}

.history-item p {
  margin-bottom: 3px;
  color: #607080;
  font-size: 13px;
}

.history-item small {
  overflow-wrap: anywhere;
}

.document-card {
  min-width: 0;
  margin-top: 28px;
  padding: 18px;
  border: 1px solid #e1e7ef;
  border-radius: 14px;
  background: #fbfcfe;
}

.document-header h3 {
  margin-bottom: 4px;
}

.document-header p {
  margin-bottom: 0;
  color: #607080;
  font-size: 13px;
}

.file-list {
  display: grid;
  gap: 8px;
  margin-top: 14px;
}

.file-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto 34px;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border: 1px solid #e1e7ef;
  border-radius: 10px;
  background: #fff;
  font-size: 12px;
}

.file-name {
  min-width: 0;
  padding: 0;
  overflow: hidden;
  background: transparent;
  color: #1f63b6;
  text-align: left;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.remove-file {
  width: 30px;
  height: 30px;
  padding: 0;
  background: #fff0f0;
  color: #b42318;
}

.upload-control {
  display: inline-flex;
  flex-direction: row;
  align-items: center;
  width: auto;
  margin-top: 14px;
  padding: 10px 14px;
  border-radius: 10px;
  background: #eef4ff;
  color: #1f63b6;
  cursor: pointer;
}

.upload-control.disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.upload-control input {
  display: none;
}

@media (max-width: 1100px) {
  .requests-layout,
  .orders-grid,
  .workflow-grid {
    grid-template-columns: 1fr;
  }

  .file-row {
    grid-template-columns: minmax(0, 1fr) auto 34px;
  }

  .file-row span:nth-of-type(2) {
    display: none;
  }
}

@media (max-width: 640px) {
  .requests-header,
  .document-header {
    flex-direction: column;
    align-items: stretch;
  }

  .file-row {
    grid-template-columns: minmax(0, 1fr) 34px;
  }

  .file-row span {
    display: none;
  }
}
</style>
