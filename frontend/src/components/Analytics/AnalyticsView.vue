<template>
  <section class="analytics-page">
    <header class="analytics-header">
      <div>
        <p class="eyebrow">Аналитика</p>
        <h1>Движение деталей по заказу</h1>
      </div>
    </header>

    <section class="card filters-card">
      <label>
        Заказ
        <select v-model="selectedOrderId">
          <option
            v-for="order in orders"
            :key="order.id"
            :value="order.id"
          >
            {{ order.number }} — {{ order.name }}
          </option>
        </select>
      </label>

      <div class="order-summary">
        <span>Проект</span>
        <strong>{{ selectedOrder.project }}</strong>
      </div>

      <div class="order-summary">
        <span>Изделий в заказе</span>
        <strong>{{ selectedOrder.quantity }} шт.</strong>
      </div>

      <div class="order-summary">
        <span>Статус</span>
        <strong>{{ selectedOrder.status }}</strong>
      </div>
    </section>

    <section class="analytics-layout">
      <section class="card chart-card">
        <div class="card-title-row">
          <div>
            <h2>Распределение деталей по цехам</h2>
            <p class="muted">
              Количество деталей вчера и сегодня по выбранному заказу.
            </p>
          </div>
        </div>

        <div class="chart-wrap">
          <Bar :data="chartData" :options="chartOptions" />
        </div>
      </section>

      <aside class="card details-card">
        <h2>Сводка по цехам</h2>

        <div
          v-for="row in selectedOrder.workshops"
          :key="row.workshop"
          class="workshop-row"
        >
          <div>
            <strong>{{ row.workshop }}</strong>
            <span>{{ row.description }}</span>
          </div>

          <div class="workshop-numbers">
            <small>Вчера: {{ row.yesterday }} шт.</small>
            <small>Сегодня: {{ row.today }} шт.</small>

            <b :class="getDeltaClass(row.today - row.yesterday)">
              {{ formatDelta(row.today - row.yesterday) }}
            </b>
          </div>
        </div>
      </aside>
    </section>

    <section class="card ai-comment-card">
      <div class="ai-icon">ИИ</div>

      <div>
        <h2>Комментарий ИИ-аналитика</h2>
        <p>
          По заказу <strong>{{ selectedOrder.number }}</strong> видно, что основной
          накопленный объём сейчас находится в цехе
          <strong>{{ bottleneckWorkshop.workshop }}</strong>.
          За сутки там изменение составило
          <strong>{{ formatDelta(bottleneckWorkshop.today - bottleneckWorkshop.yesterday) }}</strong>.
          Это может говорить о накоплении очереди перед следующим этапом производства.
        </p>
      </div>
    </section>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import {
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Legend,
  LinearScale,
  Tooltip
} from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend
)

const selectedOrderId = ref(1)

const orders = [
  {
    id: 1,
    number: 'ЗК-00124',
    name: 'Металлоконструкции секции А',
    project: 'Проект 123',
    quantity: 120,
    status: 'В производстве',
    workshops: [
      {
        workshop: 'Цех раскроя и обработки',
        yesterday: 42,
        today: 28,
        description: 'Детали после резки и обработки'
      },
      {
        workshop: 'Сборочно-сварочный цех',
        yesterday: 31,
        today: 47,
        description: 'Детали и узлы в сборке/сварке'
      },
      {
        workshop: 'Цех покраски и отгрузки',
        yesterday: 18,
        today: 26,
        description: 'Изделия на покраске и подготовке к отгрузке'
      },
      {
        workshop: 'Готово к отгрузке',
        yesterday: 9,
        today: 19,
        description: 'Готовая продукция после ОТК'
      }
    ]
  },
  {
    id: 2,
    number: 'ЗК-00125',
    name: 'Фермы покрытия',
    project: 'Проект 118',
    quantity: 80,
    status: 'Ожидает покраску',
    workshops: [
      {
        workshop: 'Цех раскроя и обработки',
        yesterday: 20,
        today: 12,
        description: 'Детали после резки и обработки'
      },
      {
        workshop: 'Сборочно-сварочный цех',
        yesterday: 34,
        today: 29,
        description: 'Детали и узлы в сборке/сварке'
      },
      {
        workshop: 'Цех покраски и отгрузки',
        yesterday: 11,
        today: 27,
        description: 'Изделия на покраске и подготовке к отгрузке'
      },
      {
        workshop: 'Готово к отгрузке',
        yesterday: 4,
        today: 12,
        description: 'Готовая продукция после ОТК'
      }
    ]
  },
  {
    id: 3,
    number: 'ЗК-00126',
    name: 'Колонны КМ',
    project: 'Проект 121',
    quantity: 64,
    status: 'Сборка',
    workshops: [
      {
        workshop: 'Цех раскроя и обработки',
        yesterday: 36,
        today: 22,
        description: 'Детали после резки и обработки'
      },
      {
        workshop: 'Сборочно-сварочный цех',
        yesterday: 14,
        today: 30,
        description: 'Детали и узлы в сборке/сварке'
      },
      {
        workshop: 'Цех покраски и отгрузки',
        yesterday: 4,
        today: 7,
        description: 'Изделия на покраске и подготовке к отгрузке'
      },
      {
        workshop: 'Готово к отгрузке',
        yesterday: 0,
        today: 2,
        description: 'Готовая продукция после ОТК'
      }
    ]
  }
]

const selectedOrder = computed(() => {
  return orders.find(order => order.id === selectedOrderId.value) || orders[0]
})

const chartData = computed(() => ({
  labels: selectedOrder.value.workshops.map(row => row.workshop),
  datasets: [
    {
      label: 'Вчера',
      data: selectedOrder.value.workshops.map(row => row.yesterday),
      backgroundColor: 'rgba(148, 163, 184, 0.65)',
      borderColor: 'rgba(100, 116, 139, 1)',
      borderWidth: 1,
      borderRadius: 8
    },
    {
      label: 'Сегодня',
      data: selectedOrder.value.workshops.map(row => row.today),
      backgroundColor: 'rgba(47, 128, 237, 0.75)',
      borderColor: 'rgba(31, 99, 182, 1)',
      borderWidth: 1,
      borderRadius: 8
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    },
    tooltip: {
      callbacks: {
        label(context) {
          return `${context.dataset.label}: ${context.parsed.y} шт.`
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0
      },
      title: {
        display: true,
        text: 'Количество деталей, шт.'
      }
    },
    x: {
      ticks: {
        maxRotation: 0,
        autoSkip: false
      }
    }
  }
}

const bottleneckWorkshop = computed(() => {
  return [...selectedOrder.value.workshops].sort((a, b) => b.today - a.today)[0]
})

function formatDelta(value) {
  if (value > 0) {
    return `+${value} шт.`
  }

  if (value < 0) {
    return `${value} шт.`
  }

  return '0 шт.'
}

function getDeltaClass(value) {
  if (value > 0) {
    return 'delta-up'
  }

  if (value < 0) {
    return 'delta-down'
  }

  return 'delta-zero'
}
</script>

<style scoped>
.analytics-page {
  display: grid;
  gap: 22px;
}

.analytics-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
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

.filters-card {
  display: grid;
  grid-template-columns: minmax(280px, 1fr) repeat(3, auto);
  align-items: end;
  gap: 16px;
  padding: 18px;
}

.order-summary {
  display: grid;
  gap: 4px;
  padding: 10px 14px;
  border-radius: 12px;
  background: #f8fafc;
}

.order-summary span {
  color: #607080;
  font-size: 12px;
  font-weight: 700;
}

.order-summary strong {
  color: #17202a;
  white-space: nowrap;
}

.analytics-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
  gap: 20px;
}

.chart-card {
  min-height: 500px;
  padding: 20px;
}

.chart-card canvas {
  min-height: 390px;
}

.badge {
  padding: 7px 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #1f63b6;
  font-size: 12px;
  font-weight: 800;
}

.details-card {
  align-self: start;
  display: grid;
  gap: 12px;
  padding: 20px;
}

.workshop-row {
  display: grid;
  gap: 12px;
  padding: 14px;
  border: 1px solid #e1e7ef;
  border-radius: 14px;
  background: #f8fafc;
}

.workshop-row strong,
.workshop-row span {
  display: block;
}

.workshop-row strong {
  color: #17202a;
}

.workshop-row span {
  margin-top: 4px;
  color: #607080;
  font-size: 13px;
  line-height: 1.4;
}

.workshop-numbers {
  display: grid;
  gap: 4px;
}

.workshop-numbers small {
  color: #607080;
}

.workshop-numbers b {
  font-size: 15px;
}

.delta-up {
  color: #b45309;
}

.delta-down {
  color: #188038;
}

.delta-zero {
  color: #607080;
}

.ai-comment-card {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 16px;
  padding: 20px;
}

.ai-icon {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: #eef4ff;
  color: #1f63b6;
  font-weight: 800;
}

.ai-comment-card p {
  margin-bottom: 0;
  color: #3c4856;
  line-height: 1.6;
}

.chart-card {
  padding: 20px;
}

.chart-wrap {
  position: relative;
  height: 420px;
  width: 100%;
}

.chart-card canvas {
  max-height: 420px;
}

@media (max-width: 1200px) {
  .filters-card,
  .analytics-layout {
    grid-template-columns: 1fr;
  }

  .order-summary strong {
    white-space: normal;
  }
}

@media (max-width: 768px) {
  .analytics-header,
  .ai-comment-card {
    display: grid;
    grid-template-columns: 1fr;
  }

  .chart-card {
    min-height: 560px;
  }
}
</style>