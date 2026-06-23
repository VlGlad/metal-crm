<template>
  <div class="month-picker">
    <button
      type="button"
      class="month-trigger"
      :disabled="disabled"
      @click="toggle"
    >
      <span>{{ selectedLabel }}</span>
      <span class="calendar-icon" aria-hidden="true">▦</span>
    </button>

    <div v-if="open" class="month-popover">
      <div class="year-selector">
        <button type="button" aria-label="Предыдущий год" @click="displayedYear--">
          ‹
        </button>
        <strong>{{ displayedYear }}</strong>
        <button type="button" aria-label="Следующий год" @click="displayedYear++">
          ›
        </button>
      </div>

      <div class="month-grid">
        <button
          v-for="(month, index) in months"
          :key="month"
          type="button"
          :class="{ selected: isSelected(index + 1) }"
          @click="selectMonth(index + 1)"
        >
          {{ month }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  disabled: Boolean
})

const emit = defineEmits(['update:modelValue'])
const open = ref(false)
const displayedYear = ref(new Date().getFullYear())

const months = [
  'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
  'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'
]

const selectedLabel = computed(() => {
  if (!props.modelValue) return 'Выберите месяц'

  const [year, month] = props.modelValue.split('-').map(Number)

  return new Intl.DateTimeFormat('ru-RU', {
    month: 'long',
    year: 'numeric'
  }).format(new Date(year, month - 1, 1))
})

watch(
  () => props.modelValue,
  (value) => {
    const year = Number(value?.slice(0, 4))
    if (year) displayedYear.value = year
  },
  { immediate: true }
)

function toggle() {
  if (!props.disabled) open.value = !open.value
}

function selectMonth(month) {
  emit('update:modelValue', `${displayedYear.value}-${String(month).padStart(2, '0')}`)
  open.value = false
}

function isSelected(month) {
  return props.modelValue === `${displayedYear.value}-${String(month).padStart(2, '0')}`
}
</script>

<style scoped>
.month-picker {
  position: relative;
  width: 100%;
}

.month-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  min-height: 44px;
  padding: 10px 12px;
  border: 1px solid #d7dde5;
  border-radius: 10px;
  background: #fff;
  color: #17202a;
  font: inherit;
  text-align: left;
}

.month-trigger:focus {
  border-color: #2f80ed;
  box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.12);
  outline: none;
}

.month-trigger:disabled {
  background: #f1f5f9;
  color: #607080;
  cursor: not-allowed;
}

.calendar-icon {
  color: #2f80ed;
  font-size: 22px;
  line-height: 1;
}

.month-popover {
  position: absolute;
  z-index: 20;
  top: calc(100% + 8px);
  left: 0;
  width: min(320px, calc(100vw - 48px));
  padding: 14px;
  border: 1px solid #d7dde5;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 16px 40px rgba(18, 38, 63, 0.18);
}

.year-selector {
  display: grid;
  grid-template-columns: 38px 1fr 38px;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  text-align: center;
}

.year-selector button {
  height: 36px;
  padding: 0;
  background: #eef4ff;
  color: #1f63b6;
  font-size: 24px;
}

.month-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 7px;
}

.month-grid button {
  padding: 9px 6px;
  border: 1px solid transparent;
  background: #f8fafc;
  color: #3c4856;
}

.month-grid button:hover {
  background: #eef4ff;
}

.month-grid button.selected {
  border-color: #2f80ed;
  background: #2f80ed;
  color: #fff;
}
</style>
