import { http } from '../http'

export async function getWorkingDocuments() {
  const { data } = await http.get('/working-documents')
  return data
}

export async function createWorkingDocument(payload) {
  const { data } = await http.post('/working-documents', payload)
  return data
}

export async function updateWorkingDocument(id, payload) {
  const { data } = await http.put(`/working-documents/${id}`, payload)
  return data
}

export async function uploadWorkingDocumentFiles(id, files) {
  let result = null

  for (let offset = 0; offset < files.length; offset += 10) {
    const formData = new FormData()

    for (const file of files.slice(offset, offset + 10)) {
      formData.append('files[]', file)
    }

    const response = await http.post(`/working-documents/${id}/documents`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    result = response.data
  }

  return result
}

export async function deleteWorkingDocumentFile(packageId, fileId) {
  const { data } = await http.delete(`/working-documents/${packageId}/documents/${fileId}`)
  return data
}

export async function downloadWorkingDocumentFile(file) {
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
