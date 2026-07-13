<template>
  <main class="progress-page">
    <header class="progress-header">
      <div>
        <h1>Производство</h1>
        <p class="subtitle">Единое поле: выполнение мастером, контроль ОТК и готовность изделия.</p>
      </div>

      <button class="secondary" :disabled="loading" @click="loadProgress">
        Обновить
      </button>
    </header>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="card progress-table-card">
      <div class="table-toolbar">
        <label>
          Поиск
          <input v-model="query" type="search" placeholder="Заказ, цех, марка, участок" />
        </label>
        <span class="counter">{{ filteredItems.length }}</span>
      </div>

      <p v-if="loading" class="muted">Загрузка...</p>
      <p v-else-if="!filteredItems.length" class="muted">Данных пока нет.</p>

      <div v-else class="progress-table-wrap">
        <table class="progress-table">
          <thead>
            <tr>
              <th>Задание</th>
              <th>Позиция</th>
              <th>Мастер</th>
              <th>ОТК</th>
              <th>Готовность</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <template v-for="item in filteredItems" :key="item.id">
              <tr>
                <td>
                  <strong>{{ item.task.title }}</strong>
                  <span>{{ formatDate(item.task.date) }} · {{ item.task.workshop }}</span>
                </td>
                <td>
                  <strong>{{ item.master.mark }}</strong>
                  <span>{{ item.section.name }}</span>
                </td>
                <td>
                  <span>План: {{ item.master.planQuantity }}</span>
                  <span>Факт: {{ item.master.factQuantity }}</span>
                  <i :class="['status-badge', `master-${item.master.status}`]">
                    {{ masterStatusLabel(item.master.status) }}
                  </i>
                </td>
                <td>
                  <span>Предъявлено: {{ item.otk?.presentedQuantity ?? 0 }}</span>
                  <span>Принято: {{ item.otk?.acceptedQuantity ?? 0 }}</span>
                  <span>Брак: {{ item.otk?.rejectedQuantity ?? 0 }}</span>
                  <i :class="['status-badge', `otk-${item.otk?.status || 'draft'}`]">
                    {{ otkStatusLabel(item.otk?.status) }}
                  </i>
                </td>
                <td>
                  <i :class="['readiness', `ready-${item.readiness}`]">
                    {{ readinessLabel(item.readiness) }}
                  </i>
                </td>
                <td>
                  <button class="secondary small" type="button" @click="toggleDetails(item)">
                    {{ openedId === item.id ? 'Скрыть' : 'Открыть' }}
                  </button>
                </td>
              </tr>
              <tr v-if="openedId === item.id" class="details-row">
                <td colspan="6">
                  <div class="details-grid">
                    <article>
                      <h3>Что сделал мастер</h3>
                      <dl>
                        <div><dt>1 смена</dt><dd>{{ item.master.firstShiftFact ?? 0 }} / {{ item.master.firstShiftPlan ?? 0 }}</dd></div>
                        <div><dt>2 смена</dt><dd>{{ item.master.secondShiftFact ?? 0 }} / {{ item.master.secondShiftPlan ?? 0 }}</dd></div>
                        <div><dt>Комментарий</dt><dd>{{ item.master.note || '—' }}</dd></div>
                      </dl>
                    </article>

                    <article>
                      <h3>Контроль ОТК</h3>
                      <div class="otk-form">
                        <label>
                          Предъявлено
                          <input v-model.number="otkForms[item.id].presentedQuantity" type="number" min="0" :disabled="!permissions.canEditOtk" />
                        </label>
                        <label>
                          Принято
                          <input v-model.number="otkForms[item.id].acceptedQuantity" type="number" min="0" :disabled="!permissions.canEditOtk" />
                        </label>
                        <label>
                          Брак
                          <input v-model.number="otkForms[item.id].rejectedQuantity" type="number" min="0" :disabled="!permissions.canEditOtk" />
                        </label>
                        <label>
                          Статус ОТК
                          <select v-model="otkForms[item.id].status" :disabled="!permissions.canEditOtk">
                            <option value="draft">Ожидает проверки</option>
                            <option value="accepted">Принято</option>
                            <option value="rejected">Не принято</option>
                          </select>
                        </label>
                        <label class="wide">
                          Описание несоответствия
                          <textarea v-model="otkForms[item.id].nonconformityDescription" rows="3" :disabled="!permissions.canEditOtk"></textarea>
                        </label>
                        <label>
                          № акта
                          <input v-model="otkForms[item.id].nonconformityActNumber" type="text" :disabled="!permissions.canEditOtk" />
                        </label>
                        <label>
                          Контролёр
                          <input v-model="otkForms[item.id].controllerName" type="text" :disabled="!permissions.canEditOtk" />
                        </label>
                      </div>

                      <div v-if="permissions.canEditOtk" class="actions-row">
                        <button class="primary" type="button" :disabled="savingId === item.id" @click="saveOtk(item)">
                          {{ savingId === item.id ? 'Сохранение...' : 'Сохранить ОТК' }}
                        </button>
                      </div>
                    </article>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'
import { getProductionProgress, saveProductionProgressOtk } from '../../services/ProductionProgress/production-progress.service.js'

const items = ref([])
const permissions = reactive({ canEditMaster: false, canEditOtk: false })
const otkForms = reactive({})
const loading = ref(false)
const savingId = ref(null)
const openedId = ref(null)
const query = ref('')
const error = ref('')
const success = ref('')

const filteredItems = computed(() => {
  const needle = query.value.trim().toLowerCase()
  if (!needle) return items.value

  return items.value.filter(item => [
    item.task.title,
    item.task.workshop,
    item.section.name,
    item.master.mark,
    item.master.note
  ].some(value => String(value ?? '').toLowerCase().includes(needle)))
})

function normalizeItem(item = {}) {
  return {
    id: item.id,
    task: item.task ?? {},
    section: item.section ?? {},
    master: item.master ?? {},
    otk: item.otk ?? null,
    readiness: item.readiness ?? 'in_work'
  }
}

function ensureOtkForm(item) {
  otkForms[item.id] = {
    presentedQuantity: item.otk?.presentedQuantity ?? item.master.factQuantity ?? 0,
    acceptedQuantity: item.otk?.acceptedQuantity ?? 0,
    rejectedQuantity: item.otk?.rejectedQuantity ?? 0,
    status: item.otk?.status ?? 'draft',
    nonconformityDescription: item.otk?.nonconformityDescription ?? '',
    nonconformityActNumber: item.otk?.nonconformityActNumber ?? '',
    controllerName: item.otk?.controllerName ?? ''
  }
}

async function loadProgress() {
  loading.value = true
  error.value = ''

  try {
    const data = await getProductionProgress()
    items.value = Array.isArray(data?.items) ? data.items.map(normalizeItem) : []
    Object.assign(permissions, data?.permissions ?? {})
    for (const item of items.value) ensureOtkForm(item)
  } catch (exception) {
    error.value = errorMessage(exception, 'Не удалось загрузить производственный прогресс.')
  } finally {
    loading.value = false
  }
}

function toggleDetails(item) {
  ensureOtkForm(item)
  openedId.value = openedId.value === item.id ? null : item.id
  success.value = ''
  error.value = ''
}

async function saveOtk(item) {
  savingId.value = item.id
  success.value = ''
  error.value = ''

  try {
    const updated = await saveProductionProgressOtk(item.id, { ...otkForms[item.id] })
    const normalized = normalizeItem(updated)
    const index = items.value.findIndex(row => row.id === item.id)
    if (index !== -1) items.value[index] = normalized
    ensureOtkForm(normalized)
    success.value = 'Результат ОТК сохранён.'
  } catch (exception) {
    error.value = errorMessage(exception, 'Не удалось сохранить результат ОТК.')
  } finally {
    savingId.value = null
  }
}

function masterStatusLabel(status) {
  return {
    not_started: 'Не начато',
    in_progress: 'В работе',
    done: 'Выполнено'
  }[status] || status || '—'
}

function otkStatusLabel(status) {
  return {
    draft: 'Ожидает проверки',
    accepted: 'Принято',
    rejected: 'Не принято'
  }[status || 'draft'] || status || '—'
}

function readinessLabel(status) {
  return {
    in_work: 'В работе',
    waiting_otk: 'Ожидает ОТК',
    ready: 'Готово',
    rework_required: 'Требуется доработка'
  }[status] || status || '—'
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('ru-RU')
}

function errorMessage(exception, fallback) {
  return exception?.response?.data?.message || exception?.message || fallback
}

onMounted(loadProgress)
</script>

<style scoped>
.progress-page {
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  padding: 32px;
  background: #f4f6f8;
  color: #17202a;
}

.progress-header,
.header-actions,
.table-toolbar,
.actions-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.progress-header {
  align-items: flex-start;
  margin-bottom: 24px;
}

.header-actions {
  justify-content: flex-end;
}

.link-button {
  display: inline-flex;
  align-items: center;
  width: auto;
  text-decoration: none;
}

h1, h3, p {
  margin-top: 0;
}

.subtitle,
.muted {
  color: #607080;
}

.card {
  min-width: 0;
  padding: 20px;
  border: 1px solid #e1e7ef;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(18, 38, 63, 0.05);
}

.table-toolbar {
  margin-bottom: 16px;
}

.table-toolbar label {
  max-width: 360px;
}

.counter {
  padding: 4px 8px;
  border-radius: 999px;
  background: #eef4ff;
  color: #1f63b6;
  font-size: 12px;
  font-weight: 800;
}

.progress-table-wrap {
  overflow-x: auto;
}

.progress-table {
  width: 100%;
  min-width: 980px;
  border-collapse: collapse;
}

.progress-table th,
.progress-table td {
  padding: 12px;
  border-bottom: 1px solid #e1e7ef;
  text-align: left;
  vertical-align: top;
}

.progress-table th {
  color: #607080;
  font-size: 12px;
  text-transform: uppercase;
}

.progress-table td {
  font-size: 13px;
}

.progress-table td span,
.progress-table td strong {
  display: block;
  overflow-wrap: anywhere;
}

.status-badge,
.readiness {
  display: inline-flex;
  width: max-content;
  margin-top: 6px;
  padding: 5px 8px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #526170;
  font-size: 11px;
  font-style: normal;
  font-weight: 800;
  white-space: nowrap;
}

.master-done,
.otk-accepted,
.ready-ready {
  background: #eaf8ef;
  color: #16703b;
}

.master-in_progress,
.ready-waiting_otk {
  background: #fff7e6;
  color: #9a5b00;
}

.otk-rejected,
.ready-rework_required {
  background: #fff0f0;
  color: #b42318;
}

.details-row td {
  background: #fbfcfe;
}

.details-grid {
  display: grid;
  grid-template-columns: minmax(260px, 0.8fr) minmax(0, 1.2fr);
  gap: 18px;
}

.details-grid article {
  min-width: 0;
  padding: 16px;
  border: 1px solid #e1e7ef;
  border-radius: 14px;
  background: #fff;
}

dl {
  display: grid;
  gap: 10px;
  margin: 0;
}

dl div {
  display: grid;
  grid-template-columns: 120px minmax(0, 1fr);
  gap: 10px;
}

dt {
  color: #607080;
}

dd {
  margin: 0;
  overflow-wrap: anywhere;
}

.otk-form {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.otk-form .wide {
  grid-column: 1 / -1;
}

.actions-row {
  justify-content: flex-end;
  margin-top: 14px;
}

.small {
  width: auto;
  padding: 8px 11px;
}

@media (max-width: 1100px) {
  .details-grid,
  .otk-form {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .progress-header,
  .table-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
