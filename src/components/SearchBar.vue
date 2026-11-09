<script setup>
// Filters for the Car listing page (make, price range, sort). OWNER: Lai.
// Suggested usage: <SearchBar v-model="filters" />
// where filters = { make: '', minPrice: '', maxPrice: '', sort: 'price_asc' }
const filters = defineModel({ type: Object, default: () => ({}) })
</script>

<template>
  <!-- Placeholder - Lai builds the real search bar -->
  <div class="alert alert-secondary">SearchBar placeholder {{ filters }}</div>
</template>
