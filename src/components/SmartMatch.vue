<script setup>
import { computed, ref, toRef, watch } from 'vue'
import { useCarRecommendations } from '../composables/useCarRecommendations'
import CarComparisonChart from './CarComparisonChart.vue'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  cars: {
    type: Array,
    required: true
  }
})

// User preferences
const preferences = ref({
  budget: 80000,
  maxMileage: 100000,
  preferredMake: '',
  weights: {
    price: 40,
    mileage: 30,
    age: 20,
    make: 10
  }
})

// Retrieve car brands from the available listings
const availableMakes = computed(() => {
  const makes = props.cars
    .map(car => car.make)
    .filter(Boolean)

  return [...new Set(makes)].sort()
})

// Weight controls
const weightFields = [
  { key: 'price', label: 'Price' },
  { key: 'mileage', label: 'Mileage' },
  { key: 'age', label: 'Vehicle Age' },
  { key: 'make', label: 'Brand' }
]

// Use our custom recommendation composable
const { recommendations } = useCarRecommendations(
  toRef(props, 'cars'),
  preferences
)


const selectedCarIds = ref([])

const comparisonSearch = ref('')

// Filter comparison options while keeping selected cars visible
const filteredComparisonCars = computed(() => {
  const search = comparisonSearch.value.trim().toLowerCase()

  return recommendations.value.filter(car => {
    const carName = `${car.make} ${car.model}`.toLowerCase()
    const isSelected = selectedCarIds.value.includes(
      String(car.listing_id)
    )

    return carName.includes(search) || isSelected
  })
})

const comparisonInitialized = ref(false)

// Select the first three cars when data becomes available
watch(recommendations, (cars) => {
  const availableIds = new Set(
    cars.map(car => String(car.listing_id))
  )

  if (!comparisonInitialized.value && cars.length) {
    selectedCarIds.value = cars
      .slice(0, 3)
      .map(car => String(car.listing_id))

    comparisonInitialized.value = true
  } else {
    selectedCarIds.value = selectedCarIds.value.filter(
      id => availableIds.has(id)
    )
  }
}, { immediate: true })

// Get the cars selected for comparison
const comparisonCars = computed(() =>
  recommendations.value.filter(car =>
    selectedCarIds.value.includes(String(car.listing_id))
  )
)

// Allow users to compare between two and three cars
function toggleCompare(listingId, checked) {
  const id = String(listingId)

  if (checked && selectedCarIds.value.length < 3) {
    selectedCarIds.value = [...selectedCarIds.value, id]
  } else if (!checked && selectedCarIds.value.length > 2) {
    selectedCarIds.value = selectedCarIds.value.filter(
      selectedId => selectedId !== id
    )
  }
}

function formatNumber(value) {
  return Number(value).toLocaleString('en-MY')
}

function resetPreferences() {
  preferences.value = {
    budget: 80000,
    maxMileage: 100000,
    preferredMake: '',
    weights: {
      price: 40,
      mileage: 30,
      age: 20,
      make: 10
    }
  }
}

const activeWeightTotal = computed(() => {
  const weights = preferences.value.weights
  const preferredMake = preferences.value.preferredMake

  return (
    weights.price +
    weights.mileage +
    weights.age +
    (preferredMake ? weights.make : 0)
  )
})

// Fields used to explain the weighted recommendation score
const scoreCriteria = [
  { key: 'price', label: 'Price' },
  { key: 'mileage', label: 'Mileage' },
  { key: 'age', label: 'Vehicle Age' },
  { key: 'make', label: 'Brand Preference' }
]

// Show only criteria which contribute to the current match score
function getScoreDetails(car) {
  const total = activeWeightTotal.value

  if (total <= 0) return []

  return scoreCriteria
    .filter(field => {
      const isActive = Number(preferences.value.weights[field.key]) > 0
      const isRelevant = field.key !== 'make' ||
        Boolean(preferences.value.preferredMake)

      return isActive && isRelevant
    })
    .map(field => {
      const weight = Number(preferences.value.weights[field.key])
      const score = Number(car.scoreBreakdown?.[field.key] ?? 0)

      return {
        key: field.key,
        label: field.label,
        score: score.toFixed(1),
        weight: ((weight / total) * 100).toFixed(1),
        contribution: ((score * weight) / total).toFixed(1)
      }
    })
}

</script>

<template>
  <section class="smart-match p-3 p-md-4 mb-4 mb-lg-5" aria-labelledby="smartMatchTitle">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div class="d-flex align-items-start gap-3">
        <span class="smart-icon"><AppIcon name="sparkles" :size="24" /></span>
        <div>
          <span class="dd-eyebrow mb-1">Personalised search</span>
          <h2 id="smartMatchTitle" class="h3 mb-1">Smart Match</h2>
          <p class="text-muted mb-0">
            Tell us what matters to you and we'll rank the cars that fit best.
          </p>
        </div>
      </div>

      <button type="button" class="btn btn-outline-primary btn-sm" @click="resetPreferences">
        Reset preferences
      </button>
    </div>

    <!-- Buyer preferences -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-6">
        <label for="matchBudget" class="form-label fw-semibold">
          Maximum Budget
        </label>

        <input
id="matchBudget" v-model.number="preferences.budget" type="range" class="form-range" min="30000"
          max="160000" step="5000" />

        <p class="fw-bold match-value mb-0">
          RM {{ formatNumber(preferences.budget) }}
        </p>
      </div>

      <div class="col-12 col-md-6">
        <label for="matchMileage" class="form-label fw-semibold">
          Preferred Maximum Mileage
        </label>

        <input
id="matchMileage" v-model.number="preferences.maxMileage" type="range" class="form-range" min="10000"
          max="200000" step="5000" />

        <p class="fw-bold match-value mb-0">
          {{ formatNumber(preferences.maxMileage) }} km
        </p>
      </div>

      <div class="col-12 col-md-6">
        <label for="matchBrand" class="form-label fw-semibold">
          Preferred Brand
        </label>

        <select id="matchBrand" v-model="preferences.preferredMake" class="form-select">
          <option value="">Any Brand</option>
          <option v-for="make in availableMakes" :key="make" :value="make">
            {{ make }}
          </option>
        </select>
      </div>
    </div>

    <!-- Adjustable weights -->
    <div class="weights-panel p-3 mb-4">
      <h3 class="h5 fw-bold mb-2">
        What Matters Most to You?
      </h3>

      <p class="text-muted small">
        Adjust the importance of each factor.
        The scoring weights are automatically normalised.
      </p>

      <div class="row g-3">
        <div v-for="field in weightFields" :key="field.key" class="col-12 col-md-6">
          <label :for="`weight-${field.key}`" class="form-label d-flex justify-content-between">
            <span>{{ field.label }}</span>
            <span>{{ preferences.weights[field.key] }}</span>
          </label>


          <input
:id="`weight-${field.key}`" v-model.number="preferences.weights[field.key]" type="range"
            class="form-range" min="0" max="100" step="5" :disabled="field.key === 'make' && !preferences.preferredMake"
            :aria-describedby="field.key === 'make' ? 'brandWeightHint' : undefined" />

          <p v-if="field.key === 'make'" id="brandWeightHint" class="small text-muted mt-1 mb-0">
            {{
              preferences.preferredMake
                ? 'Adjust how important your preferred brand is.'
                : 'Choose a Preferred Brand above to enable this slider.'
            }}
          </p>

        </div>
      </div>
    </div>

    <!-- Warning when no scoring criteria are active -->
    <div v-if="activeWeightTotal === 0" class="alert alert-warning" role="status">
      Please select at least one scoring criterion
      to generate personalised car recommendations.
    </div>


    <!-- Recommendation results -->
    <div v-if="activeWeightTotal > 0">
      <h3 class="h5 mb-2">
        Your Recommended Cars
      </h3>

      <p class="text-muted small mb-3" role="status">
        Ranked according to your selected preferences.
        Scores represent suitability, not a prediction of satisfaction.
      </p>

      <div v-if="recommendations.length" class="row g-3">
        <div v-for="car in recommendations.slice(0, 3)" :key="car.listing_id" class="col-12 col-lg-4">
          <article class="recommendation-card h-100 p-3">
            <div
class="d-flex justify-content-between
                        align-items-start gap-2 mb-2">
              <h4 class="h5 fw-bold mb-0">
                {{ car.make }} {{ car.model }}
              </h4>

              <span class="score-label">
                {{ car.matchScore }}/100
              </span>
            </div>

            <div
class="progress mb-3" style="height: 8px" role="progressbar" :aria-valuenow="car.matchScore"
              aria-valuemin="0" aria-valuemax="100" :aria-label="`Match score for ${car.make} ${car.model}`">
              <div class="progress-bar match-progress" :style="{ width: `${car.matchScore}%` }"></div>
            </div>

            <p class="fw-bold mb-1">
              RM {{ formatNumber(car.price) }}
            </p>

            <p class="text-muted small">
              {{ car.year }} Ã‚Â·
              {{ formatNumber(car.mileage_km) }} km
            </p>

            <ul class="small text-muted ps-3 mb-3">
              <li v-for="reason in car.matchReasons" :key="reason">
                {{ reason }}
              </li>
            </ul>


            <!-- Explain each recommendation score -->
            <details class="score-details mb-3">
              <summary class="fw-semibold">
                Why this score?
              </summary>

              <p class="small text-muted mt-3 mb-2">
                Your match score is calculated using the suitability
                of this car and the importance you assigned to each factor.
              </p>

              <div
v-for="factor in getScoreDetails(car)" :key="factor.key"
                class="d-flex justify-content-between gap-2 border-bottom py-2">
                <div class="small">
                  <strong>{{ factor.label }}</strong>
                  <div class="text-muted">
                    {{ factor.score }}/100 Ãƒâ€” {{ factor.weight }}% importance
                  </div>
                </div>

                <strong class="small text-nowrap">
                  {{ factor.contribution }} pts
                </strong>
              </div>

              <p class="small fw-semibold mt-3 mb-0">
                Final match score: {{ car.matchScore }}/100
              </p>

              <p class="small text-muted mb-0">
                Individual contributions are shown rounded to one decimal place.
              </p>
            </details>


            <router-link
:to="{
              name: 'car-detail',
              params: { id: car.listing_id }
            }" class="btn btn-outline-primary w-100">
              View Details
            </router-link>
          </article>
        </div>
      </div>

      <div v-else class="alert alert-info">
        No valid car listings are available for recommendations.
      </div>
    </div>



    <!-- Select cars for comparison -->
    <div v-if="activeWeightTotal > 0" class="weights-panel p-3 mt-4">
      <h3 class="h5 fw-bold mb-2">
        Choose Cars to Compare
      </h3>


      <p class="text-muted small mb-2" role="status">
        {{ selectedCarIds.length }} of 3 cars selected.

        <span v-if="selectedCarIds.length === 3">
          Untick one selected car to choose a different car.
        </span>

        <span v-else>
          Tick another car below to add it to your comparison.
        </span>
      </p>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <span v-for="car in comparisonCars" :key="car.listing_id" class="dd-chip dd-chip-active">
          {{ car.make }} {{ car.model }}
        </span>
      </div>


      <div class="mb-3">
        <label for="comparisonSearch" class="form-label fw-semibold">
          Search Cars to Compare
        </label>

        <input
id="comparisonSearch" v-model="comparisonSearch" type="search" class="form-control"
          placeholder="Search by brand or model, e.g. Honda Civic" />
      </div>

      <div class="d-flex flex-wrap gap-4">
        <div v-for="car in filteredComparisonCars" :key="car.listing_id" class="form-check">
          <input
:id="`compare-${car.listing_id}`" type="checkbox" class="form-check-input" :checked="selectedCarIds.includes(String(car.listing_id))
            " :disabled="(selectedCarIds.includes(String(car.listing_id)) &&
              selectedCarIds.length <= 2) ||
              (!selectedCarIds.includes(String(car.listing_id)) &&
                selectedCarIds.length >= 3)
              " @change="
              toggleCompare(car.listing_id, $event.target.checked)
              " />

          <label :for="`compare-${car.listing_id}`" class="form-check-label">
            {{ car.make }} {{ car.model }}
          </label>
        </div>
      </div>
    </div>

    <!-- Interactive comparison -->

    <CarComparisonChart
v-if="activeWeightTotal > 0" :cars="comparisonCars"
      :show-brand="Boolean(preferences.preferredMake)" />

  </section>
</template>

<style scoped>
.smart-match {
  background: var(--dd-primary-50);
  border: 1px solid var(--dd-primary-100);
  border-radius: var(--dd-radius-lg);
}

.smart-icon {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: var(--dd-radius);
  background: var(--dd-gradient);
  color: var(--dd-white);
  box-shadow: var(--dd-shadow);
}

.match-value {
  color: var(--dd-primary);
  font-size: 1.125rem;
  font-weight: 800;
}

.weights-panel {
  background: var(--dd-white);
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius);
}

.recommendation-card {
  background: var(--dd-white);
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius);
  box-shadow: var(--dd-shadow-sm);
}

.score-label {
  background: var(--dd-primary);
  color: var(--dd-white);
  border-radius: var(--dd-radius-pill);
  padding: 5px 11px;
  font-size: 0.8125rem;
  font-weight: 700;
  white-space: nowrap;
}

.match-progress {
  background: var(--dd-primary);
}

.score-details {
  padding: 0.75rem 1rem;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius-sm);
  background: var(--dd-surface);
}

.score-details summary {
  color: var(--dd-primary);
  cursor: pointer;
}
</style>
