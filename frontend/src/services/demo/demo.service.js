import { http } from '../http'

export async function getHello() {
  const response = await http.get('/hello')
  return response.data
}

export async function sendContact(payload) {
  const response = await http.post('/contact', payload)
  return response.data
}