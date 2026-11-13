<script setup>
// Price history line chart for the Car detail page. OWNER: Yati (D/HD feature).
// Usage: <PriceHistoryChart :listing-id="car.listing_id" :current-price="car.price" />
// Data: price_history.php -> every price the car has had. Drawn with Chart.js as a stepped line
// (a price stays the same until the seller changes it). Reloads if the price changes.
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import {
  CategoryScale,
  Chart,
  Filler,
  LinearScale,
  LineController,
  LineElement,
  PointElement,
  Tooltip
} from 'chart.js'
import { apiGet } from '../services/api'
import { formatRM, priceChange } from '../services/priceChange'

Chart.register(
  LineController,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Filler
)

const props = defineProps({
  listingId: { type: [Number, String], required: true },
  currentPrice: { type: Number, required: true }
})

const history = ref(null)
const loading = ref(false)
const errorMessage = ref('')
const canvas = ref(null)
let chart = null

const firstPrice = computed(() => (history.value ? history.value.points[0].price : 0))
const overall = computed(() => priceChange(firstPrice.value, props.currentPrice))

function shortDate(mysqlDate) {
  const date = new Date(String(mysqlDate).replace(' ', 'T'))
  return date.toLocaleDateString('en-MY', { day: 'numeric', month: 'short' })
}

async function load() {
  loading.value = true
  errorMessage.value = ''
  try {
    history.value = await apiGet('price_history.php', { listing_id: props.listingId })
  } catch (e) {
    errorMessage.value = e.message
    history.value = null
  } finally {
    loading.value = false
  }
}

// Colours come from the design tokens in main.css (--dd-chart-1 etc.), never hard-coded
function token(name, fallback) {
  const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim()
  return value || fallback
}

function draw() {
  if (chart) chart.destroy()
  chart = null
  if (!canvas.value || !history.value || !history.value.changes) return
  const points = history.value.points
  const lastIndex = points.length - 1
  const line = token('--dd-chart-1', 'blue')
  const fillColour = token('--dd-primary-50', 'transparent')
  const nowColour = token('--dd-success', 'green')
  chart = new Chart(canvas.value, {
    type: 'line',
    data: {
      labels: points.map((p, i) => (i === lastIndex ? 'Now' : shortDate(p.at))),
      datasets: [
        {
          label: 'Price',
          data: points.map((p) => p.price),
          stepped: 'before',
          borderColor: line,
          backgroundColor: fillColour,
          fill: true,
          pointRadius: 4,
          pointBackgroundColor: points.map((p, i) => (i === lastIndex ? nowColour : line))
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (item) => formatRM(item.parsed.y) } }
      },
      scales: {
        y: { ticks: { callback: (value) => formatRM(value) } }
      }
    }
  })
}

watch(
  () => [props.listingId, props.currentPrice],
  async () => {
    await load()
    draw()
  },
  { immediate: true }
)
watch(canvas, draw)

onBeforeUnmount(() => {
  if (chart) chart.destroy()
})
</script>

<template>
  <div class="price-history">
    <h2 class="h3 mb-2">Price history</h2>

    <p v-if="loading && !history" class="text-muted small mb-0">Loading price history...</p>
    <p v-else-if="errorMessage" class="text-muted small mb-0">{{ errorMessage }}</p>

    <template v-else-if="history">
      <p v-if="!history.changes" class="text-muted small mb-0">
        The price hasn't changed since this car was listed. Watch it to be told if it drops.
      </p>
      <template v-else>
        <p class="small mb-3">
          Changed {{ history.changes }} {{ history.changes === 1 ? 'time' : 'times' }}.
          <span v-if="overall.direction === 'down'" class="text-success fw-semibold">
            Down {{ formatRM(overall.amount) }} ({{ overall.percent }}%) from
            {{ formatRM(firstPrice) }}.
          </span>
          <span v-else-if="overall.direction === 'up'" class="text-danger">
            Up {{ formatRM(overall.amount) }} from {{ formatRM(firstPrice) }}.
          </span>
          Lowest: {{ formatRM(history.lowest) }}.
        </p>
        <div class="chart-box">
          <canvas
            ref="canvas"
            role="img"
            :aria-label="`Price history: lowest ${formatRM(history.lowest)}`"
          ></canvas>
        </div>
      </template>
    </template>
  </div>
</template>

<style scoped>
.chart-box {
  position: relative;
  height: 220px;
}
</style>
