<template>
  <aside class="card sidebar">
    <div class="card-title-row">
      <h2>Задания</h2>
      <button class="link" @click="$emit('new-task')">+ Новое</button>
    </div>

    <div v-if="loading" class="muted">Загрузка...</div>

    <div v-else-if="tasks.length === 0" class="empty">
      Пока нет заданий.
    </div>

    <button
      v-for="task in tasks"
      :key="task.id"
      class="task-item"
      :class="{ active: task.id === activeTaskId }"
      @click="$emit('select-task', task)"
    >
      <strong>{{ formatDate(task.date) }}</strong>
      <span>{{ task.title || 'Без названия' }}</span>
      <small>{{ task.status || 'draft' }}</small>
    </button>
  </aside>
</template>

<script setup>
defineProps({
  tasks: {
    type: Array,
    default: () => []
  },
  activeTaskId: {
    type: [Number, String, null],
    default: null
  },
  loading: Boolean
})

defineEmits(['new-task', 'select-task'])

function formatDate(value) {
  if (!value) return 'Без даты'
  return new Intl.DateTimeFormat('ru-RU').format(new Date(value))
}
</script>

<style scoped>
.sidebar {
  min-width: 0;
  align-self: start;
  position: sticky;
  top: 20px;
  padding: 20px;
}

h2 {
  margin: 0;
  font-size: 18px;
}

.task-item {
  width: 100%;
  display: grid;
  gap: 4px;
  margin-top: 10px;
  padding: 12px;
  text-align: left;
  background: #f8fafc;
  border: 1px solid #edf1f5;
}

.task-item.active {
  border-color: #2f80ed;
  background: #eef6ff;
}

.task-item span,
.task-item small {
  overflow-wrap: anywhere;
  color: #607080;
}

.task-item strong {
  color: #17202a;
}

@media (max-width: 1280px) {
  .sidebar {
    position: static;
  }
}
</style>