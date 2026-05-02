<template>
  <section>
    <section class="otk-header">
      <div>
        <p class="eyebrow">Контролеры ОТК</p>
        <h1>Журнал предъявления ОТК</h1>
      </div>

      <div class="actions">
        <button class="secondary" :disabled="loading" @click="loadItems">
          Обновить
        </button>

        <button class="primary" :disabled="saving" @click="saveItem">
          {{ saving ? 'Сохранение...' : 'Сохранить' }}
        </button>
      </div>
    </section>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="otk-layout">
      <aside class="card otk-list">
        <div class="list-title-row">
          <h2>Записи</h2>

          <button class="link" @click="createNewItem">
            + Новая
          </button>
        </div>

        <p v-if="loading" class="muted">Загрузка...</p>

        <p v-else-if="!items.length" class="empty">
          Записей пока нет.
        </p>

        <button
          v-for="item in items"
          :key="item.id"
          class="otk-list-item"
          :class="{ active: item.id === form.id }"
          @click="selectItem(item)"
        >
          <strong>{{ item.name || 'Без наименования' }}</strong>
          <span>{{ formatDate(item.date) }} · {{ item.project || 'Без проекта' }}</span>
          <small>{{ getStatusLabel(item.status) }}</small>
        </button>
      </aside>

      <section class="card otk-editor">
        <div class="card-title-row">
          <div>
            <h2>{{ form.id ? 'Редактирование записи' : 'Новая запись ОТК' }}</h2>
            <p class="muted">
              Заполните данные предъявления, приемки и брака.
            </p>
          </div>

          <button
            v-if="form.id"
            class="danger"
            :disabled="saving"
            @click="removeItem"
          >
            Удалить
          </button>
        </div>

        <div class="form-grid">
          <label>
            Дата
            <input v-model="form.date" type="date" />
          </label>

          <label>
            Статус
            <select v-model="form.status">
              <option value="draft">Черновик</option>
              <option value="submitted">Предъявлено ОТК</option>
              <option value="accepted">Принято</option>
              <option value="rejected">Есть замечания</option>
              <option value="closed">Закрыто</option>
            </select>
          </label>

          <label>
            Наименование
            <input
              v-model="form.name"
              type="text"
              placeholder="Балка Б-1"
            />
          </label>

          <label>
            Проект
            <input
              v-model="form.project"
              type="text"
              placeholder="Проект 123"
            />
          </label>

          <label>
            Предъявлено, шт.
            <input
              v-model.number="form.presentedQuantity"
              type="number"
              min="0"
            />
          </label>

          <label>
            Принято, шт.
            <input
              v-model.number="form.acceptedQuantity"
              type="number"
              min="0"
            />
          </label>

          <label>
            Забраковано, шт.
            <input
              v-model.number="form.rejectedQuantity"
              type="number"
              min="0"
            />
          </label>

          <label>
            Номер акта
            <input
              v-model="form.nonconformityActNumber"
              type="text"
              placeholder="АКТ-15"
            />
          </label>
        </div>

        <label class="full-field">
          Описание несоответствия
          <textarea
            v-model="form.nonconformityDescription"
            rows="4"
            placeholder="Опишите выявленное несоответствие"
          />
        </label>

        <section class="signatures">
          <div class="signature-card">
            <div>
              <h3>Исполнитель</h3>
              <p v-if="form.executorSignedAt" class="signed">
                Подписано: {{ formatDateTime(form.executorSignedAt) }}
              </p>
              <p v-else class="muted">
                Подпись не поставлена
              </p>
            </div>

            <label>
              ФИО исполнителя
              <input
                v-model="form.executorName"
                type="text"
                placeholder="Иванов И.И."
              />
            </label>

            <button
              class="secondary"
              :disabled="!form.id || saving || Boolean(form.executorSignedAt)"
              @click="signExecutor"
            >
              Подписать исполнителем
            </button>
          </div>

          <div class="signature-card">
            <div>
              <h3>Контролер ОТК</h3>
              <p v-if="form.controllerSignedAt" class="signed">
                Подписано: {{ formatDateTime(form.controllerSignedAt) }}
              </p>
              <p v-else class="muted">
                Подпись не поставлена
              </p>
            </div>

            <label>
              ФИО контролера
              <input
                v-model="form.controllerName"
                type="text"
                placeholder="Петров П.П."
              />
            </label>

            <button
              class="secondary"
              :disabled="!form.id || saving || Boolean(form.controllerSignedAt)"
              @click="signController"
            >
              Подписать контролером
            </button>
          </div>
        </section>
      </section>
    </section>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'
import {
  createOtkInspection,
  deleteOtkInspection,
  getOtkInspections,
  signOtkController,
  signOtkExecutor,
  updateOtkInspection
} from '../../services/OtkControllers/otk-inspections.service'
import {
  emptyOtkInspection,
  normalizeOtkInspection,
  payloadFromOtkInspection
} from '../../utils/OtkControllers/otkInspectionFactory'
import { validateOtkInspection } from '../../utils/OtkControllers/otkInspectionValidation'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const items = ref([])

const form = reactive(emptyOtkInspection())

function setMessage(type, text) {
  error.value = ''
  success.value = ''

  if (type === 'error') error.value = text
  if (type === 'success') success.value = text
}

function replaceForm(item) {
  Object.assign(form, normalizeOtkInspection(item))
}

function createNewItem() {
  replaceForm(emptyOtkInspection())
  setMessage('success', 'Создана новая запись ОТК.')
}

function selectItem(item) {
  replaceForm(item)
  setMessage('', '')
}

async function loadItems() {
  loading.value = true
  setMessage('', '')

  try {
    const data = await getOtkInspections()
    items.value = Array.isArray(data) ? data.map(normalizeOtkInspection) : []
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось загрузить записи ОТК.'))
  } finally {
    loading.value = false
  }
}

async function saveItem() {
  const validationError = validateOtkInspection(form)

  if (validationError) {
    setMessage('error', validationError)
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    const payload = payloadFromOtkInspection(form)

    const data = form.id
      ? await updateOtkInspection(form.id, payload)
      : await createOtkInspection(payload)

    replaceForm(data)
    setMessage('success', 'Запись ОТК сохранена.')
    await loadItems()
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось сохранить запись ОТК.'))
  } finally {
    saving.value = false
  }
}

async function removeItem() {
  if (!form.id) {
    return
  }

  const confirmed = window.confirm('Удалить запись ОТК?')

  if (!confirmed) {
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    await deleteOtkInspection(form.id)
    replaceForm(emptyOtkInspection())
    setMessage('success', 'Запись удалена.')
    await loadItems()
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось удалить запись ОТК.'))
  } finally {
    saving.value = false
  }
}

async function signExecutor() {
  if (!form.id) {
    setMessage('error', 'Сначала сохраните запись.')
    return
  }

  if (!String(form.executorName ?? '').trim()) {
    setMessage('error', 'Укажите исполнителя.')
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    const data = await signOtkExecutor(form.id, form.executorName)
    replaceForm(data)
    setMessage('success', 'Исполнитель подписал запись.')
    await loadItems()
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось подписать исполнителем.'))
  } finally {
    saving.value = false
  }
}

async function signController() {
  if (!form.id) {
    setMessage('error', 'Сначала сохраните запись.')
    return
  }

  if (!String(form.controllerName ?? '').trim()) {
    setMessage('error', 'Укажите контролера ОТК.')
    return
  }

  saving.value = true
  setMessage('', '')

  try {
    const data = await signOtkController(form.id, form.controllerName)
    replaceForm(data)
    setMessage('success', 'Контролер ОТК подписал запись.')
    await loadItems()
  } catch (e) {
    setMessage('error', getErrorMessage(e, 'Не удалось подписать контролером.'))
  } finally {
    saving.value = false
  }
}

function getStatusLabel(status) {
  const labels = {
    draft: 'Черновик',
    submitted: 'Предъявлено ОТК',
    accepted: 'Принято',
    rejected: 'Есть замечания',
    closed: 'Закрыто'
  }

  return labels[status] || status
}

function formatDate(value) {
  if (!value) {
    return 'Без даты'
  }

  return new Date(value).toLocaleDateString('ru-RU')
}

function formatDateTime(value) {
  if (!value) {
    return ''
  }

  return new Date(value).toLocaleString('ru-RU')
}

function getErrorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

onMounted(loadItems)
</script>

<style scoped>
.otk-header {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: start;
  gap: 24px;
  margin-bottom: 24px;
}

.eyebrow {
  margin: 0 0 6px;
  font-size: 13px;
  font-weight: 700;
  color: #2f80ed;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

h1,
h2,
h3,
p {
  margin-top: 0;
}

h1 {
  margin-bottom: 0;
  font-size: 30px;
  line-height: 1.15;
}

h2 {
  margin-bottom: 4px;
  color: #17202a;
}

h3 {
  margin-bottom: 8px;
  color: #17202a;
}

.actions {
  display: flex;
  gap: 12px;
}

.otk-layout {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  gap: 20px;
}

.otk-list {
  align-self: start;
  padding: 18px;
}

.list-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.otk-list-item {
  width: 100%;
  display: grid;
  gap: 4px;
  margin-top: 10px;
  padding: 12px;
  text-align: left;
  background: #f8fafc;
  border: 1px solid #edf1f5;
  color: #17202a;
}

.otk-list-item strong {
  color: #17202a;
}

.otk-list-item span,
.otk-list-item small {
  color: #607080;
}

.otk-list-item.active {
  border-color: #2f80ed;
  background: #eef4ff;
}

.otk-editor {
  padding: 20px;
}

.full-field {
  margin-top: 16px;
}

.signatures {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-top: 22px;
}

.signature-card {
  display: grid;
  gap: 14px;
  padding: 16px;
  border: 1px solid #e1e7ef;
  border-radius: 16px;
  background: #f8fafc;
}

.signed {
  margin-bottom: 0;
  color: #188038;
  font-weight: 700;
}

@media (max-width: 1024px) {
  .otk-header,
  .otk-layout,
  .signatures {
    grid-template-columns: 1fr;
  }

  .actions {
    justify-content: flex-start;
  }
}
</style>