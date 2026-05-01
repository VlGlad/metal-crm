<template>
  <section class="alert" :class="type">
    {{ message }}
  </section>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'success'
  },
  message: {
    type: String,
    required: true
  }
})
</script>

<style scoped>
.alert {
  margin-bottom: 16px;
  padding: 12px 14px;
  border-radius: 12px;
  font-weight: 700;
}

.alert.error {
  background: #fff0f0;
  color: #b42318;
}

.alert.success {
  background: #eefbf3;
  color: #16703b;
}
</style>