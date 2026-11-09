<script setup>
// Page numbers under a list. OWNER: Lai. Other pages may reuse it, so keep these props as agreed.
// Usage: <Pagination v-model:page="page" :total="total" :per-page="perPage" />
// (total and perPage come straight from listings.php: { cars, total, page, perPage })
import { computed } from 'vue'

const page = defineModel('page', { type: Number, default: 1 })
const props = defineProps({
  total: { type: Number, required: true },
  perPage: { type: Number, default: 6 }
})

const pageCount = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)))
</script>

<template>
  <!-- Placeholder - Lai builds the real Bootstrap pagination -->
  <p class="text-muted">Pagination placeholder: page {{ page }} of {{ pageCount }}</p>
</template>
