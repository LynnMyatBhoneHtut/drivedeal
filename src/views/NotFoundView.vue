<script setup>
// 404 page - every unknown URL. OWNER: Lai.
import AppIcon from '../components/AppIcon.vue'
import logoMarkUrl from '../assets/brand/logo-mark.svg'
</script>

<template>
  <section class="not-found">
    <div class="container">
      <div class="not-found-inner">
        <img :src="logoMarkUrl" alt="" class="not-found-mark" width="64" height="52" />
        <p class="error-code" aria-hidden="true">404</p>
        <h1 class="not-found-title">This road doesn't go anywhere</h1>
        <p class="dd-lead mb-4">
          The page you're looking for may have moved or no longer exists. Let's get you back on
          track.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2 gap-sm-3">
          <router-link :to="{ name: 'home' }" class="btn btn-primary">
            <AppIcon name="home" :size="18" /> Back to home
          </router-link>
          <router-link :to="{ name: 'cars' }" class="btn btn-outline-primary">
            <AppIcon name="search" :size="18" /> Browse cars
          </router-link>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.not-found {
  display: flex;
  align-items: center;
  min-height: 70vh;
  padding: 4rem 0;
  background: var(--dd-hero-bg);
}

.not-found-inner {
  max-width: 560px;
  margin: 0 auto;
  text-align: center;
}

.not-found-mark {
  width: 64px;
  height: auto;
}

.error-code {
  margin: 0.5rem 0 0.25rem;
  font-size: clamp(5rem, 3rem + 10vw, 8.5rem);
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.04em;
  background: var(--dd-gradient);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.not-found-title {
  margin-bottom: 0.75rem;
  font-size: clamp(1.6rem, 1.2rem + 1.4vw, 2.2rem);
}
</style>
