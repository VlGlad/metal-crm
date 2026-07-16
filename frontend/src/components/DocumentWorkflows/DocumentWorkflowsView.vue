<template>
  <main class="workflow-page">
    <header class="page-header">
      <div>
        <h1>Документооборот</h1>
        <p class="subtitle">Документы на согласование: файлы, решения согласующих, замечания и история.</p>
      </div>
      <div class="header-actions">
        <button class="secondary" :disabled="loading" @click="loadData(selected?.id)">Обновить</button>
        <button class="primary" @click="startCreating">Новый документ</button>
      </div>
    </header>

    <BaseAlert v-if="error" type="error" :message="error" />
    <BaseAlert v-if="success" type="success" :message="success" />

    <section class="layout">
      <aside class="card list">
        <div class="list-head"><h2>Документы</h2><span class="counter">{{ workflows.length }}</span></div>
        <p v-if="loading" class="muted">Загрузка...</p>
        <p v-else-if="!workflows.length" class="muted">Документов пока нет.</p>
        <button v-for="item in workflows" :key="item.id" class="list-item" :class="{ active: item.id === form.id }" @click="selectWorkflow(item)">
          <strong>{{ item.title }}</strong>
          <span>{{ typeLabel(item.documentType) }}</span>
          <i :class="['status', `status-${item.status}`]">{{ statusLabel(item.status) }}</i>
        </button>
      </aside>

      <section class="card editor">
        <h2>{{ form.id ? form.title : 'Новый документ' }}</h2>
        <form class="form" @submit.prevent="saveWorkflow">
          <label>Название<input v-model="form.title" type="text" :disabled="!canEdit" /></label>
          <label>Тип документа
            <select v-model="form.documentType" :disabled="!canEdit">
              <option value="common">Общий документ</option>
              <option value="protocol">Протокол</option>
              <option value="order">По заказу</option>
              <option value="technical">Технические требования</option>
            </select>
          </label>
          <label class="wide">Описание<textarea v-model="form.description" rows="3" :disabled="!canEdit"></textarea></label>
          <label class="wide">Согласующие
            <select v-model="form.approverIds" multiple :disabled="!canEdit">
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} — {{ user.email }}</option>
            </select>
          </label>
          <div v-if="canEdit" class="actions"><button class="primary" :disabled="saving">{{ saving ? 'Сохранение...' : 'Сохранить' }}</button></div>
        </form>

        <section v-if="form.id" class="panel">
          <div class="panel-head"><h3>Файлы</h3><span class="counter">{{ form.files.length }}</span></div>
          <div v-if="form.files.length" class="file-list">
            <div v-for="file in form.files" :key="file.id" class="file-row">
              <button class="link" type="button" @click="download(file)">{{ file.name }}</button>
              <span>{{ formatFileSize(file.size) }}</span>
              <button v-if="canEdit" class="danger" type="button" @click="removeFile(file)">×</button>
            </div>
          </div>
          <p v-else class="muted">Файлы ещё не загружены.</p>
          <label v-if="canEdit" class="upload">Добавить файлы<input type="file" multiple @change="upload($event)" /></label>
        </section>

        <section v-if="form.id" class="panel">
          <div class="panel-head">
            <h3>Согласование</h3>
            <button v-if="form.permissions.canStart" class="primary" type="button" @click="startApproval">Запустить согласование</button>
          </div>
          <div class="approvals">
            <div v-for="step in form.approvals" :key="step.id" class="approval-row">
              <strong>{{ step.approverName || 'Согласующий не указан' }}</strong>
              <i :class="['status', `status-${step.status}`]">{{ approvalLabel(step.status) }}</i>
              <span>{{ step.comment || '—' }}</span>
              <small>{{ formatDateTime(step.decidedAt) }}</small>
            </div>
          </div>
          <div v-if="form.permissions.canDecide && form.status === 'in_approval'" class="decision-actions">
            <button class="primary" type="button" @click="decide('approve')">Согласовать</button>
            <button class="secondary" type="button" @click="decide('remarks')">Замечания</button>
            <button class="danger-button" type="button" @click="decide('reject')">Не согласовать</button>
          </div>
        </section>

        <section v-if="form.id" class="panel">
          <div class="panel-head"><h3>Поручение по документу</h3></div>
          <div class="assignment-form">
            <label>Поручение<input v-model="assignment.title" type="text" /></label>
            <label>Ответственный
              <select v-model.number="assignment.responsibleId"><option :value="null">Выберите</option><option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option></select>
            </label>
            <label>Срок<input v-model="assignment.dueDate" type="date" /></label>
            <label class="wide">Описание<textarea v-model="assignment.description" rows="2"></textarea></label>
          </div>
          <div class="actions"><button class="primary" type="button" @click="createAssignment">Создать поручение</button></div>
        </section>

        <section v-if="form.id" class="panel">
          <div class="panel-head"><h3>История</h3><button class="secondary" type="button" @click="historyOpen = !historyOpen">{{ historyOpen ? 'Скрыть' : 'Показать' }}</button></div>
          <div v-if="historyOpen" class="events">
            <p v-if="!form.events.length" class="muted">История пока пустая.</p>
            <div v-for="event in form.events" :key="event.id" class="event"><strong>{{ eventLabel(event.eventType) }}</strong><span>{{ event.comment }}</span><small>{{ formatDateTime(event.createdAt) }} · {{ event.createdBy || '—' }}</small></div>
          </div>
        </section>
      </section>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import BaseAlert from '../ShiftTasks/BaseAlert.vue'
import { createDocumentWorkflow, updateDocumentWorkflow, getDocumentWorkflows, uploadDocumentWorkflowFiles, deleteDocumentWorkflowFile, downloadDocumentWorkflowFile, startDocumentWorkflow, decideDocumentWorkflow, createWorkflowAssignment } from '../../services/DocumentWorkflows/document-workflows.service.js'

const workflows = ref([])
const users = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const historyOpen = ref(false)
const selected = ref(null)
const form = reactive(emptyWorkflow())
const assignment = reactive(emptyAssignment())
const canEdit = computed(() => !form.id || form.permissions.canEdit)

function emptyWorkflow() { return { id: null, title: '', documentType: 'common', description: '', approverIds: [], files: [], approvals: [], events: [], status: 'draft', permissions: { canEdit: true, canStart: false, canDecide: false } } }
function emptyAssignment() { return { title: '', description: '', responsibleId: null, dueDate: new Date().toISOString().slice(0, 10) } }
function normalize(item = {}) { return { ...emptyWorkflow(), ...item, description: item.description ?? '', approverIds: Array.isArray(item.approvals) ? item.approvals.map(step => step.approverId).filter(Boolean) : [], files: item.files ?? [], approvals: item.approvals ?? [], events: item.events ?? [], permissions: { ...emptyWorkflow().permissions, ...(item.permissions ?? {}) } } }
function setMessage(type, message) { error.value = type === 'error' ? message : ''; success.value = type === 'success' ? message : '' }
function startCreating() { Object.assign(form, emptyWorkflow()); selected.value = null; historyOpen.value = false; Object.assign(assignment, emptyAssignment()); setMessage('', '') }
function selectWorkflow(item) { const normalized = normalize(item); selected.value = normalized; Object.assign(form, normalized); historyOpen.value = false; Object.assign(assignment, emptyAssignment()); setMessage('', '') }
async function loadData(selectedId = null) { loading.value = true; try { const data = await getDocumentWorkflows(); workflows.value = (data.workflows ?? []).map(normalize); users.value = data.users ?? []; if (selectedId) { const found = workflows.value.find(item => item.id === selectedId); if (found) selectWorkflow(found) } window.dispatchEvent(new CustomEvent('app:notifications-refresh')) } catch (e) { setMessage('error', errorMessage(e, 'Не удалось загрузить документы.')) } finally { loading.value = false } }
async function saveWorkflow() { if (!form.title.trim()) return setMessage('error', 'Укажите название документа.'); if (!form.approverIds.length) return setMessage('error', 'Выберите согласующих.'); saving.value = true; try { const payload = { title: form.title, documentType: form.documentType, description: form.description, approverIds: form.approverIds }; const saved = form.id ? await updateDocumentWorkflow(form.id, payload) : await createDocumentWorkflow(payload); Object.assign(form, normalize(saved)); await loadData(saved.id); setMessage('success', 'Документ сохранён.') } catch (e) { setMessage('error', errorMessage(e, 'Не удалось сохранить документ.')) } finally { saving.value = false } }
async function upload(event) { const files = Array.from(event.target.files ?? []); event.target.value = ''; if (!files.length || !form.id) return; try { const updated = await uploadDocumentWorkflowFiles(form.id, files); Object.assign(form, normalize(updated)); await loadData(updated.id); setMessage('success', 'Файлы загружены.') } catch (e) { setMessage('error', errorMessage(e, 'Не удалось загрузить файлы.')) } }
async function removeFile(file) { if (!confirm(`Удалить файл «${file.name}»?`)) return; try { const updated = await deleteDocumentWorkflowFile(form.id, file.id); Object.assign(form, normalize(updated)); await loadData(updated.id); setMessage('success', 'Файл удалён.') } catch (e) { setMessage('error', errorMessage(e, 'Не удалось удалить файл.')) } }
async function download(file) { try { await downloadDocumentWorkflowFile(file) } catch (e) { setMessage('error', errorMessage(e, 'Не удалось скачать файл.')) } }
async function startApproval() { try { const updated = await startDocumentWorkflow(form.id); Object.assign(form, normalize(updated)); await loadData(updated.id); setMessage('success', 'Документ отправлен на согласование.') } catch (e) { setMessage('error', errorMessage(e, 'Не удалось запустить согласование.')) } }
async function decide(action) { const comment = prompt('Комментарий:', '') ?? ''; try { const updated = await decideDocumentWorkflow(form.id, action, comment); Object.assign(form, normalize(updated)); await loadData(updated.id); setMessage('success', 'Решение сохранено.') } catch (e) { setMessage('error', errorMessage(e, 'Не удалось сохранить решение.')) } }
async function createAssignment() { if (!assignment.title.trim()) return setMessage('error', 'Укажите поручение.'); if (!assignment.responsibleId) return setMessage('error', 'Выберите ответственного.'); try { await createWorkflowAssignment(form.id, { ...assignment }); Object.assign(assignment, emptyAssignment()); await loadData(form.id); setMessage('success', 'Поручение создано.') } catch (e) { setMessage('error', errorMessage(e, 'Не удалось создать поручение.')) } }
function typeLabel(type) { return { common: 'Общий документ', protocol: 'Протокол', order: 'По заказу', technical: 'Технические требования' }[type] || type }
function statusLabel(status) { return { draft: 'Черновик', in_approval: 'На согласовании', approved: 'Согласован', remarks: 'Есть замечания', rejected: 'Отклонён', revoked: 'Отозван' }[status] || status }
function approvalLabel(status) { return { pending: 'Ожидает', approved: 'Согласовано', rejected: 'Не согласовано', remarks: 'Замечания' }[status] || status }
function eventLabel(type) { return { created: 'Создание', updated: 'Обновление', revised: 'Доработка', started: 'Запуск', file_uploaded: 'Файлы загружены', file_deleted: 'Файл удалён', approval_approved: 'Согласовано', approval_rejected: 'Не согласовано', approval_remarks: 'Замечания', assignment_created: 'Поручение' }[type] || type }
function formatDateTime(value) { return value ? new Date(value).toLocaleString('ru-RU') : '—' }
function formatFileSize(bytes) { if (bytes < 1024) return `${bytes} Б`; if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} КБ`; return `${(bytes / 1024 / 1024).toFixed(1)} МБ` }
function errorMessage(e, fallback) { return e?.response?.data?.message || e?.message || fallback }
onMounted(loadData)
</script>

<style scoped>
.workflow-page { width: 100%; min-width: 0; min-height: 100vh; padding: 32px; background: #f4f6f8; color: #17202a; }
.page-header, .header-actions, .list-head, .panel-head, .actions, .file-row, .decision-actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.page-header { align-items: flex-start; margin-bottom: 24px; }
h1, h2, h3, p { margin-top: 0; }.subtitle, .muted { color: #607080; }
.layout { display: grid; grid-template-columns: minmax(280px, 360px) minmax(0, 1fr); gap: 20px; }
.card { min-width: 0; padding: 20px; border: 1px solid #e1e7ef; border-radius: 18px; background: #fff; box-shadow: 0 12px 30px rgba(18,38,63,.05); }
.list { align-self: start; display: grid; gap: 10px; }.counter { padding: 4px 8px; border-radius: 999px; background: #eef4ff; color: #1f63b6; font-size: 12px; font-weight: 800; }
.list-item { display: grid; gap: 6px; width: 100%; padding: 13px; border: 1px solid #e1e7ef; border-radius: 12px; background: #fff; color: inherit; text-align: left; }.list-item.active { border-color: #2f80ed; background: #eef4ff; }
.status { display: inline-flex; width: max-content; padding: 4px 8px; border-radius: 999px; background: #f1f5f9; color: #526170; font-size: 11px; font-style: normal; font-weight: 800; }
.status-approved { background: #eaf8ef; color: #16703b; }.status-rejected { background: #fff0f0; color: #b42318; }.status-remarks { background: #fff7e6; color: #9a5b00; }.status-in_approval, .status-pending { background: #eef4ff; color: #1f63b6; }
.form, .assignment-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 16px; }.wide { grid-column: 1 / -1; } select[multiple] { min-height: 130px; }
.panel { margin-top: 20px; padding: 16px; border: 1px solid #e1e7ef; border-radius: 14px; background: #fbfcfe; }.file-list, .approvals, .events { display: grid; gap: 8px; margin-top: 12px; }.file-row, .approval-row, .event { min-width: 0; padding: 10px 12px; border: 1px solid #e1e7ef; border-radius: 10px; background: #fff; }.approval-row, .event { display: grid; gap: 5px; }
.link { padding: 0; background: transparent; color: #1f63b6; text-align: left; }.danger { width: 30px; height: 30px; padding: 0; background: #fff0f0; color: #b42318; }.danger-button { background: #b42318; color: #fff; }
.upload { display: inline-flex; width: auto; margin-top: 12px; padding: 10px 14px; border-radius: 10px; background: #eef4ff; color: #1f63b6; cursor: pointer; }.upload input { display: none; }
.decision-actions, .actions { justify-content: flex-end; margin-top: 14px; }
@media (max-width: 1100px) { .layout, .form, .assignment-form { grid-template-columns: 1fr; } .wide { grid-column: auto; } }
@media (max-width: 640px) { .page-header, .header-actions, .panel-head { flex-direction: column; align-items: stretch; } }
</style>
