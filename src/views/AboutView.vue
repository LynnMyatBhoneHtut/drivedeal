<script setup>
import { reactive, ref } from 'vue'
import { apiPost } from '../services/api'
import AppIcon from '../components/AppIcon.vue'

const form = reactive({
  name: '',
  email: '',
  message: ''
})

const isSubmitting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

async function submitForm() {
  successMessage.value = ''
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await apiPost('contact.php', {
      name: form.name.trim(),
      email: form.email.trim(),
      message: form.message.trim()
    })

    successMessage.value = 'Your message has been sent successfully.'

    form.name = ''
    form.email = ''
    form.message = ''
  } catch (error) {
    errorMessage.value = error.message || 'Unable to send your message.'
  } finally {
    isSubmitting.value = false
  }
}

const missions = [
  {
    icon: 'search',
    title: 'Explore cars',
    text: 'Browse listings with search, filters and Smart Match recommendations.'
  },
  {
    icon: 'eye',
    title: 'See the details',
    text: 'Photos, specifications and the seller’s own description on every car.'
  },
  {
    icon: 'message',
    title: 'Talk to the seller',
    text: 'Send an enquiry straight to the owner, with no middlemen.'
  },
  {
    icon: 'tag',
    title: 'Sell for free',
    text: 'List your own car in minutes and manage enquiries in one place.'
  }
]

const nextSteps = [
  { title: 'Your message is saved', text: 'It reaches the DriveDeal team straight away.' },
  { title: 'We read every message', text: 'Questions, feedback and bug reports all help.' },
  { title: 'We reply if needed', text: 'Using the email address you give us.' }
]
</script>

<template>
  <div>
    <!-- Small hero -->
    <section class="about-hero">
      <div class="container">
        <div class="about-hero-inner">
          <span class="dd-eyebrow">About DriveDeal</span>
          <h1 class="about-title">A simpler way to buy and sell cars</h1>
          <p class="dd-lead mb-0">
            DriveDeal is a car marketplace that connects buyers and sellers directly. Browse cars,
            compare them, and talk to the owner, all in one place.
          </p>
        </div>
      </div>
    </section>

    <!-- Mission -->
    <section class="dd-section pt-4 pt-lg-5">
      <div class="container">
        <div class="dd-section-head">
          <span class="dd-eyebrow">What you can do</span>
          <h2 class="mb-0">Everything you need for your next car</h2>
        </div>
        <div class="row g-3 g-lg-4">
          <div v-for="item in missions" :key="item.title" class="col-12 col-sm-6 col-lg-3">
            <div class="dd-feature-card">
              <span class="dd-feature-icon">
                <AppIcon :name="item.icon" :size="28" :stroke-width="1.5" />
              </span>
              <h3>{{ item.title }}</h3>
              <p>{{ item.text }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="dd-section dd-section-surface">
      <div class="container">
        <div class="row g-4 g-lg-5">
          <div class="col-12 col-lg-5">
            <span class="dd-eyebrow">Get in touch</span>
            <h2 class="mb-3">Contact us</h2>
            <p class="text-muted mb-4">
              Have a question or feedback about DriveDeal? Fill in the form and send us a message.
            </p>

            <div class="dd-card dd-card-body">
              <h3 class="h3 mb-3">What happens next</h3>
              <ol class="dd-timeline">
                <li v-for="step in nextSteps" :key="step.title">
                  <p class="fw-bold mb-0 text-body-emphasis">{{ step.title }}</p>
                  <p class="small text-muted mb-0">{{ step.text }}</p>
                </li>
              </ol>
            </div>
          </div>

          <div class="col-12 col-lg-7">
            <div class="dd-card contact-card">
              <h3 class="h3 mb-4 d-flex align-items-center gap-2">
                <AppIcon name="mail" :size="22" /> Send us a message
              </h3>

              <form @submit.prevent="submitForm">
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <label for="contactName" class="form-label">Full name</label>
                    <input
                      id="contactName"
                      v-model.trim="form.name"
                      type="text"
                      class="form-control"
                      placeholder="Your full name"
                      maxlength="100"
                      autocomplete="name"
                      required
                    />
                  </div>

                  <div class="col-12 col-md-6">
                    <label for="contactEmail" class="form-label">Email address</label>
                    <input
                      id="contactEmail"
                      v-model.trim="form.email"
                      type="email"
                      class="form-control"
                      placeholder="you@example.com"
                      maxlength="150"
                      autocomplete="email"
                      required
                    />
                  </div>

                  <div class="col-12">
                    <label for="contactMessage" class="form-label">Message</label>
                    <textarea
                      id="contactMessage"
                      v-model.trim="form.message"
                      class="form-control"
                      rows="5"
                      minlength="10"
                      maxlength="2000"
                      placeholder="Write your message here..."
                      required
                    ></textarea>
                    <div class="form-text">At least 10 characters.</div>
                  </div>
                </div>

                <div v-if="successMessage" class="alert alert-success mt-3 mb-0" role="status">
                  {{ successMessage }}
                </div>

                <div v-if="errorMessage" class="alert alert-danger mt-3 mb-0" role="alert">
                  {{ errorMessage }}
                </div>

                <button type="submit" class="btn btn-primary mt-4" :disabled="isSubmitting">
                  <span
                    v-if="isSubmitting"
                    class="spinner-border spinner-border-sm"
                    aria-hidden="true"
                  ></span>
                  <AppIcon v-else name="arrow-right" :size="18" />
                  {{ isSubmitting ? 'Sending...' : 'Send message' }}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.about-hero {
  padding: clamp(2.5rem, 1.5rem + 4vw, 5rem) 0 clamp(1.5rem, 1rem + 2vw, 2.5rem);
  background: var(--dd-hero-bg);
}

.about-hero-inner {
  max-width: 720px;
}

.about-title {
  margin-bottom: 1rem;
  font-size: clamp(2rem, 1.3rem + 2.6vw, 3rem);
}

.contact-card {
  padding: clamp(1.25rem, 1rem + 1.5vw, 2.25rem);
}
</style>
