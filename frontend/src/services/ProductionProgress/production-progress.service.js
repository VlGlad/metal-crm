import { http } from '../http'

export async function getProductionProgress() {
  const { data } = await http.get('/production-progress')
  return data
}

export async function saveProductionProgressOtk(itemId, payload) {
  const { data } = await http.post(`/production-progress/items/${itemId}/otk`, payload)
  return data
}
