<script setup>
// Car card used on Home, Browse and Liked cars. OWNER: Lai (look by the blue redesign).
// Usage: <CarCard :car="car" />
// Optional: :liked="true" when the page knows the user liked this car (Liked cars page),
//           :show-like="false" to hide the heart, @like-change="..." gets { likes, liked }.
// The whole card links to the detail page; the heart button sits above the link.
import { computed, ref } from 'vue'
import { imageUrl } from '../services/api'
import AppIcon from './AppIcon.vue'
import LikeButton from './LikeButton.vue'

const props = defineProps({
  car: {
    type: Object,
    required: true
  },
  liked: { type: Boolean, default: false },
  showLike: { type: Boolean, default: true }
})

const emit = defineEmits(['like-change'])

const imageError = ref(false)

const carName = computed(() => `${props.car.make} ${props.car.model}`)

const title = computed(() =>
  props.car.year ? `${props.car.year} ${carName.value}` : carName.value
)

const subLine = computed(() => [props.car.body_type, props.car.colour].filter(Boolean).join(' · '))

const formattedPrice = computed(() => Number(props.car.price).toLocaleString('en-MY'))

const formattedMileage = computed(() =>
  props.car.mileage_km != null ? Number(props.car.mileage_km).toLocaleString('en-MY') : null
)

const statusLabel = computed(() => {
  if (props.car.status === 'reserved') return 'Reserved'
  if (props.car.status === 'sold') return 'Sold'
  return ''
})
</script>

<template>
  <article class="car-card dd-card dd-card-hover h-100">
    <div class="car-image">
      <img
        v-if="car.image_path && !imageError"
        :src="imageUrl(car.image_path)"
        :alt="`${title} photo`"
        loading="lazy"
        @error="imageError = true"
      />

      <div v-else class="image-placeholder">
        <AppIcon name="car" :size="40" />
        <span>Photo coming soon</span>
      </div>

      <span
        v-if="statusLabel"
        class="dd-status car-status"
        :class="car.status === 'sold' ? 'dd-status-sold' : 'dd-status-reserved'"
      >
        {{ statusLabel }}
      </span>

      <div v-if="showLike" class="car-like">
        <LikeButton
          :listing-id="car.listing_id"
          :likes="Number(car.likes || 0)"
          :liked="liked"
          compact
          @change="emit('like-change', $event)"
        />
      </div>
    </div>

    <div class="car-body">
      <h3 class="car-title">
        <router-link
          :to="{ name: 'car-detail', params: { id: car.listing_id } }"
          class="stretched-link"
        >
          {{ title }}
        </router-link>
      </h3>
      <p v-if="subLine" class="car-sub">{{ subLine }}</p>

      <p class="car-price">RM {{ formattedPrice }}</p>

      <ul class="car-specs">
        <li v-if="formattedMileage">
          <AppIcon name="gauge" :size="16" />
          <span>{{ formattedMileage }} km</span>
        </li>
        <li v-if="car.transmission">
          <AppIcon name="gear" :size="16" />
          <span>{{ car.transmission }}</span>
        </li>
        <li v-if="car.fuel_type">
          <AppIcon name="fuel" :size="16" />
          <span>{{ car.fuel_type }}</span>
        </li>
      </ul>

      <p v-if="car.location" class="car-location">
        <AppIcon name="pin" :size="16" />
        <span class="text-truncate">{{ car.location }}</span>
      </p>
    </div>
  </article>
</template>

<style scoped>
.car-card {
  position: relative;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.car-image {
  position: relative;
  aspect-ratio: 4 / 3;
  background: var(--dd-surface);
}

.car-image img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 100%;
  color: var(--dd-muted);
  font-size: 0.875rem;
}

.car-status {
  position: absolute;
  top: 12px;
  left: 12px;
  box-shadow: var(--dd-shadow-sm);
}

.car-like {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 2;
}

.car-body {
  display: flex;
  flex: 1;
  flex-direction: column;
  padding: 1rem 1.1rem 1.1rem;
}

.car-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.car-title a {
  color: var(--dd-ink);
  text-decoration: none;
}

.car-title a:hover {
  color: var(--dd-primary);
}

/* The focus ring goes round the whole card, not just the title */
.car-title a:focus-visible {
  outline: none;
  box-shadow: none;
}

.car-title a:focus-visible::after {
  border-radius: var(--dd-radius);
  outline: 3px solid var(--dd-primary-100);
  box-shadow: inset 0 0 0 2px var(--dd-primary);
}

.car-sub {
  margin: 0.15rem 0 0;
  font-size: 0.85rem;
  color: var(--dd-muted);
}

.car-price {
  margin: 0.6rem 0 0.75rem;
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--dd-primary);
  line-height: 1.2;
}

.car-specs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem 0.9rem;
  margin: 0 0 0.75rem;
  padding: 0 0 0.75rem;
  list-style: none;
  border-bottom: 1px solid var(--dd-line);
  font-size: 0.82rem;
  color: var(--dd-body);
}

.car-specs li {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.car-specs :deep(svg) {
  color: var(--dd-muted);
}

.car-location {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  min-width: 0;
  margin: auto 0 0;
  font-size: 0.82rem;
  color: var(--dd-muted);
}
</style>
