<script setup>
// Photos on the Car detail page. OWNER: Lai.
// Suggested usage: <ImageGallery :images="images" />
// where images = [{ image_id, image_path }, ...] from images.php
import { imageUrl } from '../services/api'

defineProps({
  images: { type: Array, default: () => [] }
})
</script>

<template>
  <!-- Placeholder - Lai builds the real gallery -->
  <div class="d-flex gap-2 flex-wrap">
    <img
      v-for="img in images"
      :key="img.image_id"
      :src="imageUrl(img.image_path)"
      alt="Car photo"
      style="width: 120px"
    />
    <p v-if="!images.length" class="text-muted">ImageGallery placeholder (no images)</p>
  </div>
</template>
