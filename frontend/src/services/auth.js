// src/api/auth.js
import { http } from './http'

export async function login(email, password) {
  try {
    const response = await http.post('/token', {
      email,
      password,
    })

    const token = response.data.token

    if (!token) {
      throw new Error('Сервер не вернул access token')
    }

    localStorage.setItem('access_token', token)

    return response.data
  } catch (error) {
    if (error.response?.status === 401) {
      throw new Error('Неверный email или пароль')
    }

    if (error.response?.status === 400) {
      throw new Error('Заполните email и пароль')
    }

    throw new Error('Не удалось войти. Попробуйте позже')
  }
}

export async function getCurrentUser() {
  const { data } = await http.get('/me')
  return data
}

export function clearSession() {
  localStorage.removeItem('access_token')
}

export async function logout() {
  try {
    if (getToken()) {
      await http.post('/logout')
    }
  } finally {
    clearSession()
  }
}

export function getToken() {
  return localStorage.getItem('access_token')
}

export function isAuthenticated() {
  return !!getToken()
}