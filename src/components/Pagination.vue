<script setup>
// Page buttons under the car grid. OWNER: Lai.
// Usage: <Pagination v-model:page="page" :total="filteredCars.length" :per-page="6" />
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'

const page = defineModel('page', {
  type: Number,
  default: 1
})

const props = defineProps({
  total: {
    type: Number,
    required: true
  },
  perPage: {
    type: Number,
    default: 6
  }
})

const pageCount = computed(() => Math.ceil(props.total / props.perPage))

const pageNumbers = computed(() => Array.from({ length: pageCount.value }, (_, index) => index + 1))

function changePage(newPage) {
  if (newPage >= 1 && newPage <= pageCount.value) {
    page.value = newPage
  }
}
</script>

<template>
  <nav v-if="pageCount > 1" aria-label="Car listing pagination" class="mt-4 mt-lg-5">
    <ul class="dd-pagination">
      <li>
        <button
          type="button"
          class="page-btn"
          :disabled="page === 1"
          aria-label="Previous page"
          @click="changePage(page - 1)"
        >
          <AppIcon name="chevron-left" :size="18" />
        </button>
      </li>

      <li v-for="number in pageNumbers" :key="number">
        <button
          type="button"
          class="page-btn"
          :class="{ active: page === number }"
          :aria-label="`Go to page ${number}`"
          :aria-current="page === number ? 'page' : undefined"
          @click="changePage(number)"
        >
          {{ number }}
        </button>
      </li>

      <li>
        <button
          type="button"
          class="page-btn"
          :disabled="page === pageCount"
          aria-label="Next page"
          @click="changePage(page + 1)"
        >
          <AppIcon name="chevron-right" :size="18" />
        </button>
      </li>
    </ul>
  </nav>
</template>

<style scoped>
.dd-pagination {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.4rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.page-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  height: 44px;
  padding: 0 0.75rem;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius-sm);
  background: var(--dd-white);
  color: var(--dd-ink);
  font-weight: 600;
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease,
    color 0.15s ease;
}

.page-btn:hover:not(:disabled):not(.active) {
  border-color: var(--dd-primary);
  color: var(--dd-primary);
  background: var(--dd-primary-50);
}

.page-btn.active {
  border-color: var(--dd-primary);
  background: var(--dd-primary);
  color: var(--dd-white);
}

.page-btn:disabled {
  color: var(--dd-muted);
  opacity: 0.5;
}
</style>
