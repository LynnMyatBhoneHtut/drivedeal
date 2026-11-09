<script setup>
// Add / edit a car - shared by Sell a car and Edit listing. OWNER: Lynn.
// Usage: <CarForm @submit="save" />                       (Sell: empty form)
//        <CarForm :initial-car="car" @submit="save" />   (Edit: pre-filled)
defineProps({
  initialCar: { type: Object, default: null }
})
defineEmits(['submit'])
</script>

<template>
  <!-- Placeholder - Lynn builds the real form with validation -->
  <div class="alert alert-secondary">
    CarForm placeholder ({{ initialCar ? 'edit mode' : 'new car' }})
  </div>
</template>
