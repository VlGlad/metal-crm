<template>
  <section class="stub-page">
    <div class="stub-card card">
      <div class="stub-icon">✅</div>

      <h1>Контролеры ОТК</h1>

      <p>
        Раздел для контролеров ОТК пока в разработке. Здесь можно будет
        проверять выполненные работы, фиксировать брак, принимать детали
        между этапами и закрывать контроль качества.
      </p>

      <button class="secondary" disabled>
        Скоро будет доступно
      </button>
    </div>
  </section>
</template>

<style scoped>
.stub-page {
  min-height: calc(100vh - 64px);
  display: grid;
  place-items: center;
}

.stub-card {
  max-width: 620px;
  padding: 40px;
  text-align: center;
}

.stub-icon {
  width: 72px;
  height: 72px;
  display: grid;
  place-items: center;
  margin: 0 auto 20px;
  border-radius: 20px;
  background: #eef4ff;
  font-size: 34px;
}

h1 {
  margin: 0;
  font-size: 30px;
  color: #17202a;
}

p {
  margin: 14px 0 24px;
  color: #607080;
  line-height: 1.6;
}
</style>