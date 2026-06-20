<template>
  <section class="analytics-page">
    <header class="analytics-header">
      <div>
        <p class="eyebrow">Аналитика</p>
        <h1>ИИ-аналитик производства</h1>
        <p class="subtitle">
          Демонстрационный экран: как может выглядеть чат с моделью, которая анализирует сменные задания, ОТК и движение изделий.
        </p>
      </div>

      <div class="status-pill">
        <span class="status-dot"></span>
        Демо-режим
      </div>
    </header>

    <section class="analytics-layout">
      <aside class="card insights-panel">
        <h2>Сводка по производству</h2>

        <div class="metric-card">
          <span>Выполнение сменных заданий</span>
          <strong>87%</strong>
          <small>+6% к прошлой смене</small>
        </div>

        <div class="metric-card">
          <span>Предъявлено ОТК</span>
          <strong>142 шт.</strong>
          <small>За текущие сутки</small>
        </div>

        <div class="metric-card warning">
          <span>Забраковано</span>
          <strong>9 шт.</strong>
          <small>Основная причина: сварной шов</small>
        </div>

        <div class="metric-card">
          <span>Готово к отгрузке</span>
          <strong>34 шт.</strong>
          <small>3 проекта ожидают комплектации</small>
        </div>
      </aside>

      <section class="card chat-card">
        <div class="chat-topbar">
          <div>
            <h2>Чат с ИИ-моделью</h2>
            <p>Аналитика по текущему состоянию производства</p>
          </div>

          <span class="model-badge">Production AI</span>
        </div>

        <div class="chat-messages">
          <div class="message user">
            <div class="avatar">М</div>

            <div class="bubble">
              <p>
                Что сейчас тормозит производство по сменным заданиям?
              </p>
            </div>
          </div>

          <div class="message assistant">
            <div class="avatar">ИИ</div>

            <div class="bubble">
              <p>
                Основное ограничение сейчас находится на участке сборочно-сварочного цеха.
                По данным сменных заданий, раскрой выполнил план на 94%, но в сварку передано
                больше деталей, чем было закрыто сварочными постами за смену.
              </p>

              <ul>
                <li>Цех раскроя и обработки: план выполнен на 94%.</li>
                <li>Сборочно-сварочный цех: выполнение 76%.</li>
                <li>Покраска и отгрузка: выполнение 83%.</li>
              </ul>

              <p>
                Рекомендация: проверить загрузку сварочных постов №2 и №4, а также наличие
                незакрытых замечаний ОТК по проекту «Проект 123».
              </p>
            </div>
          </div>

          <div class="message user">
            <div class="avatar">М</div>

            <div class="bubble">
              <p>
                Где больше всего брака и по какой причине?
              </p>
            </div>
          </div>

          <div class="message assistant">
            <div class="avatar">ИИ</div>

            <div class="bubble">
              <p>
                Больше всего брака зафиксировано в сборочно-сварочном цехе:
                7 из 9 забракованных изделий относятся к операциям сварки.
              </p>

              <div class="mini-table">
                <div class="table-row table-head">
                  <span>Причина</span>
                  <span>Кол-во</span>
                  <span>Доля</span>
                </div>

                <div class="table-row">
                  <span>Нарушение сварного шва</span>
                  <span>5</span>
                  <span>56%</span>
                </div>

                <div class="table-row">
                  <span>Геометрия изделия</span>
                  <span>2</span>
                  <span>22%</span>
                </div>

                <div class="table-row">
                  <span>Повреждение покрытия</span>
                  <span>2</span>
                  <span>22%</span>
                </div>
              </div>

              <p>
                По актам несоответствия видно, что дефекты чаще появляются на изделиях,
                которые проходили через сварочный пост №2. Стоит отдельно проверить оснастку
                и технологическую карту по этим позициям.
              </p>
            </div>
          </div>

          <div class="message user">
            <div class="avatar">М</div>

            <div class="bubble">
              <p>
                Что можно отгрузить сегодня?
              </p>
            </div>
          </div>

          <div class="message assistant">
            <div class="avatar">ИИ</div>

            <div class="bubble">
              <p>
                Сегодня потенциально можно отгрузить 34 изделия. Из них 27 уже прошли ОТК,
                а 7 находятся в статусе «готово к проверке».
              </p>

              <p>
                Без дополнительных рисков можно планировать отгрузку по проектам:
              </p>

              <ul>
                <li>Проект 118 — 12 шт., ОТК закрыт.</li>
                <li>Проект 121 — 15 шт., ОТК закрыт.</li>
                <li>Проект 123 — 7 шт., требуется подпись контролера ОТК.</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="chat-input">
          <input
            type="text"
            value="Спроси меня о производстве, браке или отгрузке..."
            disabled
          />

          <button class="primary" disabled>
            Отправить
          </button>
        </div>
      </section>
    </section>
  </section>
</template>

<style scoped>
.analytics-page {
  display: grid;
  gap: 24px;
}

.analytics-header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: start;
  gap: 24px;
}

.eyebrow {
  margin: 0 0 6px;
  font-size: 13px;
  font-weight: 700;
  color: #2f80ed;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

h1,
h2,
p {
  margin-top: 0;
}

h1 {
  margin-bottom: 8px;
  font-size: 30px;
  line-height: 1.15;
  color: #17202a;
}

h2 {
  margin-bottom: 6px;
  color: #17202a;
}

.subtitle {
  margin-bottom: 0;
  color: #607080;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 999px;
  background: #eefbf3;
  color: #16703b;
  font-size: 13px;
  font-weight: 700;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #22c55e;
}

.analytics-layout {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  gap: 20px;
}

.insights-panel {
  align-self: start;
  display: grid;
  gap: 14px;
  padding: 20px;
}

.metric-card {
  display: grid;
  gap: 5px;
  padding: 14px;
  border: 1px solid #e1e7ef;
  border-radius: 14px;
  background: #f8fafc;
}

.metric-card span {
  color: #607080;
  font-size: 13px;
  font-weight: 700;
}

.metric-card strong {
  color: #17202a;
  font-size: 26px;
  line-height: 1;
}

.metric-card small {
  color: #607080;
}

.metric-card.warning {
  background: #fff8ed;
  border-color: #ffe1b3;
}

.chat-card {
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
  min-height: calc(100vh - 150px);
  overflow: hidden;
}

.chat-topbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 20px;
  border-bottom: 1px solid #e1e7ef;
}

.chat-topbar p {
  margin-bottom: 0;
  color: #607080;
}

.model-badge {
  padding: 7px 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #1f63b6;
  font-size: 12px;
  font-weight: 800;
}

.chat-messages {
  display: grid;
  gap: 18px;
  padding: 22px;
  overflow-y: auto;
  background:
    radial-gradient(circle at top left, rgba(47, 128, 237, 0.06), transparent 280px),
    #ffffff;
}

.message {
  display: flex;
  gap: 12px;
  max-width: 920px;
}

.message.user {
  justify-self: end;
  flex-direction: row-reverse;
}

.message.assistant {
  justify-self: start;
}

.avatar {
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1f63b6;
  font-size: 13px;
  font-weight: 800;
}

.message.user .avatar {
  background: #17202a;
  color: #ffffff;
}

.bubble {
  padding: 14px 16px;
  border-radius: 18px;
  background: #f4f6f8;
  color: #17202a;
  line-height: 1.55;
}

.message.user .bubble {
  background: #2f80ed;
  color: #ffffff;
}

.bubble p:last-child,
.bubble ul:last-child {
  margin-bottom: 0;
}

.bubble ul {
  margin: 10px 0;
  padding-left: 20px;
}

.mini-table {
  margin: 12px 0;
  overflow: hidden;
  border: 1px solid #e1e7ef;
  border-radius: 12px;
  background: #ffffff;
}

.table-row {
  display: grid;
  grid-template-columns: 1fr 80px 80px;
  gap: 12px;
  padding: 10px 12px;
  border-bottom: 1px solid #edf1f5;
}

.table-row:last-child {
  border-bottom: none;
}

.table-head {
  background: #f8fafc;
  color: #607080;
  font-size: 13px;
  font-weight: 800;
}

.chat-input {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 12px;
  padding: 18px 20px;
  border-top: 1px solid #e1e7ef;
  background: #ffffff;
}

.chat-input input:disabled {
  background: #f8fafc;
  color: #607080;
  cursor: not-allowed;
}

@media (max-width: 1100px) {
  .analytics-layout {
    grid-template-columns: 1fr;
  }

  .chat-card {
    min-height: 700px;
  }
}

@media (max-width: 768px) {
  .analytics-header,
  .chat-input {
    grid-template-columns: 1fr;
  }

  .status-pill {
    justify-self: start;
  }

  .chat-topbar {
    display: grid;
  }

  .message,
  .message.user {
    max-width: 100%;
  }

  .table-row {
    grid-template-columns: 1fr;
  }
}
</style>