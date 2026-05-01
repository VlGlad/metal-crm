export function validateTask(task) {
  if (!task.date) {
    return 'Укажите дату задания.'
  }

  if (!task.workshop.trim()) {
    return 'Укажите цех.'
  }

  for (const section of task.sections) {
    if (!section.name.trim()) {
      return 'Укажите название каждого участка.'
    }

    for (const item of section.items) {
      if (!item.mark.trim()) {
        return 'Заполните марку/деталь во всех строках.'
      }
    }
  }

  return ''
}