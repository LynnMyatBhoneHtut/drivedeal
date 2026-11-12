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
        <span class="car-icon" aria-hidden="true">🚗</span>
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
      }" class="btn btn-dark mt-auto" :aria-label="`View details for ${carName}`">
        View Details
      </router-link>
    </div>
  </article>
</template>

<style scoped>
.car-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.car-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.car-image {
  position: relative;
  height: 200px;
  background: #f1f3f5;
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
  color: #6c757d;
}

.car-icon {
  font-size: 42px;
}

.year-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  background: white;
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
}

.car-price {
  font-size: 22px;
  font-weight: 700;
  color: #198754;
}

.car-info {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  color: #6c757d;
  font-size: 14px;
}

.car-info span {
  background: #f8f9fa;
  padding: 5px 10px;
  border-radius: 6px;
}
</style>