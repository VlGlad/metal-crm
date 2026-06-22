<template>
  <main class="planning-page">
    <header class="planning-header">
      <div>
        <p class="eyebrow">Второй этап</p>
        <h1>Планирование изготовления на месяц</h1>
        <p class="subtitle">Календарный план, график производства и заявки на материалы.</p>
      </div>

      <div class="header-actions">
        <button class="secondary" :disabled="loading" @click="loadPlans(form.id)">
          Обновить
        </button>
        <button v-if="canCreate" class="primary" @click="startCreating">
          Новое планирование
        </button>
      </div>
    </header>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="planning-layout">
      <aside class="planning-list card">
        <div class="list-header">
          <h2>Планы по месяцам</h2>
          <span class="counter">{{ plans.length }}</span>
        </div>

        <p v-if="loading" class="muted">Загрузка...</p>
        <p v-else-if="!plans.length" class="muted">Планирований пока нет.</p>

        <button
          v-for="plan in plans"
          :key="plan.id"
          class="plan-item"
          :class="{ active: plan.id === form.id }"
          @click="selectPlan(plan)"
        >
          <strong>{{ formatMonth(plan.month) }}</strong>
          <span>{{ plan.name }}</span>
          <small>Обновлено {{ formatDateTime(plan.updatedAt) }}</small>
        </button>
      </aside>

      <section class="planning-editor card">
        <div class="editor-heading">
          <div>
            <h2>{{ form.id ? 'Планирование на месяц' : 'Новое планирование' }}</h2>
            <p class="muted">Сохраните основные данные, затем добавьте документы.</p>
          </div>
        </div>

        <form class="planning-form" @submit.prevent="savePlan">
          <div class="form-grid">
            <label>
              Месяц
              <input v-model="form.month" type="month" :disabled="!canEditForm" />
            </label>

            <label>
              Название
              <input
                v-model="form.name"
                type="text"
                placeholder="Производственный план на июнь"
                :disabled="!canEditForm"
              />
            </label>
          </div>

          <div v-if="canEditForm" class="save-row">
            <button class="primary" type="submit" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить планирование' }}
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
              <span class="file-count">{{ fileCount(documentType.value) }}</span>
            </div>

            <div v-if="!permission(documentType.value).canView" class="restricted">
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
                    v-if="permission(documentType.value).canUpload"
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
                v-if="permission(documentType.value).canUpload"
                class="upload-control"
                :class="{ disabled: !form.id || uploading }"
              >
                <span>{{ form.id ? 'Добавить файлы' : 'Сначала сохраните планирование' }}</span>
                <input
                  type="file"
                  multiple
                  :disabled="!form.id || uploading"
                  @change="upload(documentType.value, $event)"
                />
              </label>
            </template>
          </article>
        </section>
      </section>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'
import {
  createMonthlyPlan,
  deleteMonthlyPlanFile,
  downloadMonthlyPlanFile,
  getMonthlyPlans,
  updateMonthlyPlan,
  uploadMonthlyPlanFiles
} from '../../services/MonthlyPlans/monthly-plans.service.js'

const documentTypes = [
  {
    value: 'production_plan',
    label: 'Календарный план',
    description: 'Календарный план производства и отгрузки: план и факт.'
  },
  {
    value: 'production_schedule',
    label: 'График производства',
    description: 'Общий график производства или график по заказу.'
  },
  {
    value: 'material_request',
    label: 'Заявка на материалы',
    description: 'Заявки на металл, комплектующие, сварочные материалы и ЛКМ.'
  }
]

const plans = ref([])
const canCreate = ref(false)
const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const error = ref('')
const success = ref('')
const form = reactive(emptyPlan())

const canEditForm = computed(() => {
  return !form.id ? canCreate.value : Boolean(form.permissions.canEdit)
})

function currentMonth() {
  return new Date().toISOString().slice(0, 7)
}

function emptyPlan() {
  return {
    id: null,
    month: currentMonth(),
    name: '',
    documents: emptyDocuments(),
    permissions: {
      canEdit: false,
      documents: emptyPermissions()
    },
    updatedAt: null
  }
}

function emptyDocuments() {
  return Object.fromEntries(documentTypes.map(type => [type.value, []]))
}

function emptyPermissions() {
  return Object.fromEntries(documentTypes.map(type => [
    type.value,
    { canView: false, canUpload: false }
  ]))
}

function normalizePlan(plan = {}) {
  return {
    ...emptyPlan(),
    ...plan,
    documents: {
      ...emptyDocuments(),
      ...(plan.documents ?? {})
    },
    permissions: {
      ...emptyPlan().permissions,
      ...(plan.permissions ?? {}),
      documents: {
        ...emptyPermissions(),
        ...(plan.permissions?.documents ?? {})
      }
    }
  }
}

function setMessage(type, message) {
  error.value = type === 'error' ? message : ''
  success.value = type === 'success' ? message : ''
}

function startCreating() {
  Object.assign(form, emptyPlan())
  form.permissions.canEdit = canCreate.value
  setMessage('', '')
}

function selectPlan(plan) {
  Object.assign(form, normalizePlan(plan))
  setMessage('', '')
}

async function loadPlans(selectedId = null) {
  loading.value = true

  try {
    const data = await getMonthlyPlans()
    plans.value = Array.isArray(data?.plans) ? data.plans.map(normalizePlan) : []
    canCreate.value = Boolean(data?.canCreate)

    if (selectedId) {
      const selected = plans.value.find(plan => plan.id === selectedId)
      if (selected) selectPlan(selected)
    } else if (!form.id && plans.value.length && !canCreate.value) {
      selectPlan(plans.value[0])
    }
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось загрузить планирования.'))
  } finally {
    loading.value = false
  }
}

async function savePlan() {
  if (!form.month) {
    setMessage('error', 'Укажите месяц.')
    return
  }

  if (!form.name.trim()) {
    setMessage('error', 'Укажите название планирования.')
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    const payload = { month: form.month, name: form.name.trim() }
    const saved = form.id
      ? await updateMonthlyPlan(form.id, payload)
      : await createMonthlyPlan(payload)

    Object.assign(form, normalizePlan(saved))
    await loadPlans(saved.id)
    setMessage('success', 'Планирование сохранено.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось сохранить планирование.'))
  } finally {
    saving.value = false
  }
}

async function upload(type, event) {
  const files = Array.from(event.target.files ?? [])
  event.target.value = ''

  if (!form.id || !files.length) return

  uploading.value = true
  setMessage('', '')

  try {
    const updated = await uploadMonthlyPlanFiles(form.id, type, files)
    Object.assign(form, normalizePlan(updated))
    await loadPlans(updated.id)
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
    const updated = await deleteMonthlyPlanFile(form.id, file.id)
    Object.assign(form, normalizePlan(updated))
    await loadPlans(updated.id)
    setMessage('success', 'Файл удалён.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось удалить файл.'))
  } finally {
    uploading.value = false
  }
}

async function download(file) {
  try {
    await downloadMonthlyPlanFile(file)
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось скачать файл.'))
  }
}

function documents(type) {
  return Array.isArray(form.documents[type]) ? form.documents[type] : []
}

function permission(type) {
  return form.permissions.documents[type] ?? { canView: false, canUpload: false }
}

function fileCount(type) {
  return permission(type).canView ? documents(type).length : '—'
}

function formatMonth(value) {
  if (!value) return 'Без месяца'
  const [year, month] = value.split('-')
  return new Intl.DateTimeFormat('ru-RU', { month: 'long', year: 'numeric' })
    .format(new Date(Number(year), Number(month) - 1, 1))
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

onMounted(loadPlans)
</script>

<style scoped>
.planning-page {
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  padding: 32px;
  background: #f4f6f8;
  color: #17202a;
}

.planning-header,
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

.planning-header,
.editor-heading {
  align-items: flex-start;
}

.planning-header {
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

.planning-layout {
  display: grid;
  grid-template-columns: minmax(280px, 340px) minmax(0, 1fr);
  gap: 20px;
}

.card {
  min-width: 0;
  border: 1px solid #e1e7ef;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(18, 38, 63, 0.05);
}

.planning-list,
.planning-editor {
  padding: 20px;
}

.planning-list {
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

.plan-item {
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

.plan-item.active {
  border-color: #2f80ed;
  background: #eef4ff;
}

.plan-item span,
.plan-item small {
  overflow-wrap: anywhere;
}

.plan-item small {
  color: #607080;
}

.planning-form {
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

@media (max-width: 1100px) {
  .planning-layout,
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
  .planning-header,
  .editor-heading,
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
