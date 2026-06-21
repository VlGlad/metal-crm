let localIdCounter = 0

function makeLocalId(prefix = 'local') {
  localIdCounter += 1

  return `${prefix}-${Date.now().toString(36)}-${localIdCounter}-${Math.random()
    .toString(36)
    .slice(2)}`
}

export function emptyTask() {
  return {
    id: null,
    date: new Date().toISOString().slice(0, 10),
    title: '',
    workshop: '',
    status: 'draft',
    sections: [emptySection('Пила 810')]
  }
}

export function emptySection(name = '') {
  return {
    id: null,
    localId: makeLocalId('section'),
    name,
    items: [emptyItem()]
  }
}

export function emptyItem() {
  return {
    id: null,
    localId: makeLocalId('item'),
    mark: '',
    firstShiftPlan: null,
    firstShiftFact: null,
    secondShiftPlan: null,
    secondShiftFact: null,
    note: ''
  }
}

export function normalizeTask(task) {
  return {
    id: task.id ?? null,
    date: task.date ?? new Date().toISOString().slice(0, 10),
    title: task.title ?? '',
    workshop: task.workshop ?? '',
    status: task.status ?? 'draft',
    sections: (task.sections?.length ? task.sections : [emptySection()]).map(normalizeSection)
  }
}

function normalizeSection(section) {
  return {
    id: section.id ?? null,
    localId: section.localId ?? makeLocalId('section'),
    name: section.name ?? '',
    items: (section.items?.length ? section.items : [emptyItem()]).map(normalizeItem)
  }
}

function normalizeItem(item) {
  return {
    id: item.id ?? null,
    localId: item.localId ?? makeLocalId('item'),
    mark: item.mark ?? '',
    firstShiftPlan: item.firstShiftPlan ?? null,
    firstShiftFact: item.firstShiftFact ?? null,
    secondShiftPlan: item.secondShiftPlan ?? null,
    secondShiftFact: item.secondShiftFact ?? null,
    note: item.note ?? ''
  }
}

export function payloadFromTask(task) {
  return {
    date: task.date,
    title: task.title,
    workshop: task.workshop,
    status: task.status,
    sections: task.sections.map(section => ({
      id: section.id,
      name: section.name,
      items: section.items.map(item => ({
        id: item.id,
        mark: item.mark,
        firstShiftPlan: item.firstShiftPlan,
        firstShiftFact: item.firstShiftFact,
        secondShiftPlan: item.secondShiftPlan,
        secondShiftFact: item.secondShiftFact,
        note: item.note
      }))
    }))
  }
}