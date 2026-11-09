<script setup>
// Footer, on every page. OWNER: Lai.
const year = new Date().getFullYear()
</script>

<template>
  <footer class="bg-light border-top py-3 mt-4">
    <div class="container text-center text-muted small">
      &copy; {{ year }} DriveDeal - COS30043 group project
    </div>
  </footer>
</template>
