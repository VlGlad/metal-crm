import { http } from '../http'

export async function getMonthlyPlans() {
  const { data } = await http.get('/monthly-plans')
  return data
}

export async function createMonthlyPlan(payload) {
  const { data } = await http.post('/monthly-plans', payload)
  return data
}

export async function updateMonthlyPlan(id, payload) {
  const { data } = await http.put(`/monthly-plans/${id}`, payload)
  return data
}

export async function uploadMonthlyPlanFiles(id, type, files) {
  let result = null

  for (let offset = 0; offset < files.length; offset += 10) {
    const formData = new FormData()

    for (const file of files.slice(offset, offset + 10)) {
      formData.append('files[]', file)
    }

    const response = await http.post(`/monthly-plans/${id}/documents/${type}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    result = response.data
  }

  return result
}

export async function deleteMonthlyPlanFile(planId, fileId) {
  const { data } = await http.delete(`/monthly-plans/${planId}/documents/${fileId}`)
  return data
}

export async function downloadMonthlyPlanFile(file) {
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
