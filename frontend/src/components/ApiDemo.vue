<template>
  <main class="page">
    <h1>Vue 3 + Symfony API</h1>

    <section class="card">
      <h2>GET /api/hello</h2>

      <button @click="loadMessage">
        Запросить backend
      </button>

      <p v-if="loading">Загрузка...</p>

      <p v-if="message">
        {{ message }}
      </p>

      <small v-if="time">
        Время backend: {{ time }}
      </small>
    </section>

    <section class="card">
      <h2>POST /api/contact</h2>

      <input
        v-model="name"
        type="text"
        placeholder="Введите имя"
      >

      <button @click="sendForm">
        Отправить
      </button>

      <pre v-if="response">{{ response }}</pre>
    </section>
  </main>
</template>

<script setup>
import { ref } from 'vue'

const API_URL = 'http://localhost:8081/api'

const loading = ref(false)
const message = ref('')
const time = ref('')
const name = ref('')
const response = ref('')

async function loadMessage() {
  loading.value = true

  try {
    const res = await fetch(`${API_URL}/hello`)
    const data = await res.json()

    message.value = data.message
    time.value = data.time
  } catch (error) {
    message.value = 'Ошибка запроса к API'
    console.error(error)
  } finally {
    loading.value = false
  }
}

async function sendForm() {
  try {
    const res = await fetch(`${API_URL}/contact`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        name: name.value,
      }),
    })

    const data = await res.json()

    response.value = JSON.stringify(data, null, 2)
  } catch (error) {
    response.value = 'Ошибка отправки формы'
    console.error(error)
  }
}
</script>

<style scoped>
.page {
  max-width: 800px;
  margin: 40px auto;
  font-family: Arial, sans-serif;
}

.card {
  margin-top: 24px;
  padding: 24px;
  border: 1px solid #ddd;
  border-radius: 12px;
}

button {
  display: block;
  margin-top: 12px;
  padding: 10px 16px;
  cursor: pointer;
}

input {
  display: block;
  margin-top: 12px;
  padding: 10px;
  width: 100%;
  max-width: 300px;
}

pre {
  margin-top: 16px;
  padding: 16px;
  background: #f5f5f5;
  overflow-x: auto;
}
</style>