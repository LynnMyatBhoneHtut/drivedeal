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

const availableImages = computed(() => props.images.filter((image) => image && image.image_path))

const currentImage = computed(() => availableImages.value[selectedIndex.value] || null)

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
    selectedIndex.value = (selectedIndex.value - 1 + count) % count
  }
}

function nextImage() {
  const count = availableImages.value.length

  if (count > 0) {
    selectedIndex.value = (selectedIndex.value + 1) % count
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
        v-if="currentImage && !failedImages[currentImage.image_path]"
        :src="imageUrl(currentImage.image_path)"
        :alt="`${carName} - photo ${selectedIndex + 1}`"
        @error="handleImageError(currentImage.image_path)"
      />

      <div v-else class="image-placeholder">
        <p class="mb-2 fw-semibold">No photo available</p>
        <p class="mb-0 small">Vehicle images will appear here when uploaded.</p>
      </div>

      <!-- Navigation arrows -->
      <template v-if="availableImages.length > 1">
        <button
          type="button"
          class="gallery-arrow previous"
          aria-label="Previous photo"
          @click="previousImage"
        >
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
    <div
      v-if="availableImages.length > 1"
      class="thumbnail-list"
      role="group"
      aria-label="Choose vehicle photo"
    >
      <button
        v-for="(image, index) in availableImages"
        :key="image.image_id || image.image_path"
        type="button"
        class="thumbnail-button"
        :class="{ selected: selectedIndex === index }"
        :aria-label="`View photo ${index + 1} of ${carName}`"
        :aria-pressed="selectedIndex === index"
        @click="selectImage(index)"
      >
        <img
          v-if="!failedImages[image.image_path]"
          :src="imageUrl(image.image_path)"
          :alt="`Thumbnail ${index + 1}`"
          loading="lazy"
          @error="handleImageError(image.image_path)"
        />
        <span v-else>Photo unavailable</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.main-image {
  position: relative;
  aspect-ratio: 16 / 10;
  background: var(--dd-surface);
  border-radius: var(--dd-radius);
  overflow: hidden;
}

.main-image > img {
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
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 50%;
  background: var(--dd-white);
  color: var(--dd-ink);
  box-shadow: var(--dd-shadow);
  transform: translateY(-50%);
  cursor: pointer;
}

.gallery-arrow:hover {
  background: var(--dd-primary);
  color: var(--dd-white);
}

.previous {
  left: 14px;
}

.next {
  right: 14px;
}

.image-counter {
  position: absolute;
  bottom: 14px;
  right: 14px;
  padding: 5px 12px;
  border-radius: var(--dd-radius-pill);
  background: var(--dd-overlay);
  color: var(--dd-white);
  font-size: 0.8125rem;
  font-weight: 600;
}

.thumbnail-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.thumbnail-button {
  width: 96px;
  aspect-ratio: 4 / 3;
  padding: 0;
  border: 2px solid transparent;
  border-radius: var(--dd-radius-sm);
  overflow: hidden;
  background: var(--dd-surface);
  color: var(--dd-muted);
  font-size: 11px;
  opacity: 0.75;
  transition:
    opacity 0.15s ease,
    border-color 0.15s ease;
}

.thumbnail-button:hover,
.thumbnail-button.selected {
  opacity: 1;
}

.thumbnail-button.selected {
  border-color: var(--dd-primary);
}

.thumbnail-button img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@media (max-width: 575.98px) {
  .thumbnail-button {
    width: 72px;
  }
}
</style>
