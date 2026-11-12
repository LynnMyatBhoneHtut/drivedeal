<script setup>
// Like / unlike a car. OWNER: Yati.
// Lai puts this on the Car detail page, so keep these props as agreed.
// Usage: <LikeButton :listing-id="car.listing_id" :likes="car.likes" :liked="car.liked" />
// Calls likes.php POST (like) / DELETE (unlike) -> { "likes": 4, "liked": true }
//
// Extra (optional): @change="({ likes, liked }) => ..." fires after the server confirms,
// e.g. the Liked cars page uses it to remove a car when it is unliked.
//
// Guests are sent to the login page and come back here afterwards.
// The heart changes straight away (optimistic update) and goes back if the server fails.
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiDelete, apiPost } from '../services/api'
import { currentUser } from '../services/auth'

const props = defineProps({
  listingId: { type: [Number, String], required: true },
  likes: { type: Number, default: 0 }, // starting count
  liked: { type: Boolean, default: false } // has the logged-in user liked it already?
})
const emit = defineEmits(['change'])

const route = useRoute()
const router = useRouter()

// Local copies, so the button can update without the parent page reloading the car
const count = ref(props.likes)
const isLiked = ref(props.liked)
const busy = ref(false)
const errorMessage = ref('')

// If the page loads a different car (or reloads it), start from the new values
watch(
  () => [props.listingId, props.likes, props.liked],
  () => {
    count.value = props.likes
    isLiked.value = props.liked
  }
)

async function toggle() {
  if (!currentUser.value) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }
  if (busy.value) return

  const wasLiked = isLiked.value
  const oldCount = count.value
  // Optimistic update: show the change now
  isLiked.value = !wasLiked
  count.value = oldCount + (wasLiked ? -1 : 1)
  busy.value = true
  errorMessage.value = ''

  const body = { user_id: currentUser.value.user_id, listing_id: Number(props.listingId) }
  try {
    const result = wasLiked ? await apiDelete('likes.php', body) : await apiPost('likes.php', body)
    count.value = result.likes
    isLiked.value = result.liked
    emit('change', { likes: result.likes, liked: result.liked })
  } catch (e) {
    // Put it back the way it was
    isLiked.value = wasLiked
    count.value = oldCount
    errorMessage.value = e.message
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <span class="like-button d-inline-flex flex-column align-items-start">
    <button
      type="button"
      class="btn btn-sm"
      :class="isLiked ? 'btn-danger' : 'btn-outline-danger'"
      :aria-pressed="isLiked"
      :aria-label="isLiked ? `Unlike (${count} likes)` : `Like (${count} likes)`"
      :title="currentUser ? '' : 'Log in to like cars'"
      :disabled="busy"
      @click="toggle"
    >
      <span aria-hidden="true">{{ isLiked ? '♥' : '♡' }}</span>
      {{ count }} {{ count === 1 ? 'like' : 'likes' }}
    </button>
    <small v-if="errorMessage" class="text-danger mt-1" role="alert">{{ errorMessage }}</small>
  </span>
</template>

<style scoped>
/* Sit above Bootstrap's .stretched-link so the button stays clickable inside a card */
.like-button {
  position: relative;
  z-index: 2;
}
</style>
