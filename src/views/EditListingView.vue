<script setup>
// Edit listing page - /cars/:id/edit
// OWNER: Lynn
// TODO: Load car, check seller_id === getUser().user_id (owner only), CarForm pre-filled, listings.php PUT / DELETE
defineProps({
  id: { type: String, required: true } // from the URL, e.g. /cars/3 -> '3'
})
</script>

<template>
  <section class="container py-4">
    <h1>Edit listing page</h1>
    <p class="text-muted">Placeholder - Lynn builds this page.</p>
    <p>Car id from the URL: {{ id }}</p>
  </section>
</template>
