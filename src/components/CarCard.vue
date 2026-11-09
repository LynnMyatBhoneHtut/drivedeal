<script setup>
// One car in a grid (Home, Car listing, Liked cars). OWNER: Lai.
// Used by Yati's Liked cars page too, so keep the "car" prop as agreed.
// Usage: <CarCard :car="car" />
import { imageUrl } from '../services/api'

defineProps({
  // One car object as returned by listings.php
  // (make / model / price / image_path are guesses - match them to the real column names)
  car: { type: Object, required: true }
})
</script>

<template>
  <!-- Placeholder - Lai builds the real card -->
  <div class="card h-100">
    <img
      v-if="car.image_path"
      :src="imageUrl(car.image_path)"
      class="card-img-top"
      :alt="`${car.make} ${car.model}`"
    />
    <div class="card-body">
      <h5 class="card-title">{{ car.make }} {{ car.model }}</h5>
      <p class="card-text">RM {{ car.price }}</p>
      <router-link
        :to="{ name: 'car-detail', params: { id: car.listing_id } }"
        class="stretched-link"
      >
        View details
      </router-link>
    </div>
  </div>
</template>
