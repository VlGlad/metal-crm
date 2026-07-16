import { http } from '../http'

export async function getDocumentWorkflows() { const { data } = await http.get('/document-workflows'); return data }
export async function createDocumentWorkflow(payload) { const { data } = await http.post('/document-workflows', payload); return data }
export async function updateDocumentWorkflow(id, payload) { const { data } = await http.put(`/document-workflows/${id}`, payload); return data }
export async function startDocumentWorkflow(id) { const { data } = await http.post(`/document-workflows/${id}/start`); return data }
export async function decideDocumentWorkflow(id, action, comment) { const { data } = await http.post(`/document-workflows/${id}/${action}`, { comment }); return data }
export async function createWorkflowAssignment(id, payload) { const { data } = await http.post(`/document-workflows/${id}/assignments`, payload); return data }
export async function uploadDocumentWorkflowFiles(id, files) {
  let result = null
  for (let offset = 0; offset < files.length; offset += 10) {
    const formData = new FormData()
    for (const file of files.slice(offset, offset + 10)) formData.append('files[]', file)
    const response = await http.post(`/document-workflows/${id}/files`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    result = response.data
  }
  return result
}
export async function deleteDocumentWorkflowFile(workflowId, fileId) { const { data } = await http.delete(`/document-workflows/${workflowId}/files/${fileId}`); return data }
export async function downloadDocumentWorkflowFile(file) {
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