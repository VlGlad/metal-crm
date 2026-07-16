import { http } from '../http'

export async function getTaskAssignments(month = '') { const { data } = await http.get('/task-assignments', { params: month ? { month } : {} }); return data }
export async function createTaskAssignment(payload) { const { data } = await http.post('/task-assignments', payload); return data }
export async function updateTaskAssignment(id, payload) { const { data } = await http.put(`/task-assignments/${id}`, payload); return data }
export async function startTaskAssignment(id) { const { data } = await http.post(`/task-assignments/${id}/start`); return data }
export async function completeTaskAssignment(id, comment = '') { const { data } = await http.post(`/task-assignments/${id}/complete`, { comment }); return data }
export async function cancelTaskAssignment(id, comment = '') { const { data } = await http.post(`/task-assignments/${id}/cancel`, { comment }); return data }