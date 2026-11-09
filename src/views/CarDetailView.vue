<script setup>
// Car detail page - /cars/:id
// OWNER: Lai
// TODO: ImageGallery, car info + seller_name, LikeButton (Yati), "Contact seller" form that posts to inquiries.php (Lynn)
defineProps({
  id: { type: String, required: true } // from the URL, e.g. /cars/3 -> '3'
})
</script>

<template>
  <section class="container py-4">
    <h1>Car detail page</h1>
    <p class="text-muted">Placeholder - Lai builds this page.</p>
    <p>Car id from the URL: {{ id }}</p>
  </section>
</template>
