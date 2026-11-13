<script setup>
// Browse filters (sidebar on desktop, collapsible panel on mobile). OWNER: Lai.
// Usage: <SearchBar v-model="filters" />  filters = { make, body_type, minPrice, maxPrice, sort }
// The sort select lives above the results in CarListView; Clear filters resets everything.
import AppIcon from './AppIcon.vue'

const filters = defineModel({
  type: Object,
  required: true
})

const BODY_TYPES = ['Sedan', 'Hatchback', 'SUV', 'MPV', 'Pickup']

function resetFilters() {
  filters.value = {
    make: '',
    body_type: '',
    minPrice: '',
    maxPrice: '',
    sort: 'price_asc'
  }
}
</script>

<template>
  <div class="search-bar dd-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="h3 mb-0 d-flex align-items-center gap-2">
        <AppIcon name="sliders" :size="20" />
        Filters
      </h2>
      <button type="button" class="btn btn-ghost btn-sm" @click="resetFilters">Clear all</button>
    </div>

    <div class="mb-3">
      <label for="carSearch" class="form-label">Make or model</label>
      <div class="input-icon">
        <AppIcon name="search" :size="18" />
        <input
          id="carSearch"
          v-model.trim="filters.make"
          type="search"
          class="form-control"
          placeholder="e.g. Toyota"
        />
      </div>
    </div>

    <fieldset class="mb-3">
      <legend class="form-label">Body type</legend>
      <div class="body-options">
        <label class="body-option">
          <input
            v-model="filters.body_type"
            type="radio"
            name="bodyType"
            value=""
            class="visually-hidden"
          />
          <span>Any</span>
        </label>
        <label v-for="type in BODY_TYPES" :key="type" class="body-option">
          <input
            v-model="filters.body_type"
            type="radio"
            name="bodyType"
            :value="type"
            class="visually-hidden"
          />
          <span>{{ type }}</span>
        </label>
      </div>
    </fieldset>

    <div class="row g-2 mb-3">
      <div class="col-6">
        <label for="minPrice" class="form-label">Min price (RM)</label>
        <input
          id="minPrice"
          v-model.number="filters.minPrice"
          type="number"
          min="0"
          step="1000"
          class="form-control"
          placeholder="Any"
        />
      </div>
      <div class="col-6">
        <label for="maxPrice" class="form-label">Max price (RM)</label>
        <input
          id="maxPrice"
          v-model.number="filters.maxPrice"
          type="number"
          min="0"
          step="1000"
          class="form-control"
          placeholder="Any"
        />
      </div>
    </div>

    <button type="button" class="btn btn-outline-secondary w-100" @click="resetFilters">
      Clear filters
    </button>
  </div>
</template>

<style scoped>
.search-bar {
  padding: 1.25rem;
}

fieldset {
  border: 0;
  margin: 0;
  padding: 0;
}

.input-icon {
  position: relative;
}

.input-icon :deep(svg) {
  position: absolute;
  top: 50%;
  left: 0.9rem;
  color: var(--dd-muted);
  transform: translateY(-50%);
  pointer-events: none;
}

.input-icon .form-control {
  padding-left: 2.6rem;
}

.body-options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.body-option span {
  display: inline-flex;
  align-items: center;
  min-height: 36px;
  padding: 0.3rem 0.85rem;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius-pill);
  background: var(--dd-white);
  color: var(--dd-ink);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.body-option span:hover {
  border-color: var(--dd-primary);
  color: var(--dd-primary);
}

.body-option input:checked + span {
  border-color: var(--dd-primary);
  background: var(--dd-primary);
  color: var(--dd-white);
}

.body-option input:focus-visible + span {
  outline: 3px solid var(--dd-primary-100);
  outline-offset: 2px;
}
</style>
