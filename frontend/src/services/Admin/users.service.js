import { http } from '../http'

export async function getUsers() {
  const { data } = await http.get('/admin/users')
  return data
}

export async function createUser(payload) {
  const { data } = await http.post('/admin/users', payload)
  return data
}

export async function updateUser(id, payload) {
  const { data } = await http.put(`/admin/users/${id}`, payload)
  return data
}
