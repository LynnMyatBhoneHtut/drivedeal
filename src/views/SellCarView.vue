<script setup>
// Sell a car page - /sell
// OWNER: Lynn
// CarForm -> listings.php POST -> upload.php for each photo -> the new car's detail page.
// If a photo fails after the car was created, go to the Edit page and list the failed photos there.
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import CarForm from '../components/CarForm.vue'
import AppIcon from '../components/AppIcon.vue'
import PageHeader from '../components/PageHeader.vue'
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
  <div>
    <PageHeader title="Sell a car">
      <template #subtitle>Fill in your car's details and add photos to list it for sale.</template>
    </PageHeader>

    <section class="container py-4 py-lg-5">
      <div class="sell-wrap">
        <div v-if="!currentUser" class="dd-empty">
          <span class="dd-icon-circle"><AppIcon name="user" :size="28" /></span>
          <h2>Please sign in to sell a car</h2>
          <p>It's free to list your car, and buyers can contact you straight away.</p>
          <div class="d-flex flex-wrap justify-content-center gap-2">
            <router-link :to="{ name: 'login' }" class="btn btn-primary">Sign in</router-link>
            <router-link :to="{ name: 'register' }" class="btn btn-outline-primary">
              Create an account
            </router-link>
          </div>
        </div>

        <template v-else>
          <div v-if="errorMessage" class="alert alert-danger" role="alert">{{ errorMessage }}</div>
          <div
            v-if="progress"
            class="alert alert-info d-flex align-items-center gap-2"
            role="status"
          >
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            {{ progress }}
          </div>

          <CarForm :saving="saving" @submit="save" />
        </template>
      </div>
    </section>
  </div>
</template>

<style scoped>
.sell-wrap {
  max-width: 920px;
  margin: 0 auto;
}
</style>
