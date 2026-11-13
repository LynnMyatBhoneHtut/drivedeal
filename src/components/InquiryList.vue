<script setup>
// Buyer messages for the seller's cars, on My listings. OWNER: Lynn.
// Usage: <InquiryList :inquiries="inquiries" />  (loaded with inquiries.php GET)
// Each item: { inquiry_id, listing_id, buyer_id, message, created_at,
//              make, model, buyer_name, buyer_email, buyer_phone }
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'

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

function initials(name) {
  const parts = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (!parts.length) return '?'
  return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase()
}

function formatDate(value) {
  const date = toDate(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
  <div v-if="!sorted.length" class="dd-empty">
    <span class="dd-icon-circle"><AppIcon name="message" :size="28" /></span>
    <h3>No enquiries yet</h3>
    <p class="mb-0">When buyers message you about your cars, they'll show up here.</p>
  </div>

  <ul v-else class="inquiry-list">
    <li v-for="inquiry in sorted" :key="inquiry.inquiry_id" class="inquiry">
      <article class="inquiry-card dd-card">
        <header class="inquiry-head">
          <span class="dd-avatar" aria-hidden="true">{{ initials(inquiry.buyer_name) }}</span>
          <div class="min-w-0 flex-grow-1">
            <p class="fw-bold mb-0 text-truncate">{{ inquiry.buyer_name }}</p>
            <p class="small text-muted mb-0">
              About
              <router-link
                :to="{ name: 'car-detail', params: { id: inquiry.listing_id } }"
                class="fw-semibold text-decoration-none"
              >
                {{ inquiry.make }} {{ inquiry.model }}
              </router-link>
            </p>
          </div>
          <time class="small text-muted text-nowrap" :datetime="inquiry.created_at">
            {{ formatDate(inquiry.created_at) }}
          </time>
        </header>

        <p class="message">{{ inquiry.message }}</p>

        <footer class="inquiry-contact">
          <a :href="`mailto:${inquiry.buyer_email}`" class="contact-link text-break">
            <AppIcon name="mail" :size="16" /> {{ inquiry.buyer_email }}
          </a>
          <a
            v-if="inquiry.buyer_phone"
            :href="`tel:${inquiry.buyer_phone.replace(/\s+/g, '')}`"
            class="contact-link"
          >
            <AppIcon name="phone" :size="16" /> {{ inquiry.buyer_phone }}
          </a>
        </footer>
      </article>
    </li>
  </ul>
</template>

<style scoped>
.inquiry-list {
  position: relative;
  display: grid;
  gap: 1rem;
  margin: 0;
  padding: 0 0 0 1.5rem;
  list-style: none;
}

/* Subtle vertical timeline on the left */
.inquiry-list::before {
  content: '';
  position: absolute;
  top: 0.5rem;
  bottom: 0.5rem;
  left: 5px;
  width: 1px;
  background: var(--dd-primary-100);
}

.inquiry {
  position: relative;
}

.inquiry::before {
  content: '';
  position: absolute;
  top: 1.6rem;
  left: calc(-1.5rem + 1px);
  width: 9px;
  height: 9px;
  border: 2px solid var(--dd-primary);
  border-radius: 50%;
  background: var(--dd-white);
}

.inquiry-card {
  padding: 1.1rem 1.25rem;
}

.inquiry-head {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.message {
  margin: 0 0 0.75rem;
  white-space: pre-line;
  overflow-wrap: anywhere;
  color: var(--dd-body);
}

.inquiry-contact {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1.25rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--dd-line);
}

.contact-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: 32px;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
}

.contact-link:hover {
  text-decoration: underline;
}

.min-w-0 {
  min-width: 0;
}

@media (max-width: 575.98px) {
  .inquiry-head {
    flex-wrap: wrap;
  }

  .inquiry-head time {
    width: 100%;
    padding-left: calc(40px + 0.75rem);
  }
}
</style>
