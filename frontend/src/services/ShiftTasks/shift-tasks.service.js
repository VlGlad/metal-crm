import { http } from '../http'

export async function getShiftTasks() {
  const { data } = await http.get('/shift-tasks')
  return data
}

export async function createShiftTask(payload) {
  const { data } = await http.post('/shift-tasks', payload)
  return data
}

export async function updateShiftTask(id, payload) {
  const { data } = await http.put(`/shift-tasks/${id}`, payload)
  return data
}

export async function deleteShiftTask(id) {
  const { data } = await http.delete(`/shift-tasks/${id}`)
  return data
}