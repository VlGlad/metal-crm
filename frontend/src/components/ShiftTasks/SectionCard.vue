<template>
  <article class="section-card">
    <div class="section-header">
      <label class="section-name">
        <span>Название участка</span>

        <div class="section-name-row">
          <input v-model="section.name" type="text" placeholder="Пила 810" />

          <button class="danger" @click="$emit('remove-section', sectionIndex)">
            Удалить участок
          </button>
        </div>
      </label>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Марка / деталь</th>
            <th>1 смена план</th>
            <th>1 смена факт</th>
            <th>2 смена план</th>
            <th>2 смена факт</th>
            <th>Примечание</th>
            <th></th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="(item, itemIndex) in section.items" :key="item.localId">
            <td>
              <textarea
                v-model="item.mark"
                rows="2"
                placeholder="Труба 180x140x5 C245 (0167) ПК 1-31 дет 311"
              />
            </td>

            <td>
              <input v-model.number="item.firstShiftPlan" type="number" min="0" />
            </td>

            <td>
              <input v-model.number="item.firstShiftFact" type="number" min="0" />
            </td>

            <td>
              <input v-model.number="item.secondShiftPlan" type="number" min="0" />
            </td>

            <td>
              <input v-model.number="item.secondShiftFact" type="number" min="0" />
            </td>

            <td>
              <input v-model="item.note" type="text" placeholder="ССЦ" />
            </td>

            <td>
              <button class="icon-danger" @click="removeItem(itemIndex)">
                ×
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <button class="link add-row" @click="addItem">
      + Добавить строку
    </button>
  </article>
</template>

<script setup>
import { emptyItem } from '../../utils/ShiftTasks/shiftTaskFactory.js'

const props = defineProps({
  section: {
    type: Object,
    required: true
  },
  sectionIndex: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['remove-section', 'error'])

function addItem() {
  props.section.items.push(emptyItem())
}

function removeItem(itemIndex) {
  if (props.section.items.length === 1) {
    emit('error', 'В участке должна быть хотя бы одна строка.')
    return
  }

  props.section.items.splice(itemIndex, 1)
}
</script>

<style scoped>
.section-card {
  min-width: 0;
  max-width: 100%;
  margin-top: 16px;
  padding: 16px;
  border: 1px solid #e1e7ef;
  border-radius: 16px;
  background: #fbfcfe;
}

.section-header {
  display: block;
}

.section-name {
  width: 100%;
}

.section-name-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-name-row input {
  min-width: 0;
  flex: 1;
}

.section-name-row .danger {
  height: 42px;
  white-space: nowrap;
}

.table-wrap {
  display: block;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow-x: auto;
  margin-top: 16px;
  border: 1px solid #e1e7ef;
  border-radius: 14px;
  background: #ffffff;
}

table {
  width: 100%;
  min-width: 980px;
  border-collapse: collapse;
}

th,
td {
  padding: 10px;
  border-bottom: 1px solid #edf1f5;
  text-align: left;
  vertical-align: top;
}

th {
  background: #f8fafc;
  font-size: 12px;
  color: #526170;
}

td input[type='number'] {
  min-width: 110px;
}

.add-row {
  margin-top: 12px;
}

@media (max-width: 640px) {
  .section-name-row {
    flex-direction: column;
    align-items: stretch;
  }

  .section-name-row .danger {
    width: 100%;
  }
}
</style>