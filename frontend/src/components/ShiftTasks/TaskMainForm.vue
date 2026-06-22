<template>
  <section>
    <div class="card-title-row">
      <h2>Основные данные</h2>
      <span class="badge">{{ model.status }}</span>
    </div>

    <div class="form-grid">
      <label>
        Дата задания
        <input v-model="model.date" type="date" />
      </label>

      <label>
        Название
        <input
          v-model="model.title"
          type="text"
          placeholder="Сменное задание на 12 января 2025 г."
        />
      </label>

      <label>
        Цех
        <input
          :value="model.workshop"
          type="text"
          placeholder="Определяется по роли пользователя"
          readonly
        />
      </label>

      <label>
        Статус
        <select v-model="model.status">
          <option value="draft">Черновик</option>
          <option value="issued">Выдано</option>
          <option value="in_progress">В работе</option>
          <option value="closed">Закрыто</option>
        </select>
      </label>
    </div>
  </section>
</template>

<script setup>
const model = defineModel({
  type: Object,
  required: true
})
</script>

<style scoped>
h2 {
  margin: 0;
  font-size: 18px;
}

.badge {
  padding: 6px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #526170;
  font-size: 12px;
  font-weight: 700;
}

input[readonly] {
  background: #f1f5f9;
  color: #526170;
  cursor: not-allowed;
}
</style>
