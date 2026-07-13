import { http } from '../http'

export async function getProcurementRequests() {
  const { data } = await http.get('/procurement-requests')
  return data
}

export async function createProcurementRequest(payload) {
  const { data } = await http.post('/procurement-requests', payload)
  return data
}

export async function updateProcurementRequest(id, payload) {
  const { data } = await http.put(`/procurement-requests/${id}`, payload)
  return data
}

export async function transitionProcurementRequest(id, action, payload = {}) {
  const { data } = await http.post(`/procurement-requests/${id}/${action}`, payload)
  return data
}

export async function uploadProcurementRequestFiles(id, files) {
  let result = null

  for (let offset = 0; offset < files.length; offset += 10) {
    const formData = new FormData()

    for (const file of files.slice(offset, offset + 10)) {
      formData.append('files[]', file)
    }

    const response = await http.post(`/procurement-requests/${id}/documents`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    result = response.data
  }

  return result
}

export async function deleteProcurementRequestFile(requestId, fileId) {
  const { data } = await http.delete(`/procurement-requests/${requestId}/documents/${fileId}`)
  return data
}

export async function downloadProcurementRequestFile(file) {
  const response = await http.get(file.downloadUrl, { responseType: 'blob' })
  const url = URL.createObjectURL(response.data)
  const link = document.createElement('a')
  link.href = url
  link.download = file.name
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
