<script setup>
const filters = defineModel({
  type: Object,
  required: true
})

function resetFilters() {
  filters.value = {
    make: '',
    minPrice: '',
    maxPrice: '',
    sort: 'price_asc'
  }
}
</script>

<template>
  <div class="card border-0 bg-light mb-4">
    <div class="card-body p-4">
      <h2 class="h5 fw-bold mb-3">Find Your Car</h2>

      <div class="row g-3">
        <div class="col-12 col-md-6 col-lg-3">
          <label for="carSearch" class="form-label">
            Search Car
          </label>
          <input
id="carSearch" v-model.trim="filters.make" type="search" class="form-control"
            placeholder="e.g. Toyota" />
        </div>

        <div class="col-12 col-md-6 col-lg-3">
          <label for="minPrice" class="form-label">
            Minimum Price (RM)
          </label>
          <input
id="minPrice" v-model.number="filters.minPrice" type="number" min="0" class="form-control"
            placeholder="Min price" />
        </div>

        <div class="col-12 col-md-6 col-lg-3">
          <label for="maxPrice" class="form-label">
            Maximum Price (RM)
          </label>
          <input
id="maxPrice" v-model.number="filters.maxPrice" type="number" min="0" class="form-control"
            placeholder="Max price" />
        </div>

        <div class="col-12 col-md-6 col-lg-3">
          <label for="carSort" class="form-label">
            Sort By
          </label>
          <select id="carSort" v-model="filters.sort" class="form-select">
            <option value="price_asc">
              Price: Low to High
            </option>
            <option value="price_desc">
              Price: High to Low
            </option>
            <option value="year_desc">
              Year: Newest First
            </option>
          </select>
        </div>
      </div>

      <button type="button" class="btn btn-outline-secondary mt-3" @click="resetFilters">
        Reset Filters
      </button>
    </div>
  </div>
</template>