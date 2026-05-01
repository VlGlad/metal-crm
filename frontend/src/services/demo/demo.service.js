import { http } from '../http'

export async function getHello() {
  const { data} = await http.get('/hello')
  return data
}

export async function sendContact(payload) {
  const { data} = await http.post('/contact', payload)
  return data
}