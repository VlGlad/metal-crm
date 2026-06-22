<template>
  <main class="orders-page">
    <header class="orders-header">
      <div>
        <p class="eyebrow">Первый этап</p>
        <h1>Заказы</h1>
        <p class="subtitle">Документы для анализа и запуска заказа в производство.</p>
      </div>

      <div class="header-actions">
        <button class="secondary" :disabled="loading" @click="loadOrders(form.id)">
          Обновить
        </button>
        <button v-if="canCreate" class="primary" @click="startCreating">
          Новый заказ
        </button>
      </div>
    </header>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="orders-layout">
      <aside class="orders-list card">
        <div class="list-header">
          <h2>Список заказов</h2>
          <span class="counter">{{ orders.length }}</span>
        </div>

        <p v-if="loading" class="muted">Загрузка...</p>
        <p v-else-if="!orders.length" class="muted">Заказов пока нет.</p>

        <button
          v-for="order in orders"
          :key="order.id"
          class="order-item"
          :class="{ active: order.id === form.id }"
          @click="selectOrder(order)"
        >
          <span class="order-title">
            <strong>{{ order.number || 'Без номера' }}</strong>
            <i :class="order.status === 'in_work' ? 'status-work' : 'status-draft'">
              {{ statusLabel(order.status) }}
            </i>
          </span>
          <span>{{ order.name }}</span>
          <small>Обновлён {{ formatDateTime(order.updatedAt) }}</small>
        </button>
      </aside>

      <section class="order-editor card">
        <div class="editor-heading">
          <div>
            <h2>{{ form.id ? 'Карточка заказа' : 'Новый заказ' }}</h2>
            <p class="muted">
              Сначала сохраните карточку, затем загрузите документы.
            </p>
          </div>

          <button
            v-if="form.id && form.permissions.canIssue && form.status !== 'in_work'"
            class="issue-button"
            :disabled="saving || uploading"
            @click="issue"
          >
            Отдать в работу
          </button>
        </div>

        <form class="order-form" @submit.prevent="saveOrder">
          <div class="form-grid">
            <label>
              Номер заказа
              <input
                v-model="form.number"
                type="text"
                placeholder="ЗК-00124"
                :disabled="!canEditForm"
              />
            </label>

            <label>
              Наименование заказа
              <input
                v-model="form.name"
                type="text"
                placeholder="Металлоконструкции секции А"
                :disabled="!canEditForm"
              />
            </label>
          </div>

          <div v-if="canEditForm" class="save-row">
            <button class="primary" type="submit" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить заказ' }}
            </button>
          </div>
        </form>

        <section class="documents">
          <article
            v-for="documentType in documentTypes"
            :key="documentType.value"
            class="document-card"
          >
            <div class="document-header">
              <div>
                <h3>{{ documentType.label }}</h3>
                <p>{{ documentType.description }}</p>
              </div>

              <span class="file-count">
                {{ fileCount(documentType.value) }}
              </span>
            </div>

            <div v-if="!documentPermission(documentType.value).canView" class="restricted">
              Доступ к документам ограничен вашей ролью.
            </div>

            <template v-else>
              <div v-if="documents(documentType.value).length" class="file-list">
                <div
                  v-for="file in documents(documentType.value)"
                  :key="file.id"
                  class="file-row"
                >
                  <button class="file-name" type="button" @click="download(file)">
                    {{ file.name }}
                  </button>

                  <span>{{ formatFileSize(file.size) }}</span>
                  <span>{{ formatDateTime(file.uploadedAt) }}</span>

                  <button
                    v-if="documentPermission(documentType.value).canUpload"
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
                v-if="documentPermission(documentType.value).canUpload"
                class="upload-control"
                :class="{ disabled: !form.id || uploading }"
              >
                <span>{{ form.id ? 'Добавить файлы' : 'Сначала сохраните заказ' }}</span>
                <input
                  type="file"
                  multiple
                  :disabled="!form.id || uploading"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.rtf,.txt,.csv,.zip,.jpg,.jpeg,.png"
                  @change="upload(documentType.value, $event)"
                />
              </label>
            </template>
          </article>
        </section>

        <footer v-if="form.status === 'in_work'" class="issued-info">
          Заказ отдан в работу
          <strong>{{ formatDateTime(form.issuedAt) }}</strong>
          <span v-if="form.issuedBy">— {{ form.issuedBy }}</span>
        </footer>
      </section>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'
import {
  createOrder,
  deleteOrderFile,
  downloadOrderFile,
  getOrders,
  issueOrder,
  updateOrder,
  uploadOrderFiles
} from '../../services/Orders/orders.service.js'

const documentTypes = [
  {
    value: 'km_project',
    label: 'КМ',
    description: 'Проект на изготовление конструкций.'
  },
  {
    value: 'order_calculation',
    label: 'Калькуляция заказа',
    description: 'Расчёт стоимости и экономические материалы.'
  },
  {
    value: 'specification_and_contracts',
    label: 'Заключение спецификации и договоры',
    description: 'Спецификации, договоры и документы отдела продаж.'
  }
]

const orders = ref([])
const canCreate = ref(false)
const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const error = ref('')
const success = ref('')
const form = reactive(emptyOrder())

const canEditForm = computed(() => {
  return !form.id ? canCreate.value : Boolean(form.permissions.canEdit)
})

function emptyOrder() {
  return {
    id: null,
    number: '',
    name: '',
    status: 'draft',
    documents: emptyDocuments(),
    permissions: {
      canEdit: false,
      canIssue: false,
      documents: emptyDocumentPermissions()
    },
    issuedBy: null,
    issuedAt: null,
    updatedAt: null
  }
}

function emptyDocuments() {
  return Object.fromEntries(documentTypes.map(type => [type.value, []]))
}

function emptyDocumentPermissions() {
  return Object.fromEntries(documentTypes.map(type => [
    type.value,
    { canView: false, canUpload: false }
  ]))
}

function normalizeOrder(order = {}) {
  return {
    ...emptyOrder(),
    ...order,
    number: order.number ?? '',
    name: order.name ?? '',
    documents: {
      ...emptyDocuments(),
      ...(order.documents ?? {})
    },
    permissions: {
      ...emptyOrder().permissions,
      ...(order.permissions ?? {}),
      documents: {
        ...emptyDocumentPermissions(),
        ...(order.permissions?.documents ?? {})
      }
    }
  }
}

function setMessage(type, message) {
  error.value = type === 'error' ? message : ''
  success.value = type === 'success' ? message : ''
}

function startCreating() {
  Object.assign(form, emptyOrder())
  form.permissions.canEdit = canCreate.value
  setMessage('', '')
}

function selectOrder(order) {
  Object.assign(form, normalizeOrder(order))
  setMessage('', '')
}

async function loadOrders(selectedId = null) {
  loading.value = true

  try {
    const data = await getOrders()
    orders.value = Array.isArray(data?.orders) ? data.orders.map(normalizeOrder) : []
    canCreate.value = Boolean(data?.canCreate)

    if (selectedId) {
      const selected = orders.value.find(order => order.id === selectedId)

      if (selected) {
        selectOrder(selected)
      }
    } else if (!form.id && orders.value.length && !canCreate.value) {
      selectOrder(orders.value[0])
    }
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось загрузить заказы.'))
  } finally {
    loading.value = false
  }
}

async function saveOrder() {
  if (!form.name.trim()) {
    setMessage('error', 'Укажите наименование заказа.')
    return
  }

  saving.value = true
  setMessage('', '')

  const payload = {
    number: form.number.trim(),
    name: form.name.trim()
  }

  try {
    const saved = form.id
      ? await updateOrder(form.id, payload)
      : await createOrder(payload)

    Object.assign(form, normalizeOrder(saved))
    await loadOrders(saved.id)
    setMessage('success', 'Заказ сохранён.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось сохранить заказ.'))
  } finally {
    saving.value = false
  }
}

async function upload(type, event) {
  const files = Array.from(event.target.files ?? [])
  event.target.value = ''

  if (!form.id || !files.length) {
    return
  }

  uploading.value = true
  setMessage('', '')

  try {
    const updated = await uploadOrderFiles(form.id, type, files)
    Object.assign(form, normalizeOrder(updated))
    await loadOrders(updated.id)
    setMessage('success', 'Файлы загружены.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось загрузить файлы.'))
  } finally {
    uploading.value = false
  }
}

async function removeFile(file) {
  if (!window.confirm(`Удалить файл «${file.name}»?`)) {
    return
  }

  uploading.value = true
  setMessage('', '')

  try {
    const updated = await deleteOrderFile(form.id, file.id)
    Object.assign(form, normalizeOrder(updated))
    await loadOrders(updated.id)
    setMessage('success', 'Файл удалён.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось удалить файл.'))
  } finally {
    uploading.value = false
  }
}

async function download(file) {
  try {
    await downloadOrderFile(file)
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось скачать файл.'))
  }
}

async function issue() {
  if (!window.confirm('Отдать заказ в работу? После этого действие нельзя будет отменить.')) {
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    const updated = await issueOrder(form.id)
    Object.assign(form, normalizeOrder(updated))
    await loadOrders(updated.id)
    setMessage('success', 'Заказ отдан в работу.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось отдать заказ в работу.'))
  } finally {
    saving.value = false
  }
}

function documents(type) {
  return Array.isArray(form.documents[type]) ? form.documents[type] : []
}

function documentPermission(type) {
  return form.permissions.documents[type] ?? { canView: false, canUpload: false }
}

function fileCount(type) {
  if (!documentPermission(type).canView) {
    return '—'
  }

  return documents(type).length
}

function statusLabel(status) {
  return status === 'in_work' ? 'В работе' : 'Черновик'
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

onMounted(loadOrders)
</script>

<style scoped>
.orders-page {
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  padding: 32px;
  background: #f4f6f8;
  color: #17202a;
}

.orders-header,
.header-actions,
.list-header,
.order-title,
.editor-heading,
.save-row,
.document-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.orders-header,
.editor-heading {
  align-items: flex-start;
}

.orders-header {
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
h3,
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

.orders-layout {
  display: grid;
  grid-template-columns: minmax(280px, 340px) minmax(0, 1fr);
  gap: 20px;
}

.card {
  min-width: 0;
  border: 1px solid #e1e7ef;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 12px 30px rgba(18, 38, 63, 0.05);
}

.orders-list,
.order-editor {
  padding: 20px;
}

.orders-list {
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

.order-item {
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

.order-item.active {
  border-color: #2f80ed;
  background: #eef4ff;
}

.order-item span,
.order-item small {
  overflow-wrap: anywhere;
}

.order-item small {
  color: #607080;
}

.order-title i {
  padding: 4px 7px;
  border-radius: 999px;
  font-size: 10px;
  font-style: normal;
}

.status-draft {
  background: #f1f5f9;
  color: #526170;
}

.status-work {
  background: #eaf8ef;
  color: #16703b;
}

.issue-button {
  background: #16703b;
  color: #fff;
  white-space: nowrap;
}

.order-form {
  margin-top: 22px;
}

.form-grid {
  display: grid;
  grid-template-columns: minmax(0, 220px) minmax(0, 1fr);
  gap: 16px;
}

.save-row {
  justify-content: flex-end;
  margin-top: 16px;
}

.documents {
  display: grid;
  gap: 16px;
  margin-top: 28px;
}

.document-card {
  min-width: 0;
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

.restricted {
  margin-top: 14px;
  padding: 12px;
  border-radius: 10px;
  background: #f1f5f9;
  color: #607080;
}

.issued-info {
  margin-top: 22px;
  padding: 14px;
  border-radius: 12px;
  background: #eaf8ef;
  color: #16703b;
}

@media (max-width: 1100px) {
  .orders-layout,
  .form-grid {
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
  .orders-header,
  .editor-heading,
  .document-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    justify-content: flex-start;
  }

  .file-row {
    grid-template-columns: minmax(0, 1fr) 34px;
  }

  .file-row span {
    display: none;
  }
}
</style>
