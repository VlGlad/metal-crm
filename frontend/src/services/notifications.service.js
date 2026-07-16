import { http } from './http'

export async function getNotificationCounts() {
  const { data } = await http.get('/notifications/counts')
  return data
}
