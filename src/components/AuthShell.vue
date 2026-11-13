<script setup>
// Split layout for Sign in / Register: form card on the left, blue benefits panel on the right.
// On phones only the form card shows. OWNER: Lynn (shared, look only).
// Usage: <AuthShell headline="..." :benefits="['...', '...', '...']"> form card content </AuthShell>
import AppIcon from './AppIcon.vue'
import logoWhiteUrl from '../assets/brand/logo-white.svg'

defineProps({
  headline: { type: String, required: true },
  benefits: { type: Array, default: () => [] }
})
</script>

<template>
  <section class="auth">
    <div class="container">
      <div class="auth-grid">
        <div class="auth-card dd-card">
          <slot />
        </div>

        <aside class="auth-panel dd-panel-blue d-none d-lg-flex" aria-label="Why join DriveDeal">
          <span class="auth-shape" aria-hidden="true"></span>
          <img
            :src="logoWhiteUrl"
            alt="DriveDeal"
            class="auth-logo"
            width="209"
            height="30"
            loading="lazy"
          />
          <div class="auth-copy">
            <h2 class="auth-headline">{{ headline }}</h2>
            <ul class="auth-benefits">
              <li v-for="benefit in benefits" :key="benefit">
                <span class="auth-check"
                  ><AppIcon name="check" :size="16" :stroke-width="2.5"
                /></span>
                {{ benefit }}
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </div>
  </section>
</template>

<style scoped>
.auth {
  padding: clamp(2rem, 1rem + 4vw, 4rem) 0;
  background: var(--dd-hero-bg);
}

.auth-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  justify-items: center;
  gap: 2rem;
}

.auth-card {
  width: 100%;
  max-width: 440px;
  padding: clamp(1.5rem, 1rem + 2vw, 2.25rem);
  box-shadow: var(--dd-shadow);
}

.auth-panel {
  flex-direction: column;
  justify-content: space-between;
  gap: 2rem;
  min-height: 520px;
  padding: 2.5rem;
}

.auth-shape {
  position: absolute;
  right: -90px;
  bottom: -90px;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: var(--dd-white-15);
  pointer-events: none;
}

.auth-logo {
  position: relative;
  width: auto;
  height: 30px;
  align-self: flex-start;
}

.auth-copy {
  position: relative;
}

.auth-headline {
  margin-bottom: 1.5rem;
  color: var(--dd-white);
  font-size: clamp(1.6rem, 1.2rem + 1vw, 2.1rem);
}

.auth-benefits {
  display: grid;
  gap: 0.9rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.auth-benefits li {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--dd-white);
  font-weight: 500;
}

.auth-check {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--dd-white);
  color: var(--dd-primary);
}

@media (min-width: 992px) {
  .auth-grid {
    grid-template-columns: minmax(0, 440px) minmax(0, 1fr);
    align-items: stretch;
    justify-items: stretch;
    gap: 2.5rem;
  }

  .auth-card {
    align-self: center;
  }
}
</style>
