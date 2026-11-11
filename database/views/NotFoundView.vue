<template>
  <section class="container text-center not-found-page">
    <div class="mx-auto">
      <p class="error-code mb-0">404</p>

      <h1 class="fw-bold mb-3">Page Not Found</h1>

      <p class="text-muted mb-4">
        Sorry, we couldn't find the page you're looking for.
        It may have been moved or no longer exists.
      </p>

      <div class="d-flex flex-wrap justify-content-center gap-3">
        <router-link :to="{ name: 'home' }" class="btn btn-dark px-4">
          Back to Home
        </router-link>

        <router-link :to="{ name: 'cars' }" class="btn btn-outline-dark px-4">
          Browse Cars
        </router-link>
      </div>
    </div>
  </section>
</template>

<style scoped>
.not-found-page {
  min-height: 65vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
}

.not-found-page>div {
  max-width: 550px;
}

.error-code {
  font-size: clamp(5rem, 12vw, 9rem);
  font-weight: 800;
  line-height: 1.1;
  color: #212529;
}
</style>
