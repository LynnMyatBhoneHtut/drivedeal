<script setup>
// Like / unlike a car. OWNER: Yati.
// Lai puts this on the Car detail page, so keep these props as agreed.
// Usage: <LikeButton :listing-id="car.listing_id" :likes="car.likes" :liked="car.liked" />
// Calls likes.php POST (like) / DELETE (unlike) -> { "likes": 4, "liked": true }
defineProps({
  listingId: { type: [Number, String], required: true },
  likes: { type: Number, default: 0 }, // starting count
  liked: { type: Boolean, default: false } // has the logged-in user liked it already?
})
</script>

<template>
  <!-- Placeholder - Yati builds the real button -->
  <button type="button" class="btn btn-outline-danger btn-sm" disabled>
    &hearts; {{ likes }} (LikeButton placeholder)
  </button>
</template>
