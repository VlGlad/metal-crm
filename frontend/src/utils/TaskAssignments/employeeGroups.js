const NO_DEPARTMENT = 'Без подразделения'

export function groupByDepartment(users = []) {
  const groups = new Map()

  for (const user of users) {
    const department = user.department || NO_DEPARTMENT
    if (!groups.has(department)) groups.set(department, [])
    groups.get(department).push(user)
  }

  return [...groups.entries()]
    .sort(([a], [b]) => (a === NO_DEPARTMENT) - (b === NO_DEPARTMENT) || a.localeCompare(b, 'ru'))
    .map(([department, items]) => ({
      department,
      users: [...items].sort((a, b) => (a.name || '').localeCompare(b.name || '', 'ru'))
    }))
}

export function optionLabel(user) {
  return user.position ? `${user.name} — ${user.position}` : user.name
}
