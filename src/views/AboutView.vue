<script setup>
import { reactive, ref } from 'vue'
import { apiPost } from '../services/api'

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
</script>

<template>
  <div>
    <!-- Page heading -->
    <section class="about-header text-white py-5">
      <div class="container">
        <h1 class="fw-bold mb-3">About DriveDeal</h1>
        <p class="mb-0">
          Making it easier to explore, buy and sell cars.
        </p>
      </div>
    </section>

    <!-- About section -->
    <section class="container py-5">
      <div class="row align-items-center g-5">
        <div class="col-12 col-lg-7">
          <p class="section-label">WHO WE ARE</p>

          <h2 class="fw-bold mb-4">
            A Simpler Way to Find Your Next Car
          </h2>

          <p class="text-muted">
            DriveDeal is a car marketplace designed to connect
            buyers and sellers through a convenient online platform.
          </p>

          <p class="text-muted">
            Our platform allows users to browse car listings,
            explore vehicle information and contact sellers
            directly to learn more about available cars.
          </p>

          <p class="text-muted mb-0">
            Whether you're searching for your next vehicle
            or planning to sell your current car,
            DriveDeal aims to make the process straightforward.
          </p>
        </div>

        <div class="col-12 col-lg-5">
          <div class="about-card">
            <h3 class="h4 fw-bold mb-4">
              What You Can Do
            </h3>

            <div class="feature-item">
              <span class="feature-icon" aria-hidden="true">✓</span>
              <div>
                <strong>Explore Cars</strong>
                <p class="text-muted mb-0">
                  Browse listings with useful search and filters.
                </p>
              </div>
            </div>

            <div class="feature-item">
              <span class="feature-icon" aria-hidden="true">✓</span>
              <div>
                <strong>View Vehicle Details</strong>
                <p class="text-muted mb-0">
                  Check car specifications and information.
                </p>
              </div>
            </div>

            <div class="feature-item">
              <span class="feature-icon" aria-hidden="true">✓</span>
              <div>
                <strong>Connect With Sellers</strong>
                <p class="text-muted mb-0">
                  Send enquiries about cars you're interested in.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact section -->
    <section class="contact-section py-5">
      <div class="container">
        <div class="row g-5">
          <div class="col-12 col-lg-5">
            <p class="section-label">GET IN TOUCH</p>

            <h2 class="fw-bold mb-3">
              Contact Us
            </h2>

            <p class="text-muted">
              Have a question or feedback about DriveDeal?
              Fill in the form and send us a message.
            </p>
          </div>

          <div class="col-12 col-lg-7">
            <div class="contact-card">
              <h3 class="h4 fw-bold mb-4">
                Send Us a Message
              </h3>

              <form @submit.prevent="submitForm">
                <div class="mb-3">
                  <label for="contactName" class="form-label">
                    Full Name
                  </label>

                  <input
id="contactName" v-model.trim="form.name" type="text" class="form-control"
                    placeholder="Enter your full name" maxlength="100" required />
                </div>

                <div class="mb-3">
                  <label for="contactEmail" class="form-label">
                    Email Address
                  </label>

                  <input
id="contactEmail" v-model.trim="form.email" type="email" class="form-control"
                    placeholder="Enter your email" maxlength="150" required />
                </div>

                <div class="mb-4">
                  <label for="contactMessage" class="form-label">
                    Message
                  </label>

                  <textarea
id="contactMessage" v-model.trim="form.message" class="form-control" rows="5" minlength="10"
                    maxlength="2000" placeholder="Write your message here..." required></textarea>
                </div>

                <div v-if="successMessage" class="alert alert-success" role="status">
                  {{ successMessage }}
                </div>

                <div v-if="errorMessage" class="alert alert-danger" role="alert">
                  {{ errorMessage }}
                </div>

                <button type="submit" class="btn btn-copper px-4" :disabled="isSubmitting">
                  {{ isSubmitting ? 'Sending...' : 'Send Message' }}
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
.about-header {
  background: #1c2228;
}

.about-header p {
  color: #e8e3df;
}

.section-label {
  color: #c2410c;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.about-card {
  background: #faf8f5;
  border: 1px solid #e9e5e1;
  border-radius: 14px;
  padding: 30px;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 22px;
}

.feature-item:last-child {
  margin-bottom: 0;
}

.feature-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #c2410c;
  color: white;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  flex-shrink: 0;
}

.contact-section {
  background: #faf8f5;
}

.contact-card {
  background: white;
  border: 1px solid #e9e5e1;
  border-radius: 14px;
  padding: 35px;
}

.form-control:focus {
  border-color: #c2410c;
  box-shadow: 0 0 0 0.2rem rgba(194, 65, 12, 0.15);
}

.btn-copper {
  background: #c2410c;
  border: 1px solid #c2410c;
  color: white;
  font-weight: 600;
}

.btn-copper:hover {
  background: #9a3412;
  border-color: #9a3412;
  color: white;
}

.btn-copper:disabled {
  background: #adadad;
  border-color: #adadad;
}

@media (max-width: 767px) {

  .contact-card,
  .about-card {
    padding: 22px;
  }
}
</style>
