<script setup>
import { computed, ref } from 'vue'
import { imageUrl } from '../services/api'

const props = defineProps({
  car: {
    type: Object,
    required: true
  }
})

const imageError = ref(false)

const carName = computed(() =>
  `${props.car.make} ${props.car.model}`
)

const formattedPrice = computed(() =>
  Number(props.car.price).toLocaleString('en-MY')
)

const formattedMileage = computed(() =>
  props.car.mileage_km != null
    ? Number(props.car.mileage_km).toLocaleString('en-MY')
    : null
)
</script>

<template>
  <article class="card car-card h-100">
    <div class="car-image">
      <img
v-if="car.image_path && !imageError" :src="imageUrl(car.image_path)" :alt="carName"
        @error="imageError = true" />

      <div v-else class="image-placeholder">
        <span class="car-icon" aria-hidden="true">ðŸš—</span>
        <span>Photo coming soon</span>
      </div>

      <span v-if="car.year" class="year-badge">
        {{ car.year }}
      </span>
    </div>

    <div class="card-body d-flex flex-column">
      <h2 class="h5 card-title fw-bold">
        {{ carName }}
      </h2>

      <p class="car-price mb-3">
        RM {{ formattedPrice }}
      </p>

      <div class="car-info mb-3">
        <span v-if="formattedMileage">
          {{ formattedMileage }} km
        </span>
        <span v-if="car.transmission">
          {{ car.transmission }}
        </span>
        <span v-if="car.location">
          {{ car.location }}
        </span>
      </div>

      <router-link
:to="{
        name: 'car-detail',
        params: { id: car.listing_id }
      }" class="btn btn-outline-primary mt-auto" :aria-label="`View details for ${carName}`">
        View Details
      </router-link>
    </div>
  </article>
</template>

<style scoped>
.car-card {
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius);
  overflow: hidden;
  box-shadow: var(--dd-shadow-sm);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

@media (prefers-reduced-motion: no-preference) {
  .car-card:hover {
    transform: translateY(-3px);
  }
}

.car-card:hover {
  box-shadow: var(--dd-shadow-lg);
}

.car-image {
  position: relative;
  height: 200px;
  background: var(--dd-surface);
}

.car-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--dd-muted);
}

.car-icon {
  font-size: 42px;
}

.year-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  background: var(--dd-white);
  color: var(--dd-ink);
  padding: 4px 12px;
  border-radius: var(--dd-radius-pill);
  font-size: 0.8125rem;
  font-weight: 700;
}

.car-price {
  font-size: 1.375rem;
  font-weight: 800;
  color: var(--dd-primary);
}

.car-info {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  color: var(--dd-muted);
  font-size: 0.875rem;
}

.car-info span {
  background: var(--dd-surface);
  padding: 5px 10px;
  border-radius: var(--dd-radius-pill);
}
</style>
