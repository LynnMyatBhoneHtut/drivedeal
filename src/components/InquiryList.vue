<script setup>
// Buyer messages for the seller's cars, on My listings. OWNER: Lynn.
// Usage: <InquiryList :inquiries="inquiries" />  (loaded with inquiries.php GET)
// Each item: { inquiry_id, listing_id, buyer_id, message, created_at,
//              make, model, buyer_name, buyer_email, buyer_phone }
import { computed } from 'vue'

const props = defineProps({
  inquiries: { type: Array, default: () => [] }
})

/** MySQL "2026-11-10 14:00:00" -> a Date (local time). */
function toDate(value) {
  return new Date(String(value || '').replace(' ', 'T'))
}

// Newest first, even if the array arrives in another order
const sorted = computed(() =>
  [...props.inquiries].sort(
    (a, b) => toDate(b.created_at) - toDate(a.created_at) || b.inquiry_id - a.inquiry_id
  )
)

function formatDate(value) {
  const date = toDate(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
  <p v-if="!sorted.length" class="text-muted mb-0">No enquiries yet.</p>

  <ul v-else class="list-group">
    <li v-for="inquiry in sorted" :key="inquiry.inquiry_id" class="list-group-item py-3">
      <div class="d-flex flex-wrap justify-content-between gap-1 mb-1">
        <router-link
          :to="{ name: 'car-detail', params: { id: inquiry.listing_id } }"
          class="fw-semibold text-decoration-none"
        >
          {{ inquiry.make }} {{ inquiry.model }}
        </router-link>
        <small class="text-muted">
          <time :datetime="inquiry.created_at">{{ formatDate(inquiry.created_at) }}</time>
        </small>
      </div>

      <div class="small mb-2">
        <span class="fw-semibold">{{ inquiry.buyer_name }}</span>
        <span class="text-muted"> &middot; </span>
        <a :href="`mailto:${inquiry.buyer_email}`" class="text-break">{{ inquiry.buyer_email }}</a>
        <template v-if="inquiry.buyer_phone">
          <span class="text-muted"> &middot; </span>
          <a :href="`tel:${inquiry.buyer_phone.replace(/\s+/g, '')}`">{{ inquiry.buyer_phone }}</a>
        </template>
      </div>

      <p class="mb-0 message">{{ inquiry.message }}</p>
    </li>
  </ul>
</template>

<style scoped>
.message {
  white-space: pre-line;
  overflow-wrap: anywhere;
}
</style>
