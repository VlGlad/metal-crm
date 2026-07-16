<template>
  <main class="assignments-page">
    <header class="page-header">
      <div>
        <h1>Поручения</h1>
        <p class="subtitle">Постановка поручений, контроль сроков и месячный анализ выполнения.</p>
      </div>
      <div class="header-actions">
        <label class="month-filter">Месяц отчёта<input v-model="month" type="month" @change="loadData" /></label>
        <button class="secondary" :disabled="loading" @click="loadData">Обновить</button>
        <button class="primary" @click="startCreating">Новое поручение</button>
      </div>
    </header>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="report-grid">
      <article class="metric"><span>Всего</span><strong>{{ report.total.total }}</strong></article>
      <article class="metric good"><span>Выполнено</span><strong>{{ report.total.completed }}</strong></article>
      <article class="metric"><span>В срок</span><strong>{{ report.total.completedInTime }}</strong></article>
      <article class="metric warn"><span>Не выполнено</span><strong>{{ report.total.notCompleted }}</strong></article>
      <article class="metric danger"><span>Просрочено</span><strong>{{ report.total.overdue }}</strong></article>
      <article class="metric"><span>% выполнения</span><strong>{{ report.total.completionRate }}%</strong></article>
    </section>

    <section class="layout">
      <aside class="card list">
        <div class="list-head"><h2>Список</h2><span class="counter">{{ filteredAssignments.length }}</span></div>
        <label>Фильтр
          <select v-model="filter">
            <option value="all">Все</option>
            <option value="assigned">Назначено</option>
            <option value="in_progress">В работе</option>
            <option value="overdue">Просрочено</option>
            <option value="completed">Выполнено</option>
          </select>
        </label>
        <p v-if="loading" class="muted">Загрузка...</p>
        <p v-else-if="!filteredAssignments.length" class="muted">Поручений пока нет.</p>
        <button v-for="item in filteredAssignments" :key="item.id" class="list-item" :class="{ active: item.id === form.id }" @click="selectAssignment(item)">
          <strong>{{ item.title }}</strong>
          <span>{{ item.responsibleName || 'Без ответственного' }}</span>
          <small>Срок: {{ formatDate(item.dueDate) }}</small>
          <i :class="['status', `status-${item.status}`]">{{ statusLabel(item.status) }}</i>
        </button>
      </aside>

      <section class="card editor">
        <h2>{{ form.id ? form.title : 'Новое поручение' }}</h2>
        <form class="form" @submit.prevent="saveAssignment">
          <label>Название<input v-model="form.title" type="text" :disabled="form.id && !form.permissions.canEdit" /></label>
          <label>Ответственный
            <select v-model.number="form.responsibleId" :disabled="form.id && !form.permissions.canEdit"><option :value="null">Выберите</option><option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option></select>
          </label>
          <label>Срок<input v-model="form.dueDate" type="date" :disabled="form.id && !form.permissions.canEdit" /></label>
          <label>Документ-источник
            <select v-model.number="form.documentWorkflowId" :disabled="form.id && !form.permissions.canEdit"><option :value="null">Без документа</option><option v-for="doc in documents" :key="doc.id" :value="doc.id">{{ doc.title }}</option></select>
          </label>
          <label class="wide">Описание<textarea v-model="form.description" rows="3" :disabled="form.id && !form.permissions.canEdit"></textarea></label>
          <div v-if="!form.id || form.permissions.canEdit" class="actions"><button class="primary" :disabled="saving">{{ saving ? 'Сохранение...' : 'Сохранить поручение' }}</button></div>
        </form>

        <section v-if="form.id" class="panel">
          <div class="panel-head"><h3>Управление</h3><i :class="['status', `status-${form.status}`]">{{ statusLabel(form.status) }}</i></div>
          <div class="button-row">
            <button v-if="form.permissions.canStart && form.rawStatus === 'assigned'" class="secondary" type="button" @click="startWork">В работу</button>
            <button v-if="form.permissions.canComplete && form.rawStatus !== 'completed'" class="primary" type="button" @click="completeWork">Выполнено</button>
            <button v-if="form.permissions.canCancel && form.rawStatus !== 'completed' && form.rawStatus !== 'cancelled'" class="danger-button" type="button" @click="cancelWork">Отменить</button>
          </div>
        </section>

        <section v-if="form.id" class="panel">
          <div class="panel-head"><h3>История</h3><button class="secondary" type="button" @click="historyOpen = !historyOpen">{{ historyOpen ? 'Скрыть' : 'Показать' }}</button></div>
          <div v-if="historyOpen" class="events">
            <p v-if="!form.events.length" class="muted">История пока пустая.</p>
            <div v-for="event in form.events" :key="event.id" class="event"><strong>{{ eventLabel(event.eventType) }}</strong><span>{{ event.comment }}</span><small>{{ formatDateTime(event.createdAt) }} · {{ event.createdBy || '—' }}</small></div>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head"><h3>Отчёт по ответственным</h3></div>
          <div class="report-table-wrap">
            <table class="report-table">
              <thead><tr><th>Ответственный</th><th>Всего</th><th>Выполнено</th><th>Не выполнено</th><th>Просрочено</th><th>%</th></tr></thead>
              <tbody><tr v-for="row in report.rows" :key="row.responsible"><td>{{ row.responsible }}</td><td>{{ row.total }}</td><td>{{ row.completed }}</td><td>{{ row.notCompleted }}</td><td>{{ row.overdue }}</td><td>{{ row.completionRate }}%</td></tr></tbody>
            </table>
          </div>
        </section>
      </section>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'
import { getTaskAssignments, createTaskAssignment, updateTaskAssignment, startTaskAssignment, completeTaskAssignment, cancelTaskAssignment } from '../../services/TaskAssignments/task-assignments.service.js'

const assignments = ref([])
const users = ref([])
const documents = ref([])
const report = reactive({ total: { total: 0, completed: 0, completedInTime: 0, completedLate: 0, notCompleted: 0, overdue: 0, completionRate: 0 }, rows: [] })
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const historyOpen = ref(false)
const filter = ref('all')
const month = ref(new Date().toISOString().slice(0, 7))
const form = reactive(emptyAssignment())

const filteredAssignments = computed(() => filter.value === 'all' ? assignments.value : assignments.value.filter(item => item.status === filter.value || item.rawStatus === filter.value))

function emptyAssignment() { return { id: null, title: '', description: '', responsibleId: null, documentWorkflowId: null, dueDate: new Date().toISOString().slice(0, 10), status: 'assigned', rawStatus: 'assigned', events: [], permissions: { canEdit: true, canStart: false, canComplete: false, canCancel: true } } }
function normalize(item = {}) { return { ...emptyAssignment(), ...item, description: item.description ?? '', events: item.events ?? [], permissions: { ...emptyAssignment().permissions, ...(item.permissions ?? {}) } } }
function setMessage(type, message) { error.value = type === 'error' ? message : ''; success.value = type === 'success' ? message : '' }
function startCreating() { Object.assign(form, emptyAssignment()); historyOpen.value = false; setMessage('', '') }
function selectAssignment(item) { Object.assign(form, normalize(item)); historyOpen.value = false; setMessage('', '') }
async function loadData() { loading.value = true; try { const data = await getTaskAssignments(month.value); assignments.value = (data.assignments ?? []).map(normalize); users.value = data.users ?? []; documents.value = data.documents ?? []; Object.assign(report, data.report ?? report) } catch (e) { setMessage('error', errorMessage(e, 'Не удалось загрузить поручения.')) } finally { loading.value = false } }
async function saveAssignment() { if (!form.title.trim()) return setMessage('error', 'Укажите название поручения.'); if (!form.responsibleId) return setMessage('error', 'Выберите ответственного.'); saving.value = true; try { const payload = { title: form.title, description: form.description, responsibleId: form.responsibleId, documentWorkflowId: form.documentWorkflowId, dueDate: form.dueDate }; const saved = form.id ? await updateTaskAssignment(form.id, payload) : await createTaskAssignment(payload); Object.assign(form, normalize(saved)); await loadData(); const found = assignments.value.find(item => item.id === saved.id); if (found) selectAssignment(found); setMessage('success', 'Поручение сохранено.') } catch (e) { setMessage('error', errorMessage(e, 'Не удалось сохранить поручение.')) } finally { saving.value = false } }
async function startWork() { await action(() => startTaskAssignment(form.id), 'Поручение взято в работу.') }
async function completeWork() { const comment = prompt('Комментарий о выполнении:', '') ?? ''; await action(() => completeTaskAssignment(form.id, comment), 'Поручение выполнено.') }
async function cancelWork() { const comment = prompt('Причина отмены:', '') ?? ''; await action(() => cancelTaskAssignment(form.id, comment), 'Поручение отменено.') }
async function action(fn, message) { try { const updated = await fn(); Object.assign(form, normalize(updated)); await loadData(); const found = assignments.value.find(item => item.id === updated.id); if (found) selectAssignment(found); setMessage('success', message) } catch (e) { setMessage('error', errorMessage(e, 'Не удалось выполнить действие.')) } }
function statusLabel(status) { return { assigned: 'Назначено', in_progress: 'В работе', completed: 'Выполнено', overdue: 'Просрочено', cancelled: 'Отменено' }[status] || status }
function eventLabel(type) { return { created: 'Создание', updated: 'Обновление', started: 'В работу', completed: 'Выполнено', cancelled: 'Отменено' }[type] || type }
function formatDate(value) { return value ? new Date(value).toLocaleDateString('ru-RU') : '—' }
function formatDateTime(value) { return value ? new Date(value).toLocaleString('ru-RU') : '—' }
function errorMessage(e, fallback) { return e?.response?.data?.message || e?.message || fallback }
onMounted(loadData)
</script>

<style scoped>
.assignments-page { width: 100%; min-width: 0; min-height: 100vh; padding: 32px; background: #f4f6f8; color: #17202a; }
.page-header, .header-actions, .list-head, .panel-head, .actions, .button-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.page-header { align-items: flex-start; margin-bottom: 24px; }.header-actions { flex-wrap: wrap; justify-content: flex-end; } .month-filter { width: 180px; }
h1, h2, h3, p { margin-top: 0; }.subtitle, .muted { color: #607080; }
.report-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }.metric { padding: 16px; border: 1px solid #e1e7ef; border-radius: 16px; background: #fff; }.metric span { display: block; color: #607080; font-size: 12px; }.metric strong { font-size: 24px; }.metric.good strong { color: #16703b; }.metric.warn strong { color: #9a5b00; }.metric.danger strong { color: #b42318; }
.layout { display: grid; grid-template-columns: minmax(280px, 360px) minmax(0, 1fr); gap: 20px; }.card { min-width: 0; padding: 20px; border: 1px solid #e1e7ef; border-radius: 18px; background: #fff; box-shadow: 0 12px 30px rgba(18,38,63,.05); }.list { align-self: start; display: grid; gap: 10px; }.counter { padding: 4px 8px; border-radius: 999px; background: #eef4ff; color: #1f63b6; font-size: 12px; font-weight: 800; }
.list-item { display: grid; gap: 6px; width: 100%; padding: 13px; border: 1px solid #e1e7ef; border-radius: 12px; background: #fff; color: inherit; text-align: left; }.list-item.active { border-color: #2f80ed; background: #eef4ff; }
.status { display: inline-flex; width: max-content; padding: 4px 8px; border-radius: 999px; background: #f1f5f9; color: #526170; font-size: 11px; font-style: normal; font-weight: 800; }.status-completed { background: #eaf8ef; color: #16703b; }.status-overdue { background: #fff0f0; color: #b42318; }.status-in_progress { background: #eef4ff; color: #1f63b6; }.status-cancelled { background: #f1f5f9; color: #526170; }
.form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 16px; }.wide { grid-column: 1 / -1; }.actions, .button-row { justify-content: flex-end; margin-top: 14px; }.danger-button { background: #b42318; color: #fff; }
.panel { margin-top: 20px; padding: 16px; border: 1px solid #e1e7ef; border-radius: 14px; background: #fbfcfe; }.events { display: grid; gap: 8px; margin-top: 12px; }.event { display: grid; gap: 5px; min-width: 0; padding: 10px 12px; border: 1px solid #e1e7ef; border-radius: 10px; background: #fff; }
.report-table-wrap { overflow-x: auto; }.report-table { width: 100%; min-width: 650px; border-collapse: collapse; }.report-table th, .report-table td { padding: 10px; border-bottom: 1px solid #e1e7ef; text-align: left; }
@media (max-width: 1100px) { .layout, .form, .report-grid { grid-template-columns: 1fr; } .wide { grid-column: auto; } }
@media (max-width: 640px) { .page-header, .header-actions, .panel-head { flex-direction: column; align-items: stretch; } }
</style>
