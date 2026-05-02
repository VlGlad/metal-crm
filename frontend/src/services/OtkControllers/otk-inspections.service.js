import { http } from '../http'

export async function getOtkInspections() {
  const { data } = await http.get('/otk-inspections')
  return data
}

export async function createOtkInspection(payload) {
  const { data } = await http.post('/otk-inspections', payload)
  return data
}

export async function updateOtkInspection(id, payload) {
  const { data } = await http.put(`/otk-inspections/${id}`, payload)
  return data
}

export async function deleteOtkInspection(id) {
  const { data } = await http.delete(`/otk-inspections/${id}`)
  return data
}

export async function signOtkExecutor(id, executorName) {
  const { data } = await http.post(`/otk-inspections/${id}/sign-executor`, {
    executorName
  })

  return data
}

export async function signOtkController(id, controllerName) {
  const { data } = await http.post(`/otk-inspections/${id}/sign-controller`, {
    controllerName
  })

  return data
}