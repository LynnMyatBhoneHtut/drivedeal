<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue'

import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Legend,
  Tooltip
} from 'chart.js'

// Register the Chart.js features needed for a bar chart
Chart.register(
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Legend,
  Tooltip
)

const props = defineProps({
  cars: {
    type: Array,
    default: () => []
  },
  showBrand: {
    type: Boolean,
    default: false
  }
})

const chartCanvas = ref(null)
let chartInstance = null

// Limit the comparison to three vehicles
const selectedCars = computed(() => props.cars.slice(0, 3))

// Compare normalised scores using the same 0-100 scale
const criteria = computed(() => {
  const items = [
    { key: 'price', label: 'Price' },
    { key: 'mileage', label: 'Mileage' },
    { key: 'age', label: 'Vehicle Age' }
  ]

  if (props.showBrand) {
    items.push({ key: 'make', label: 'Brand Match' })
  }

  return items
})

const chartColors = [
  '#c2410c',
  '#1c2228',
  '#0f766e'
]

function getScore(car, key) {
  const value = Number(car.scoreBreakdown?.[key])

  return Number.isFinite(value)
    ? Math.max(0, Math.min(100, value))
    : 0
}

function getChartData() {
  return {
    labels: criteria.value.map(item => item.label),
    datasets: selectedCars.value.map((car, index) => ({
      label: `${car.make} ${car.model}`,
      data: criteria.value.map(item => getScore(car, item.key)),
      backgroundColor: chartColors[index],
      borderRadius: 5
    }))
  }
}

// Create or update the Chart.js chart
function renderChart() {
  if (!chartCanvas.value) return

  const data = getChartData()

  if (chartInstance) {
    chartInstance.data = data
    chartInstance.update()
    return
  }

  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
    data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        },
        tooltip: {
          enabled: true
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          min: 0,
          max: 100,
          ticks: {
            stepSize: 20
          },
          title: {
            display: true,
            text: 'Suitability Score (0-100)'
          }
        }
      }
    }
  })
}

// Vue lifecycle: create the chart when mounted
onMounted(() => {
  renderChart()
})

// Refresh the chart whenever recommendations change
watch(
  [() => props.cars, () => props.showBrand],
  renderChart,
  { deep: true, flush: 'post' }
)

// Remove the chart when Vue destroys the component
onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
</script>

<template>
  <section class="comparison-section p-4 mt-4">
    <h3 class="h4 fw-bold mb-2">
      Interactive Car Comparison
    </h3>

    <p class="text-muted mb-4">
      Compare how the recommended cars perform across
      different criteria. The chart updates when you
      adjust your preferences.
    </p>

    <div v-if="!selectedCars.length" class="alert alert-info">
      No cars are available for comparison.
    </div>

    <template v-else>
      <div class="chart-container">
        <canvas ref="chartCanvas" role="img" aria-label="Bar chart comparing vehicle suitability scores"></canvas>
      </div>

      <!-- Accessible alternative to the chart -->
      <details class="mt-3">
        <summary class="fw-semibold">
          View comparison scores as a table
        </summary>

        <div class="table-responsive mt-3">
          <table class="table table-bordered table-sm">
            <caption>
              Car suitability scores out of 100
            </caption>

            <thead>
              <tr>
                <th scope="col">Criterion</th>
                <th v-for="car in selectedCars" :key="car.listing_id" scope="col">
                  {{ car.make }} {{ car.model }}
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="item in criteria" :key="item.key">
                <th scope="row">{{ item.label }}</th>
                <td v-for="car in selectedCars" :key="car.listing_id">
                  {{ Math.round(getScore(car, item.key)) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </details>
    </template>
  </section>
</template>

<style scoped>
.comparison-section {
  background: white;
  border: 1px solid #e9e5e1;
  border-radius: 14px;
}

.chart-container {
  position: relative;
  width: 100%;
  height: 350px;
}

summary {
  cursor: pointer;
  color: #9a3412;
}

@media (max-width: 767px) {
  .comparison-section {
    padding: 18px !important;
  }

  .chart-container {
    height: 300px;
  }
}
</style>
