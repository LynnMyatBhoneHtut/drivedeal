<script setup>
import { computed } from 'vue'

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

const pageCount = computed(() =>
  Math.ceil(props.total / props.perPage)
)

const pageNumbers = computed(() =>
  Array.from(
    { length: pageCount.value },
    (_, index) => index + 1
  )
)

function changePage(newPage) {
  if (newPage >= 1 && newPage <= pageCount.value) {
    page.value = newPage
  }
}
</script>

<template>
  <nav v-if="pageCount > 1" aria-label="Car listing pagination" class="mt-4">
    <ul class="pagination justify-content-center">
      <li class="page-item" :class="{ disabled: page === 1 }">
        <button type="button" class="page-link" :disabled="page === 1" @click="changePage(page - 1)">
          Previous
        </button>
      </li>

      <li v-for="number in pageNumbers" :key="number" class="page-item" :class="{ active: page === number }">
        <button
type="button" class="page-link" :aria-label="`Go to page ${number}`"
          :aria-current="page === number ? 'page' : undefined" @click="changePage(number)">
          {{ number }}
        </button>
      </li>

      <li class="page-item" :class="{ disabled: page === pageCount }">
        <button type="button" class="page-link" :disabled="page === pageCount" @click="changePage(page + 1)">
          Next
        </button>
      </li>
    </ul>
  </nav>
</template>
