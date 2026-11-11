
import test from 'node:test'
import assert from 'node:assert/strict'
import { ref } from 'vue'
import { useCarRecommendations } from '../src/composables/useCarRecommendations.js'

const year = new Date().getFullYear()

const cars = [
  {
    listing_id: 1,
    make: 'Toyota',
    model: 'Corolla Cross',
    year: year - 4,
    price: 105000,
    mileage_km: 35000
  },
  {
    listing_id: 2,
    make: 'Honda',
    model: 'Civic',
    year: year - 5,
    price: 89000,
    mileage_km: 48000
  },
  {
    listing_id: 3,
    make: 'Perodua',
    model: 'Myvi',
    year: year - 3,
    price: 48000,
    mileage_km: 18000
  }
]

function defaultPreferences() {
  return {
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

test('Ranks cars using weighted suitability', () => {
  const { recommendations } = useCarRecommendations(
    ref(cars),
    ref(defaultPreferences())
  )

  assert.deepEqual(
    recommendations.value.map(car => car.listing_id),
    [3, 2, 1]
  )

  assert.deepEqual(
    recommendations.value.map(car => car.matchScore),
    [84, 64, 54]
  )
})

test('Updates when preferences change', () => {
  const preferences = ref(defaultPreferences())

  const { recommendations } = useCarRecommendations(
    ref(cars),
    preferences
  )

  const originalScore = recommendations.value.find(
    car => car.listing_id === 2
  ).matchScore

  preferences.value.budget = 120000

  const updatedScore = recommendations.value.find(
    car => car.listing_id === 2
  ).matchScore

  assert.ok(updatedScore > originalScore)

  preferences.value.preferredMake = 'Honda'

  const honda = recommendations.value.find(
    car => car.listing_id === 2
  )

  assert.equal(honda.scoreBreakdown.make, 100)
})

test('Excludes cars with missing prices', () => {
  const invalidCar = {
    ...cars[0],
    listing_id: 99,
    price: null
  }

  const { recommendations } = useCarRecommendations(
    ref([...cars, invalidCar]),
    ref(defaultPreferences())
  )


test('Handles all zero weights safely', () => {
  const preferences = defaultPreferences()

  preferences.weights = {
    price: 0,
    mileage: 0,
    age: 0,
    make: 0
  }

  const { recommendations } = useCarRecommendations(
    ref(cars),
    ref(preferences)
  )

  assert.equal(recommendations.value.length, 3)

  recommendations.value.forEach(car => {
    assert.equal(car.matchScore, 0)
    assert.ok(Number.isFinite(car.matchScore))
  })
})

test('Prioritises the preferred brand when its weight is highest', () => {
  const preferences = defaultPreferences()

  preferences.preferredMake = 'Honda'

  preferences.weights = {
    price: 0,
    mileage: 0,
    age: 0,
    make: 100
  }

  const { recommendations } = useCarRecommendations(
    ref(cars),
    ref(preferences)
  )

  assert.equal(recommendations.value[0].make, 'Honda')
  assert.equal(recommendations.value[0].matchScore, 100)

  recommendations.value.slice(1).forEach(car => {
    assert.equal(car.matchScore, 0)
  })
})

  assert.equal(recommendations.value.length, 3)
})
