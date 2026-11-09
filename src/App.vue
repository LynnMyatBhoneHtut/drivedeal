<script setup>
// The frame around every page. Shared file: don't change it without telling the group.
import Navbar from './components/Navbar.vue'
import Footer from './components/Footer.vue'
</script>

<template>
  <Navbar />
  <main>
    <router-view />
  </main>
  <Footer />
</template>
