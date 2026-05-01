<template>
  <section class="sections">
    <div class="section-toolbar">
      <h2>Оборудование / участки</h2>
      <button class="secondary" @click="addSection">
        + Добавить участок
      </button>
    </div>

    <SectionCard
      v-for="(section, sectionIndex) in sections"
      :key="section.localId"
      :section="section"
      :section-index="sectionIndex"
      @remove-section="removeSection"
      @error="$emit('error', $event)"
    />
  </section>
</template>

<script setup>
import SectionCard from './SectionCard.vue'
import { emptySection } from '../../utils/ShiftTasks/shiftTaskFactory.js'

const sections = defineModel('sections', {
  type: Array,
  required: true
})

const emit = defineEmits(['error'])

function addSection() {
  sections.value.push(emptySection())
}

function removeSection(sectionIndex) {
  if (sections.value.length === 1) {
    emit('error', 'В задании должен быть хотя бы один участок.')
    return
  }

  sections.value.splice(sectionIndex, 1)
}
</script>

<style scoped>
.sections {
  margin-top: 28px;
}

h2 {
  margin: 0;
  font-size: 18px;
}
</style>