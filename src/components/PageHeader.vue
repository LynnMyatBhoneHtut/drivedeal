<script setup>
// Slim page header with a breadcrumb, used by Browse-style pages. OWNER: Lynn (shared).
// Usage:
//   <PageHeader title="My listings" :crumbs="[{ label: 'Profile', to: { name: 'profile' } }]">
//     <template #subtitle>3 cars</template>
//     <template #actions><router-link ...>Sell a car</router-link></template>
//   </PageHeader>
// Home is always the first crumb; the title is the last (current page).
defineProps({
  title: { type: String, required: true },
  crumbs: { type: Array, default: () => [] }
})
</script>

<template>
  <header class="dd-page-header">
    <div class="container">
      <nav aria-label="Breadcrumb">
        <ol class="breadcrumb dd-breadcrumb">
          <li class="breadcrumb-item"><router-link :to="{ name: 'home' }">Home</router-link></li>
          <li v-for="crumb in crumbs" :key="crumb.label" class="breadcrumb-item">
            <router-link :to="crumb.to">{{ crumb.label }}</router-link>
          </li>
          <li class="breadcrumb-item active" aria-current="page">{{ title }}</li>
        </ol>
      </nav>
      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
        <div class="min-w-0">
          <h1 class="dd-page-title">{{ title }}</h1>
          <p v-if="$slots.subtitle" class="text-muted mb-0 mt-1">
            <slot name="subtitle" />
          </p>
        </div>
        <div v-if="$slots.actions" class="d-flex flex-wrap gap-2">
          <slot name="actions" />
        </div>
      </div>
      <slot />
    </div>
  </header>
</template>

<style scoped>
.min-w-0 {
  min-width: 0;
}
</style>
