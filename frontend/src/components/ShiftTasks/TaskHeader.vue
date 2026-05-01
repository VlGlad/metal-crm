<template>
  <section class="header">
    <div>
      <p class="eyebrow">Сменное задание</p>
      <h1>Форма заполнения сменного задания</h1>
    </div>

    <div class="actions">
      <button class="secondary" :disabled="loading" @click="$emit('refresh')">
        Обновить
      </button>

      <button class="primary" :disabled="saving" @click="$emit('save')">
        {{ saving ? 'Сохранение...' : 'Сохранить' }}
      </button>
    </div>
  </section>
</template>

<script setup>
defineProps({
  saving: Boolean,
  loading: Boolean
})

defineEmits(['refresh', 'save'])
</script>

<style scoped>
.header {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: start;
  gap: 24px;
  margin-bottom: 24px;
}

.header > div:first-child {
  grid-column: 2;
  text-align: center;
}

.actions {
  grid-column: 3;
  justify-self: end;
  display: flex;
  gap: 12px;
}

.eyebrow {
  margin: 0 0 6px;
  font-size: 13px;
  font-weight: 700;
  color: #2f80ed;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

h1 {
  margin: 0;
  font-size: 30px;
  line-height: 1.15;
}

@media (max-width: 1024px) {
  .header {
    grid-template-columns: 1fr;
  }

  .header > div:first-child,
  .actions {
    grid-column: 1;
    justify-self: center;
  }

  .actions {
    margin-top: 12px;
  }
}
</style>