<template>
  <main class="working-page">
    <header class="working-header">
      <div>
        <p class="eyebrow">Третий этап</p>
        <h1>Рабочие документы и чертежи</h1>
        <p class="subtitle">Чертежи раскроя и управляющие программы для производства.</p>
      </div>

      <div class="header-actions">
        <button class="secondary" :disabled="loading" @click="loadPackages(form.id)">
          Обновить
        </button>
        <button v-if="canCreate" class="primary" @click="startCreating">
          Новый комплект
        </button>
      </div>
    </header>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="working-layout">
      <aside class="package-list card">
        <div class="list-header">
          <h2>Комплекты документов</h2>
          <span class="counter">{{ packages.length }}</span>
        </div>

        <p v-if="loading" class="muted">Загрузка...</p>
        <p v-else-if="!packages.length" class="muted">Комплектов пока нет.</p>

        <button
          v-for="item in packages"
          :key="item.id"
          class="package-item"
          :class="{ active: item.id === form.id }"
          @click="selectPackage(item)"
        >
          <strong>{{ item.name }}</strong>
          <span>{{ monthlyPlanLabel(item.monthlyPlan) }}</span>
          <small>{{ item.files.length }} файл(ов)</small>
        </button>
      </aside>

      <section class="package-editor card">
        <div class="editor-heading">
          <div>
            <h2>{{ form.id ? 'Комплект рабочих документов' : 'Новый комплект' }}</h2>
            <p class="muted">Свяжите документы с планированием на конкретный месяц.</p>
          </div>
        </div>

        <form class="package-form" @submit.prevent="savePackage">
          <div class="form-grid">
            <label>
              Месячное планирование
              <select v-model.number="form.monthlyPlanId" :disabled="!canEditForm">
                <option :value="null" disabled>Выберите планирование</option>
                <option
                  v-for="plan in monthlyPlans"
                  :key="plan.id"
                  :value="plan.id"
                >
                  {{ monthlyPlanLabel(plan) }}
                </option>
              </select>
            </label>

            <label>
              Название комплекта
              <input
                v-model="form.name"
                type="text"
                placeholder="Чертежи и программы по заказу"
                :disabled="!canEditForm"
              />
            </label>
          </div>

          <div v-if="canEditForm" class="save-row">
            <button class="primary" type="submit" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить комплект' }}
            </button>
          </div>
        </form>

        <article class="document-card">
          <div class="document-header">
            <div>
              <h3>Чертежи раскроя и управляющие программы</h3>
              <p>Рабочие документы, подготовленные в соответствии с графиком производства.</p>
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
            <span>{{ form.id ? 'Добавить файлы' : 'Сначала сохраните комплект' }}</span>
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
import {
  createWorkingDocument,
  deleteWorkingDocumentFile,
  downloadWorkingDocumentFile,
  getWorkingDocuments,
  updateWorkingDocument,
  uploadWorkingDocumentFiles
} from '../../services/WorkingDocuments/working-documents.service.js'

const packages = ref([])
const monthlyPlans = ref([])
const canCreate = ref(false)
const loading = ref(false)
const saving = ref(false)
const uploading = ref(false)
const error = ref('')
const success = ref('')
const form = reactive(emptyPackage())

const canEditForm = computed(() => {
  return !form.id ? canCreate.value : Boolean(form.permissions.canEdit)
})

function emptyPackage() {
  return {
    id: null,
    name: '',
    monthlyPlanId: null,
    monthlyPlan: null,
    files: [],
    permissions: { canEdit: false },
    updatedAt: null
  }
}

function normalizePackage(item = {}) {
  return {
    ...emptyPackage(),
    ...item,
    monthlyPlanId: item.monthlyPlan?.id ?? null,
    files: Array.isArray(item.files) ? item.files : [],
    permissions: {
      ...emptyPackage().permissions,
      ...(item.permissions ?? {})
    }
  }
}

function setMessage(type, message) {
  error.value = type === 'error' ? message : ''
  success.value = type === 'success' ? message : ''
}

function startCreating() {
  Object.assign(form, emptyPackage())
  form.permissions.canEdit = canCreate.value
  setMessage('', '')
}

function selectPackage(item) {
  Object.assign(form, normalizePackage(item))
  setMessage('', '')
}

async function loadPackages(selectedId = null) {
  loading.value = true

  try {
    const data = await getWorkingDocuments()
    packages.value = Array.isArray(data?.packages) ? data.packages.map(normalizePackage) : []
    monthlyPlans.value = Array.isArray(data?.monthlyPlans) ? data.monthlyPlans : []
    canCreate.value = Boolean(data?.canCreate)

    if (selectedId) {
      const selected = packages.value.find(item => item.id === selectedId)
      if (selected) selectPackage(selected)
    }
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось загрузить рабочие документы.'))
  } finally {
    loading.value = false
  }
}

async function savePackage() {
  if (!form.monthlyPlanId) {
    setMessage('error', 'Выберите месячное планирование.')
    return
  }

  if (!form.name.trim()) {
    setMessage('error', 'Укажите название комплекта.')
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    const payload = {
      monthlyPlanId: form.monthlyPlanId,
      name: form.name.trim()
    }

    const saved = form.id
      ? await updateWorkingDocument(form.id, payload)
      : await createWorkingDocument(payload)

    Object.assign(form, normalizePackage(saved))
    await loadPackages(saved.id)
    setMessage('success', 'Комплект документов сохранён.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось сохранить комплект.'))
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
    const updated = await uploadWorkingDocumentFiles(form.id, files)
    Object.assign(form, normalizePackage(updated))
    await loadPackages(updated.id)
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
    const updated = await deleteWorkingDocumentFile(form.id, file.id)
    Object.assign(form, normalizePackage(updated))
    await loadPackages(updated.id)
    setMessage('success', 'Файл удалён.')
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось удалить файл.'))
  } finally {
    uploading.value = false
  }
}

async function download(file) {
  try {
    await downloadWorkingDocumentFile(file)
  } catch (exception) {
    setMessage('error', errorMessage(exception, 'Не удалось скачать файл.'))
  }
}

function monthlyPlanLabel(plan) {
  if (!plan) return 'Без планирования'

  const [year, month] = plan.month.split('-')
  const formattedMonth = new Intl.DateTimeFormat('ru-RU', { month: 'long', year: 'numeric' })
    .format(new Date(Number(year), Number(month) - 1, 1))

  return `${formattedMonth} — ${plan.name}`
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

onMounted(loadPackages)
</script>

<style scoped>
.working-page {
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  padding: 32px;
  background: #f4f6f8;
  color: #17202a;
}

.working-header,
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

.working-header,
.editor-heading {
  align-items: flex-start;
}

.working-header {
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

.working-layout {
  display: grid;
  grid-template-columns: minmax(280px, 340px) minmax(0, 1fr);
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

.package-list {
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

.package-item {
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

.package-item.active {
  border-color: #2f80ed;
  background: #eef4ff;
}

.package-item span,
.package-item small {
  overflow-wrap: anywhere;
}

.package-item small {
  color: #607080;
}

.package-form {
  margin-top: 22px;
}

.form-grid {
  display: grid;
  grid-template-columns: minmax(260px, 0.8fr) minmax(0, 1.2fr);
  gap: 16px;
}

.save-row {
  justify-content: flex-end;
  margin-top: 16px;
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
  .working-layout,
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
  .working-header,
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
