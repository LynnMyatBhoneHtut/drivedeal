<script setup>
// Add / edit a car - shared by Sell a car and Edit listing. OWNER: Lynn.
// Usage: <CarForm :saving="saving" @submit="save" />                       (Sell: empty form)
//        <CarForm :initial-car="car" :saving="saving" @submit="save"
//                 @remove-image="removeImage" />                            (Edit: pre-filled)
//
// submit(carData, newFiles): carData has every car field (plus status in edit mode),
//   already trimmed and turned into numbers; newFiles is an array of File objects to upload.
// remove-image(image): one of initialCar.images - the page deletes it with upload.php DELETE.
// The same rules are checked again in api/listings.php.
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { imageUrl } from '../services/api'

const props = defineProps({
  initialCar: { type: Object, default: null },
  saving: { type: Boolean, default: false }
})
const emit = defineEmits(['submit', 'remove-image'])

const TRANSMISSIONS = ['Automatic', 'Manual']
const FUEL_TYPES = ['Petrol', 'Diesel', 'Hybrid', 'Electric']
const BODY_TYPES = ['Sedan', 'Hatchback', 'SUV', 'MPV', 'Pickup']
const STATUSES = ['available', 'reserved', 'sold']
const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp']
const MAX_PHOTO_BYTES = 2 * 1024 * 1024
const MAX_PHOTOS = 8
const MAX_YEAR = new Date().getFullYear() + 1

const isEdit = computed(() => props.initialCar !== null)

function emptyForm() {
  return {
    make: '',
    model: '',
    year: '',
    price: '',
    mileage_km: '',
    transmission: '',
    fuel_type: '',
    body_type: '',
    colour: '',
    location: '',
    description: '',
    status: 'available'
  }
}

// Local copy of the car - initialCar itself is never changed
const form = reactive(emptyForm())
const touched = reactive({})
const submitted = ref(false)

// Photos picked in this form, not uploaded yet: { key, file, url }
const newPhotos = ref([])
const photoErrors = ref([])
let nextKey = 1

function clearNewPhotos() {
  newPhotos.value.forEach((photo) => URL.revokeObjectURL(photo.url))
  newPhotos.value = []
  photoErrors.value = []
}

// Copy initialCar into the form whenever the page gives us a (re)loaded car
watch(
  () => props.initialCar,
  (car) => {
    const fresh = emptyForm()
    if (car) {
      for (const key of Object.keys(fresh)) {
        if (car[key] !== null && car[key] !== undefined) fresh[key] = String(car[key])
      }
      // "48000.00" -> "48000"
      if (fresh.price !== '') fresh.price = String(Number(fresh.price))
    }
    Object.assign(form, fresh)
    for (const key of Object.keys(touched)) delete touched[key]
    submitted.value = false
    clearNewPhotos()
  },
  { immediate: true }
)

onBeforeUnmount(clearNewPhotos)

// Keep a body type from the database selectable even if it isn't in our list
const bodyTypeOptions = computed(() => {
  const current = props.initialCar && props.initialCar.body_type
  return current && !BODY_TYPES.includes(current) ? [...BODY_TYPES, current] : BODY_TYPES
})

const existingImages = computed(() => (props.initialCar && props.initialCar.images) || [])
const totalPhotos = computed(() => existingImages.value.length + newPhotos.value.length)

// ---- Validation (same rules as api/listings.php) ----
const text = (value) => String(value ?? '').trim()
const isWholeNumber = (value) => /^\d+$/.test(text(value))

const errors = computed(() => {
  const e = {}
  const make = text(form.make)
  if (!make) e.make = 'Make is required.'
  else if (make.length > 50) e.make = 'Make must be 50 characters or fewer.'

  const model = text(form.model)
  if (!model) e.model = 'Model is required.'
  else if (model.length > 50) e.model = 'Model must be 50 characters or fewer.'

  const year = Number(text(form.year))
  if (!isWholeNumber(form.year) || year < 1980 || year > MAX_YEAR) {
    e.year = `Enter a whole year from 1980 to ${MAX_YEAR}.`
  }

  const price = Number(text(form.price))
  if (text(form.price) === '' || !Number.isFinite(price) || price <= 0 || price > 10000000) {
    e.price = 'Enter a price more than RM 0 and at most RM 10,000,000.'
  }

  const mileage = Number(text(form.mileage_km))
  if (!isWholeNumber(form.mileage_km) || mileage > 2000000) {
    e.mileage_km = 'Enter whole kilometres from 0 to 2,000,000.'
  }

  if (!TRANSMISSIONS.includes(form.transmission)) e.transmission = 'Choose a transmission.'
  if (!FUEL_TYPES.includes(form.fuel_type)) e.fuel_type = 'Choose a fuel type.'
  if (text(form.body_type).length > 30) e.body_type = 'Body type must be 30 characters or fewer.'
  if (text(form.colour).length > 30) e.colour = 'Colour must be 30 characters or fewer.'
  if (text(form.location).length > 100) e.location = 'Location must be 100 characters or fewer.'
  if (text(form.description).length > 2000) {
    e.description = 'Description must be 2000 characters or fewer.'
  }
  if (isEdit.value && !STATUSES.includes(form.status)) e.status = 'Choose a status.'
  return e
})

/** Show a field's error only after the user has left the field or pressed Save. */
function showError(field) {
  return (submitted.value || touched[field]) && Boolean(errors.value[field])
}

function fieldClass(field) {
  return { 'is-invalid': showError(field) }
}

const pricePreview = computed(() => {
  const price = Number(text(form.price))
  return text(form.price) !== '' && Number.isFinite(price) && price > 0
    ? `RM ${price.toLocaleString('en-MY')}`
    : ''
})

// ---- Photos ----
function formatSize(bytes) {
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function pickPhotos(event) {
  photoErrors.value = []
  for (const file of Array.from(event.target.files || [])) {
    if (!PHOTO_TYPES.includes(file.type)) {
      photoErrors.value.push(`${file.name}: only JPG, PNG or WEBP photos are allowed.`)
    } else if (file.size > MAX_PHOTO_BYTES) {
      photoErrors.value.push(`${file.name} is ${formatSize(file.size)} - the maximum is 2 MB.`)
    } else if (totalPhotos.value >= MAX_PHOTOS) {
      photoErrors.value.push(`${file.name}: a car can have at most ${MAX_PHOTOS} photos.`)
    } else {
      newPhotos.value.push({ key: nextKey++, file, url: URL.createObjectURL(file) })
    }
  }
  event.target.value = '' // lets the same file be picked again after removing it
}

function removeNewPhoto(index) {
  URL.revokeObjectURL(newPhotos.value[index].url)
  newPhotos.value.splice(index, 1)
}

// ---- Submit ----
function submit() {
  submitted.value = true
  if (Object.keys(errors.value).length) {
    // Bring the first problem into view
    requestAnimationFrame(() => {
      const first = document.querySelector('.car-form .is-invalid')
      if (first) first.focus()
    })
    return
  }

  const optional = (value) => text(value) || null
  const carData = {
    make: text(form.make),
    model: text(form.model),
    year: Number(text(form.year)),
    price: Number(text(form.price)),
    mileage_km: Number(text(form.mileage_km)),
    transmission: form.transmission,
    fuel_type: form.fuel_type,
    body_type: optional(form.body_type),
    colour: optional(form.colour),
    location: optional(form.location),
    description: optional(form.description)
  }
  if (isEdit.value) carData.status = form.status

  emit(
    'submit',
    carData,
    newPhotos.value.map((photo) => photo.file)
  )
}
</script>

<template>
  <form class="car-form" novalidate @submit.prevent="submit">
    <div class="row g-3">
      <div class="col-12 col-md-6">
        <label for="car-make" class="form-label">Make <span class="text-danger">*</span></label>
        <input
          id="car-make"
          v-model="form.make"
          type="text"
          class="form-control"
          :class="fieldClass('make')"
          maxlength="50"
          placeholder="e.g. Perodua"
          required
          @blur="touched.make = true"
        />
        <div class="invalid-feedback">{{ errors.make }}</div>
      </div>

      <div class="col-12 col-md-6">
        <label for="car-model" class="form-label">Model <span class="text-danger">*</span></label>
        <input
          id="car-model"
          v-model="form.model"
          type="text"
          class="form-control"
          :class="fieldClass('model')"
          maxlength="50"
          placeholder="e.g. Myvi"
          required
          @blur="touched.model = true"
        />
        <div class="invalid-feedback">{{ errors.model }}</div>
      </div>

      <div class="col-6 col-md-4">
        <label for="car-year" class="form-label">Year <span class="text-danger">*</span></label>
        <input
          id="car-year"
          v-model="form.year"
          type="number"
          class="form-control"
          :class="fieldClass('year')"
          min="1980"
          :max="MAX_YEAR"
          step="1"
          inputmode="numeric"
          placeholder="e.g. 2021"
          required
          @blur="touched.year = true"
        />
        <div class="invalid-feedback">{{ errors.year }}</div>
      </div>

      <div class="col-6 col-md-4">
        <label for="car-price" class="form-label"
          >Price (RM) <span class="text-danger">*</span></label
        >
        <input
          id="car-price"
          v-model="form.price"
          type="number"
          class="form-control"
          :class="fieldClass('price')"
          min="1"
          max="10000000"
          step="any"
          inputmode="decimal"
          placeholder="e.g. 48000"
          required
          @blur="touched.price = true"
        />
        <div class="invalid-feedback">{{ errors.price }}</div>
        <div v-if="pricePreview && !showError('price')" class="form-text">{{ pricePreview }}</div>
      </div>

      <div class="col-12 col-md-4">
        <label for="car-mileage" class="form-label">
          Mileage (km) <span class="text-danger">*</span>
        </label>
        <input
          id="car-mileage"
          v-model="form.mileage_km"
          type="number"
          class="form-control"
          :class="fieldClass('mileage_km')"
          min="0"
          max="2000000"
          step="1"
          inputmode="numeric"
          placeholder="e.g. 35000"
          required
          @blur="touched.mileage_km = true"
        />
        <div class="invalid-feedback">{{ errors.mileage_km }}</div>
      </div>

      <div class="col-6 col-md-4">
        <label for="car-transmission" class="form-label">
          Transmission <span class="text-danger">*</span>
        </label>
        <select
          id="car-transmission"
          v-model="form.transmission"
          class="form-select"
          :class="fieldClass('transmission')"
          required
          @blur="touched.transmission = true"
        >
          <option value="" disabled>Choose...</option>
          <option v-for="option in TRANSMISSIONS" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
        <div class="invalid-feedback">{{ errors.transmission }}</div>
      </div>

      <div class="col-6 col-md-4">
        <label for="car-fuel" class="form-label"
          >Fuel type <span class="text-danger">*</span></label
        >
        <select
          id="car-fuel"
          v-model="form.fuel_type"
          class="form-select"
          :class="fieldClass('fuel_type')"
          required
          @blur="touched.fuel_type = true"
        >
          <option value="" disabled>Choose...</option>
          <option v-for="option in FUEL_TYPES" :key="option" :value="option">{{ option }}</option>
        </select>
        <div class="invalid-feedback">{{ errors.fuel_type }}</div>
      </div>

      <div class="col-12 col-md-4">
        <label for="car-body" class="form-label">Body type</label>
        <select
          id="car-body"
          v-model="form.body_type"
          class="form-select"
          :class="fieldClass('body_type')"
          @blur="touched.body_type = true"
        >
          <option value="">Not specified</option>
          <option v-for="option in bodyTypeOptions" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
        <div class="invalid-feedback">{{ errors.body_type }}</div>
      </div>

      <div class="col-12 col-md-4">
        <label for="car-colour" class="form-label">Colour</label>
        <input
          id="car-colour"
          v-model="form.colour"
          type="text"
          class="form-control"
          :class="fieldClass('colour')"
          maxlength="30"
          placeholder="e.g. Red"
          @blur="touched.colour = true"
        />
        <div class="invalid-feedback">{{ errors.colour }}</div>
      </div>

      <div class="col-12" :class="isEdit ? 'col-md-4' : 'col-md-8'">
        <label for="car-location" class="form-label">Location</label>
        <input
          id="car-location"
          v-model="form.location"
          type="text"
          class="form-control"
          :class="fieldClass('location')"
          maxlength="100"
          placeholder="e.g. Shah Alam, Selangor"
          @blur="touched.location = true"
        />
        <div class="invalid-feedback">{{ errors.location }}</div>
      </div>

      <div v-if="isEdit" class="col-12 col-md-4">
        <label for="car-status" class="form-label">Status <span class="text-danger">*</span></label>
        <select
          id="car-status"
          v-model="form.status"
          class="form-select"
          :class="fieldClass('status')"
          @blur="touched.status = true"
        >
          <option value="available">Available</option>
          <option value="reserved">Reserved</option>
          <option value="sold">Sold</option>
        </select>
        <div class="invalid-feedback">{{ errors.status }}</div>
      </div>

      <div class="col-12">
        <label for="car-description" class="form-label">Description</label>
        <textarea
          id="car-description"
          v-model="form.description"
          class="form-control"
          :class="fieldClass('description')"
          rows="5"
          placeholder="Condition, service history, extras..."
          @blur="touched.description = true"
        ></textarea>
        <div class="invalid-feedback">{{ errors.description }}</div>
        <div
          class="form-text text-end"
          :class="{ 'text-danger': text(form.description).length > 2000 }"
        >
          {{ text(form.description).length }} / 2000
        </div>
      </div>

      <!-- Photos -->
      <div class="col-12">
        <label for="car-photos" class="form-label">
          Photos
          <span class="text-muted small">({{ totalPhotos }} / {{ MAX_PHOTOS }})</span>
        </label>

        <div v-if="existingImages.length" class="mb-3">
          <p class="small text-muted mb-2">Current photos (the first one is the cover photo)</p>
          <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-4 g-2">
            <div v-for="(image, index) in existingImages" :key="image.image_id" class="col">
              <div class="card h-100">
                <img
                  :src="imageUrl(image.image_path)"
                  class="card-img-top photo-thumb"
                  :alt="`Photo ${index + 1}`"
                />
                <div class="card-body p-2 d-flex justify-content-between align-items-center">
                  <span v-if="index === 0" class="badge bg-primary">Cover</span>
                  <span v-else></span>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    :disabled="saving"
                    @click="emit('remove-image', image)"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <input
          id="car-photos"
          type="file"
          class="form-control"
          accept="image/jpeg,image/png,image/webp"
          multiple
          :disabled="saving || totalPhotos >= MAX_PHOTOS"
          @change="pickPhotos"
        />
        <div class="form-text">
          JPG, PNG or WEBP, up to 2 MB each, {{ MAX_PHOTOS }} photos per car.
        </div>

        <div v-if="photoErrors.length" class="alert alert-warning mt-2 mb-0 py-2" role="alert">
          <div v-for="message in photoErrors" :key="message" class="small">{{ message }}</div>
        </div>

        <div v-if="newPhotos.length" class="mt-3">
          <p class="small text-muted mb-2">New photos (uploaded when you save)</p>
          <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-4 g-2">
            <div v-for="(photo, index) in newPhotos" :key="photo.key" class="col">
              <div class="card h-100">
                <img :src="photo.url" class="card-img-top photo-thumb" :alt="photo.file.name" />
                <div class="card-body p-2 d-flex justify-content-between align-items-center gap-2">
                  <span class="small text-truncate" :title="photo.file.name">
                    <span v-if="!existingImages.length && index === 0" class="badge bg-primary">
                      Cover
                    </span>
                    <template v-else>{{ photo.file.name }}</template>
                  </span>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary flex-shrink-0"
                    :disabled="saving"
                    :aria-label="`Remove ${photo.file.name}`"
                    @click="removeNewPhoto(index)"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div
          v-if="submitted && Object.keys(errors).length"
          class="alert alert-danger py-2"
          role="alert"
        >
          Please fix the highlighted fields.
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100 w-sm-auto" :disabled="saving">
          <span
            v-if="saving"
            class="spinner-border spinner-border-sm me-2"
            role="status"
            aria-hidden="true"
          ></span>
          {{ saving ? 'Saving...' : isEdit ? 'Save changes' : 'List my car' }}
        </button>
      </div>
    </div>
  </form>
</template>

<style scoped>
.photo-thumb {
  aspect-ratio: 4 / 3;
  object-fit: cover;
}

@media (min-width: 576px) {
  .w-sm-auto {
    width: auto !important;
  }
}
</style>
