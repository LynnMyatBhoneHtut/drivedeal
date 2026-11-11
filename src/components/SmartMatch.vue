<script setup>
import { computed, ref, toRef, watch } from 'vue'
import { useCarRecommendations } from '../composables/useCarRecommendations'
import CarComparisonChart from './CarComparisonChart.vue'

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

</script>

<template>
  <section class="smart-match p-4 mb-4">
    <div
class="d-flex flex-wrap justify-content-between
                align-items-center gap-3 mb-4">
      <div>
        <p class="section-label mb-2">PERSONALISED SEARCH</p>
        <h2 class="h3 fw-bold mb-2">Smart Car Match</h2>
        <p class="text-muted mb-0">
          Tell us what matters to you and discover
          cars that match your preferences.
        </p>
      </div>

      <button type="button" class="btn btn-outline-dark" @click="resetPreferences">
        Reset Preferences
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
            class="form-range" min="0" max="100" step="5" />
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
      <h3 class="h4 fw-bold mb-2">
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
              {{ car.year }} ·
              {{ formatNumber(car.mileage_km) }} km
            </p>

            <ul class="small text-muted ps-3 mb-3">
              <li v-for="reason in car.matchReasons" :key="reason">
                {{ reason }}
              </li>
            </ul>

            <router-link
:to="{
              name: 'car-detail',
              params: { id: car.listing_id }
            }" class="btn btn-outline-dark w-100">
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

      <p class="text-muted small">
        Select two or three cars to compare their suitability scores.
      </p>

      <div class="d-flex flex-wrap gap-4">
        <div v-for="car in recommendations" :key="car.listing_id" class="form-check">
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
  background: #faf8f5;
  border: 1px solid #e9e5e1;
  border-radius: 16px;
}

.section-label {
  color: #c2410c;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.match-value {
  color: #c2410c;
  font-size: 18px;
}

.weights-panel {
  background: white;
  border: 1px solid #e9e5e1;
  border-radius: 12px;
}

.recommendation-card {
  background: white;
  border: 1px solid #e9e5e1;
  border-radius: 12px;
}

.score-label {
  background: #fff0e6;
  color: #9a3412;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}

.match-progress {
  background: #c2410c;
}

/* Copper slider controls */
.form-range::-webkit-slider-thumb {
  background-color: #c2410c;
}

.form-range::-moz-range-thumb {
  background-color: #c2410c;
  border-color: #c2410c;
}

.form-range:focus::-webkit-slider-thumb {
  box-shadow: 0 0 0 0.25rem rgba(194, 65, 12, 0.2);
}

.form-range:focus-visible,
.form-select:focus-visible {
  outline: 2px solid #c2410c;
  outline-offset: 2px;
}

/* Copper comparison checkboxes */
.form-check-input:checked {
  background-color: #c2410c;
  border-color: #c2410c;
}

.form-check-input:focus {
  border-color: #c2410c;
  box-shadow: 0 0 0 0.25rem rgba(194, 65, 12, 0.2);
}
</style>
