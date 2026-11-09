<script setup>
// Buyer messages for the seller's cars, on My listings. OWNER: Lynn.
// Usage: <InquiryList :inquiries="inquiries" />  (loaded with inquiries.php GET)
defineProps({
  inquiries: { type: Array, default: () => [] }
})
</script>

<template>
  <!-- Placeholder - Lynn builds the real list -->
  <p class="text-muted">InquiryList placeholder ({{ inquiries.length }} inquiries)</p>
</template>
