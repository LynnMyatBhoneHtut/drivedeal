<script setup>
// Sell a car page - /sell
// OWNER: Lynn
// CarForm -> listings.php POST -> upload.php for each photo -> the new car's detail page.
// If a photo fails after the car was created, go to the Edit page and list the failed photos there.
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import CarForm from '../components/CarForm.vue'
import { apiPost, apiUpload } from '../services/api'
import { currentUser, getUser } from '../services/auth'

const router = useRouter()
const saving = ref(false)
const errorMessage = ref('')
const progress = ref('')

async function save(carData, files) {
  const user = getUser()
  if (!user) {
    errorMessage.value = 'Please log in to sell a car.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  progress.value = 'Saving your car...'

  let listingId
  try {
    const result = await apiPost('listings.php', { ...carData, seller_id: user.user_id })
    listingId = result.listing_id
  } catch (e) {
    errorMessage.value = e.message
    saving.value = false
    progress.value = ''
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }

  // Upload photos one at a time, so the first one becomes the cover photo
  const failedPhotos = []
  for (const [index, file] of files.entries()) {
    progress.value = `Uploading photo ${index + 1} of ${files.length}...`
    try {
      const form = new FormData()
      form.append('image', file)
      form.append('listing_id', listingId)
      form.append('seller_id', user.user_id)
      await apiUpload('upload.php', form)
    } catch (e) {
      failedPhotos.push(`${file.name}: ${e.message}`)
    }
  }

  saving.value = false
  progress.value = ''
  if (failedPhotos.length) {
    router.push({ name: 'edit-listing', params: { id: listingId }, state: { failedPhotos } })
  } else {
    router.push({ name: 'car-detail', params: { id: listingId } })
  }
}
</script>

<template>
  <section class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-9">
        <h1 class="mb-1">Sell a car</h1>
        <p class="text-muted mb-4">
          Fill in your car's details and add photos to list it for sale.
        </p>

        <div v-if="!currentUser" class="alert alert-info" role="alert">
          <h2 class="h5">Please log in to sell a car</h2>
          <p class="mb-0">
            <router-link :to="{ name: 'login' }" class="alert-link">Log in</router-link>
            or
            <router-link :to="{ name: 'register' }" class="alert-link"
              >create an account</router-link
            >
            to list your car.
          </p>
        </div>

        <template v-else>
          <div v-if="errorMessage" class="alert alert-danger" role="alert">{{ errorMessage }}</div>
          <div v-if="progress" class="alert alert-info d-flex align-items-center" role="status">
            <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
            {{ progress }}
          </div>

          <div class="card shadow-sm">
            <div class="card-body p-3 p-md-4">
              <CarForm :saving="saving" @submit="save" />
            </div>
          </div>
        </template>
      </div>
    </div>
  </section>
</template>
