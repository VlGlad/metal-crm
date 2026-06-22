import { http } from '../http'

export async function getOrders() {
  const { data } = await http.get('/orders')
  return data
}

export async function createOrder(payload) {
  const { data } = await http.post('/orders', payload)
  return data
}

export async function updateOrder(id, payload) {
  const { data } = await http.put(`/orders/${id}`, payload)
  return data
}

export async function uploadOrderFiles(id, type, files) {
  const formData = new FormData()

  for (const file of files) {
    formData.append('files[]', file)
  }

  const { data } = await http.post(`/orders/${id}/documents/${type}`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })

  return data
}

export async function deleteOrderFile(orderId, fileId) {
  const { data } = await http.delete(`/orders/${orderId}/documents/${fileId}`)
  return data
}

export async function issueOrder(id) {
  const { data } = await http.post(`/orders/${id}/issue`)
  return data
}

export async function downloadOrderFile(file) {
  const response = await http.get(file.downloadUrl, {
    responseType: 'blob'
  })

  const url = URL.createObjectURL(response.data)
  const link = document.createElement('a')
  link.href = url
  link.download = file.name
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
