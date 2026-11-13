import { createApp } from 'vue'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js' // navbar toggler, dropdowns, modals
import './assets/main.css'
import { createPinia } from 'pinia' // Yati - watchlist store (D/HD)
import App from './App.vue'
import router from './router'

createApp(App).use(createPinia()).use(router).mount('#app')
