import test from 'node:test'
import assert from 'node:assert/strict'
import { ref } from 'vue'
import { useCarRecommendations } from '../src/composables/useCarRecommendations.js'

// COS30043 DriveDeal: individual Smart Car Match algorithm tests.
// Run from the project root: node --test tests/carRecommendations.test.js
const currentYear = new Date().getFullYear()

const sampleCars = [
  {
    listing_id: 1,
    make: 'Toyota',
    model: 'Corolla Cross',
    year: currentYear - 4,
    price: 105000,
    mileage_km: 35000
  },
  {
    listing_id: 2,
    make: 'Honda',
    model: 'Civic',
    year: currentYear - 5,
    price: 89000,
    mileage_km: 48000
  },
  {
    listing_id: 3,
    make: 'Perodua',
    model: 'Myvi',
    year: currentYear - 3,
    price: 48000,
    mileage_km: 18000
  }
]

function defaultPreferences() {
  return {
    budget: 80000,
    maxMileage: 100000,
    preferredMake: '',
    weights: { price: 40, mileage: 30, age: 20, make: 10 }
  }
}

function getRecommendations(cars = sampleCars, settings = defaultPreferences()) {
  return useCarRecommendations(ref(cars), ref(settings)).recommendations.value
}

test('Ranks cars by weighted suitability with the expected scores', () => {
  const results = getRecommendations()

  assert.deepEqual(results.map(car => car.listing_id), [3, 2, 1])
  assert.deepEqual(results.map(car => car.matchScore), [84, 64, 54])
})

test('Recalculates scores reactively when buyer preferences change', () => {
  const preferences = ref(defaultPreferences())
  const { recommendations } = useCarRecommendations(ref(sampleCars), preferences)

  const before = recommendations.value.find(car => car.listing_id === 2).matchScore
  preferences.value.budget = 120000
  const after = recommendations.value.find(car => car.listing_id === 2).matchScore

  assert.ok(after > before)

  preferences.value.preferredMake = 'Honda'
  const honda = recommendations.value.find(car => car.listing_id === 2)
  assert.equal(honda.scoreBreakdown.make, 100)
})

test('Excludes missing or empty car prices', () => {
  const invalid = [
    { ...sampleCars[0], listing_id: 90, price: null },
    { ...sampleCars[0], listing_id: 91, price: '' }
  ]

  const results = getRecommendations([...sampleCars, ...invalid])
  assert.equal(results.length, 3)
  assert.ok(results.every(car => car.listing_id !== 90 && car.listing_id !== 91))
})

test('Excludes invalid mileage, prices and model years', () => {
  const invalid = [
    { ...sampleCars[0], listing_id: 90, mileage_km: -1 },
    { ...sampleCars[0], listing_id: 91, price: -500 },
    { ...sampleCars[0], listing_id: 92, year: currentYear + 2 },
    { ...sampleCars[0], listing_id: 93, year: null }
  ]

  const results = getRecommendations([...sampleCars, ...invalid])
  assert.equal(results.length, sampleCars.length)
})

test('Handles zero weights safely without NaN or Infinity', () => {
  const settings = defaultPreferences()
  settings.weights = { price: 0, mileage: 0, age: 0, make: 0 }

  const results = getRecommendations(sampleCars, settings)
  assert.equal(results.length, 3)
  results.forEach(car => {
    assert.equal(car.matchScore, 0)
    assert.ok(Number.isFinite(car.matchScore))
  })
})

test('Prioritises the selected brand with 100% brand importance', () => {
  const settings = defaultPreferences()
  settings.preferredMake = 'Honda'
  settings.weights = { price: 0, mileage: 0, age: 0, make: 100 }

  const results = getRecommendations(sampleCars, settings)
  assert.equal(results[0].make, 'Honda')
  assert.equal(results[0].matchScore, 100)
  results.slice(1).forEach(car => assert.equal(car.matchScore, 0))
})

test('Ignores brand importance while Any Brand is selected', () => {
  const settings = defaultPreferences()
  settings.preferredMake = ''
  settings.weights = { price: 100, mileage: 0, age: 0, make: 100 }

  const results = getRecommendations(sampleCars, settings)
  results.forEach(car => {
    assert.equal(car.matchScore, Math.round(car.scoreBreakdown.price))
  })
})

test('Keeps every suitability score within the range 0 to 100', () => {
  const extremeCar = {
    ...sampleCars[0],
    listing_id: 99,
    price: 10000000,
    mileage_km: 1000000
  }

  const results = getRecommendations([...sampleCars, extremeCar])
  results.forEach(car => {
    assert.ok(car.matchScore >= 0 && car.matchScore <= 100)
    Object.values(car.scoreBreakdown).forEach(score => {
      assert.ok(Number.isFinite(score))
      assert.ok(score >= 0 && score <= 100)
    })
  })
})

test('Updates recommendations when the database car list changes', () => {
  const cars = ref([...sampleCars])
  const { recommendations } = useCarRecommendations(cars, ref(defaultPreferences()))

  assert.equal(recommendations.value.length, 3)
  cars.value = cars.value.filter(car => car.listing_id !== 1)
  assert.equal(recommendations.value.length, 2)
  assert.ok(recommendations.value.every(car => car.listing_id !== 1))
})

test('Provides understandable reasons for a recommended car', () => {
  const myvi = getRecommendations().find(car => car.listing_id === 3)

  assert.ok(myvi.matchReasons.includes('Within your budget'))
  assert.ok(myvi.matchReasons.includes('Meets your mileage preference'))
  assert.ok(myvi.matchReasons.includes('Relatively recent model'))
})

test('Returns an empty recommendation list for no available cars', () => {
  assert.deepEqual(getRecommendations([]), [])
})
