<script setup>
// Footer, on every page. Navy, with link columns. Every link goes to a page we have.
import logoWhiteUrl from '../assets/brand/logo-white.svg'

const year = new Date().getFullYear()

const columns = [
  {
    title: 'Browse',
    links: [
      { label: 'All cars', to: { name: 'cars' } },
      { label: 'Sell a car', to: { name: 'sell' } },
      { label: 'Smart Match', to: { name: 'cars' } }
    ]
  },
  {
    title: 'Account',
    links: [
      { label: 'Sign in', to: { name: 'login' } },
      { label: 'Register', to: { name: 'register' } },
      { label: 'My listings', to: { name: 'my-listings' } },
      { label: 'Liked cars', to: { name: 'liked' } }
    ]
  },
  {
    title: 'DriveDeal',
    links: [
      { label: 'About', to: { name: 'about' } },
      { label: 'Contact', to: { name: 'about' } }
    ]
  }
]
</script>

<template>
  <footer class="dd-footer">
    <div class="container">
      <div class="row g-4 g-lg-5">
        <div class="col-12 col-lg-5">
          <router-link :to="{ name: 'home' }" class="d-inline-block mb-3">
            <img
              :src="logoWhiteUrl"
              alt="DriveDeal"
              class="dd-footer-logo"
              width="209"
              height="30"
              loading="lazy"
            />
          </router-link>
          <p class="dd-footer-tagline mb-0">
            A friendly place to find your next car and sell your current one, with no middlemen.
          </p>
        </div>

        <nav
          v-for="column in columns"
          :key="column.title"
          class="col-6 col-sm-4 col-lg-2"
          :aria-label="`${column.title} links`"
        >
          <h2 class="dd-footer-heading">{{ column.title }}</h2>
          <ul class="list-unstyled mb-0">
            <li v-for="link in column.links" :key="link.label">
              <router-link :to="link.to" class="dd-footer-link">{{ link.label }}</router-link>
            </li>
          </ul>
        </nav>
      </div>

      <div class="dd-footer-bottom">
        &copy; {{ year }} DriveDeal &middot; COS30043 group project, Swinburne University of
        Technology
      </div>
    </div>
  </footer>
</template>

<style scoped>
.dd-footer {
  margin-top: auto;
  padding-top: 64px;
  background: var(--dd-navy);
  color: var(--dd-white-70);
}

.dd-footer-logo {
  display: block;
  width: auto;
  height: 30px;
}

.dd-footer-tagline {
  max-width: 340px;
  font-size: 0.925rem;
}

.dd-footer-heading {
  margin-bottom: 1rem;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--dd-white);
}

.dd-footer-link {
  display: inline-block;
  padding: 0.35rem 0;
  color: var(--dd-white-70);
  font-size: 0.925rem;
  text-decoration: none;
}

.dd-footer-link:hover {
  color: var(--dd-white);
  text-decoration: underline;
}

.dd-footer a:focus-visible {
  outline-color: var(--dd-primary-300);
  box-shadow: none;
}

.dd-footer-bottom {
  margin-top: 48px;
  padding-block: 1.5rem;
  border-top: 1px solid var(--dd-white-15);
  font-size: 0.85rem;
}
</style>
