<script setup>
// Like / unlike a car. OWNER: Yati.
// Lai puts this on the Car detail page, so keep these props as agreed.
// Usage: <LikeButton :listing-id="car.listing_id" :likes="car.likes" :liked="car.liked" />
// Calls likes.php POST (like) / DELETE (unlike) -> { "likes": 4, "liked": true }
//
// Extra (optional): @change="({ likes, liked }) => ..." fires after the server confirms,
// e.g. the Liked cars page uses it to remove a car when it is unliked.
//
// compact: a 36px round heart without the count text (used on car cards).
//
// Guests are sent to the login page and come back here afterwards.
// The heart changes straight away (optimistic update) and goes back if the server fails.
import { ref, watch } from 'vue'
import AppIcon from './AppIcon.vue'
import { useRoute, useRouter } from 'vue-router'
import { apiDelete, apiPost } from '../services/api'
import { currentUser } from '../services/auth'

const props = defineProps({
  listingId: { type: [Number, String], required: true },
  likes: { type: Number, default: 0 }, // starting count
  liked: { type: Boolean, default: false }, // has the logged-in user liked it already?
  compact: { type: Boolean, default: false } // round icon-only button (car cards)
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
      class="like-btn"
      :class="{ 'is-liked': isLiked, 'is-compact': compact }"
      :aria-pressed="isLiked"
      :aria-label="isLiked ? `Unlike (${count} likes)` : `Like (${count} likes)`"
      :title="currentUser ? (isLiked ? 'Unlike' : 'Like') : 'Log in to like cars'"
      :disabled="busy"
      @click="toggle"
    >
      <AppIcon :name="isLiked ? 'heart-filled' : 'heart'" :size="compact ? 18 : 20" />
      <span v-if="!compact">{{ count }} {{ count === 1 ? 'like' : 'likes' }}</span>
    </button>
    <small v-if="errorMessage" class="like-error text-danger mt-1" role="alert">
      {{ errorMessage }}
    </small>
  </span>
</template>

<style scoped>
/* Sit above Bootstrap's .stretched-link so the button stays clickable inside a card */
.like-button {
  position: relative;
  z-index: 2;
}

.like-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 44px;
  padding: 0.5rem 1rem;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius-pill);
  background: var(--dd-white);
  color: var(--dd-ink);
  font-weight: 600;
  transition:
    border-color 0.15s ease,
    background-color 0.15s ease,
    color 0.15s ease;
}

.like-btn:hover:not(:disabled) {
  border-color: var(--dd-danger);
  color: var(--dd-danger);
}

.like-btn.is-liked {
  border-color: var(--dd-danger-50);
  background: var(--dd-danger-50);
  color: var(--dd-danger);
}

.like-btn:disabled {
  opacity: 0.7;
}

.like-btn.is-compact {
  justify-content: center;
  width: 36px;
  min-height: 0;
  height: 36px;
  padding: 0;
  border: 0;
  box-shadow: var(--dd-shadow);
}

.like-btn.is-compact.is-liked {
  background: var(--dd-white);
}

@media (prefers-reduced-motion: no-preference) {
  .like-btn.is-liked :deep(svg) {
    animation: like-pop 0.3s ease;
  }
}

@keyframes like-pop {
  50% {
    transform: scale(1.25);
  }
}

.like-error {
  max-width: 180px;
  padding: 0.15rem 0.4rem;
  border-radius: 6px;
  background: var(--dd-white);
}
</style>
