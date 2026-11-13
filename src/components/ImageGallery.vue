<script setup>
import { computed, ref, watch } from 'vue'
import { imageUrl } from '../services/api'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  images: {
    type: Array,
    default: () => []
  },
  carName: {
    type: String,
    default: 'Vehicle'
  }
})

const selectedIndex = ref(0)
const failedImages = ref({})

const availableImages = computed(() =>
  props.images.filter(image => image && image.image_path)
)

const currentImage = computed(() =>
  availableImages.value[selectedIndex.value] || null
)

// Reset the gallery when a different car is loaded
watch(
  () => props.images,
  () => {
    selectedIndex.value = 0
    failedImages.value = {}
  },
  { deep: true }
)

function selectImage(index) {
  selectedIndex.value = index
}

function previousImage() {
  const count = availableImages.value.length

  if (count > 0) {
    selectedIndex.value =
      (selectedIndex.value - 1 + count) % count
  }
}

function nextImage() {
  const count = availableImages.value.length

  if (count > 0) {
    selectedIndex.value =
      (selectedIndex.value + 1) % count
  }
}

function handleImageError(path) {
  failedImages.value = {
    ...failedImages.value,
    [path]: true
  }
}
</script>

<template>
  <div class="image-gallery">
    <!-- Main image -->
    <div class="main-image">
      <img
v-if="
        currentImage &&
        !failedImages[currentImage.image_path]
      " :src="imageUrl(currentImage.image_path)" :alt="`${carName} - photo ${selectedIndex + 1}`"
        @error="handleImageError(currentImage.image_path)" />

      <div v-else class="image-placeholder">
        <p class="mb-2 fw-semibold">
          No photo available
        </p>
        <p class="mb-0 small">
          Vehicle images will appear here when uploaded.
        </p>
      </div>

      <!-- Navigation arrows -->
      <template v-if="availableImages.length > 1">
        <button type="button" class="gallery-arrow previous" aria-label="Previous photo" @click="previousImage">
          <AppIcon name="chevron-left" :size="22" />
        </button>

        <button type="button" class="gallery-arrow next" aria-label="Next photo" @click="nextImage">
          <AppIcon name="chevron-right" :size="22" />
        </button>
      </template>

      <span v-if="availableImages.length" class="image-counter">
        {{ selectedIndex + 1 }} /
        {{ availableImages.length }}
      </span>
    </div>

    <!-- Thumbnail gallery -->
    <div v-if="availableImages.length > 1" class="thumbnail-list" role="group" aria-label="Choose vehicle photo">
      <button
v-for="(image, index) in availableImages" :key="image.image_id || image.image_path" type="button"
        class="thumbnail-button" :class="{ selected: selectedIndex === index }"
        :aria-label="`View photo ${index + 1} of ${carName}`" :aria-pressed="selectedIndex === index"
        @click="selectImage(index)">
        <img
v-if="!failedImages[image.image_path]" :src="imageUrl(image.image_path)" :alt="`Thumbnail ${index + 1}`"
          loading="lazy" @error="handleImageError(image.image_path)" />
        <span v-else>Photo unavailable</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.main-image {
  position: relative;
  height: 380px;
  background: var(--dd-surface);
  border-radius: var(--dd-radius);
  overflow: hidden;
}

.main-image>img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.image-placeholder {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--dd-muted);
  text-align: center;
  padding: 20px;
}

.gallery-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: var(--dd-overlay);
  color: var(--dd-white);
  border: none;
  border-radius: 50%;
  width: 42px;
  height: 42px;
  font-size: 18px;
  cursor: pointer;
}

.gallery-arrow:hover {
  background: var(--dd-primary);
}

.previous {
  left: 15px;
}

.next {
  right: 15px;
}

.image-counter {
  position: absolute;
  bottom: 15px;
  right: 15px;
  background: var(--dd-overlay);
  color: var(--dd-white);
  padding: 6px 12px;
  border-radius: var(--dd-radius-pill);
  font-size: 13px;
}

.thumbnail-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 15px;
}

.thumbnail-button {
  width: 90px;
  height: 70px;
  padding: 0;
  border: 2px solid transparent;
  border-radius: 8px;
  overflow: hidden;
  background: var(--dd-surface);
  color: var(--dd-muted);
  font-size: 11px;
}

.thumbnail-button.selected {
  border-color: var(--dd-primary);
}

.thumbnail-button img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@media (max-width: 767px) {
  .main-image {
    height: 250px;
  }
}
</style>
