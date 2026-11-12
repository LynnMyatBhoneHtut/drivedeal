
import { computed, unref } from 'vue'

// Keep a score between 0 and 100
function clamp(score) {
  return Math.max(0, Math.min(100, score))
}

// Convert user input to a valid positive number
function positiveNumber(value, fallback) {
  const number = Number(value)
  return Number.isFinite(number) && number > 0
    ? number
    : fallback
}

// Reusable recommendation composable
export function useCarRecommendations(cars, preferences) {
  const recommendations = computed(() => {
    const carList = unref(cars)
    const settings = unref(preferences) || {}

    if (!Array.isArray(carList)) {
      return []
    }

    // User preferences
    const budget = positiveNumber(settings.budget, 80000)
    const maxMileage = positiveNumber(settings.maxMileage, 100000)

    const preferredMake = String(
      settings.preferredMake || ''
    ).trim().toLowerCase()

    // Default importance weights
    const customWeights = settings.weights || {}

    const weights = {
      price: Math.max(0, Number(customWeights.price ?? 40)),
      mileage: Math.max(0, Number(customWeights.mileage ?? 30)),
      age: Math.max(0, Number(customWeights.age ?? 20)),
      make: preferredMake
        ? Math.max(0, Number(customWeights.make ?? 10))
        : 0
    }

    const totalWeight = Object.values(weights).reduce(
      (total, weight) => total + weight,
      0
    )

    const currentYear = new Date().getFullYear()

    // Calculate a compatibility score for each car
    return carList
      .map(car => {

        // Reject incomplete records before numeric conversion
        if (
          !car ||
          !String(car.make ?? '').trim() ||
          !String(car.model ?? '').trim() ||
          [car.price, car.mileage_km, car.year].some(
            value =>
              value === null ||
              value === undefined ||
              (typeof value === 'string' && value.trim() === '')
          )
        ) {
          return null
        }

        const price = Number(car.price)
        const mileage = Number(car.mileage_km)
        const year = Number(car.year)

        // Exclude cars with invalid or incomplete data
        if (
          !Number.isFinite(price) || price < 0 ||
          !Number.isFinite(mileage) || mileage < 0 ||
          !Number.isInteger(year) ||
          year < 1900 || year > currentYear + 1
        ) {
          return null
        }

        const carAge = Math.max(0, currentYear - year)
        
        // Price suitability:
        // Lower prices receive higher affordability scores.
        // Cars above budget receive an additional penalty.
        const priceScore = price <= budget
          ? clamp(100 - (price / budget) * 35)
          : clamp(
              65 - ((price - budget) / budget) * 150
            )

        // Mileage suitability
        const mileageScore = mileage <= maxMileage
          ? clamp(100 - (mileage / maxMileage) * 30)
          : clamp(
              70 - ((mileage - maxMileage) / maxMileage) * 140
            )

        // Vehicle age suitability
        const ageScore = clamp(100 - carAge * 7)

        // Brand preference
        const makeScore = preferredMake &&
          String(car.make).trim().toLowerCase() === preferredMake
          ? 100
          : 0

        // Individual scores for visual comparison
        const scoreBreakdown = {
          price: priceScore,
          mileage: mileageScore,
          age: ageScore,
          make: makeScore
        }

        // Weighted average of all criteria
        const weightedTotal =
          priceScore * weights.price +
          mileageScore * weights.mileage +
          ageScore * weights.age +
          makeScore * weights.make

        const matchScore = totalWeight > 0
          ? Math.round(weightedTotal / totalWeight)
          : 0

        // Explain the recommendation
        const matchReasons = []

        if (price <= budget) {
          matchReasons.push('Within your budget')
        } else {
          matchReasons.push('Above your preferred budget')
        }

        if (mileage <= maxMileage) {
          matchReasons.push('Meets your mileage preference')
        }

        if (carAge <= 5) {
          matchReasons.push('Relatively recent model')
        }

        if (preferredMake && makeScore === 100) {
          matchReasons.push('Matches your preferred brand')
        }

        return {
          ...car,
          matchScore,
          scoreBreakdown,
          matchReasons
        }
      })
      .filter(car => car !== null)
      .sort((a, b) => b.matchScore - a.matchScore)
  })

  return { recommendations }
}
