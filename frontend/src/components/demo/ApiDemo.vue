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
import { getHello, sendContact } from '../../services/demo/demo.service'

const loading = ref(false)
const message = ref('')
const time = ref('')
const name = ref('')
const response = ref('')
const error = ref('')

async function loadMessage() {
  loading.value = true
  error.value = ''

  try {
    const data = await getHello()

    message.value = data.message
    time.value = data.time
  } catch (e) {
    error.value = 'Ошибка запроса к API'
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function sendForm() {
  error.value = ''

  try {
    const data = await sendContact({
      name: name.value,
    })

    response.value = JSON.stringify(data, null, 2)
  } catch (e) {
    error.value = 'Ошибка отправки формы'
    console.error(e)
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