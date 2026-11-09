import { createRouter, createWebHashHistory } from 'vue-router'
import { authGuard } from './guards'

// Lai - Browse
import HomeView from '../views/HomeView.vue'
import CarListView from '../views/CarListView.vue'
import CarDetailView from '../views/CarDetailView.vue'
import AboutView from '../views/AboutView.vue'
import NotFoundView from '../views/NotFoundView.vue'
// Yati - Accounts
import RegisterView from '../views/RegisterView.vue'
import LoginView from '../views/LoginView.vue'
import ProfileView from '../views/ProfileView.vue'
import LikedCarsView from '../views/LikedCarsView.vue'
// Lynn - Selling
import SellCarView from '../views/SellCarView.vue'
import EditListingView from '../views/EditListingView.vue'
import MyListingsView from '../views/MyListingsView.vue'

// All 12 routes, agreed in the group task plan. Shared file: don't change it without telling the group.
// meta.requiresAuth -> must be logged in (checked in guards.js)
// meta.ownerOnly    -> must also own the car (the page checks this after loading the car)
// props: true       -> the page receives :id as a prop
const routes = [
  // Lai
  { path: '/', name: 'home', component: HomeView },
  { path: '/cars', name: 'cars', component: CarListView },
  { path: '/cars/:id', name: 'car-detail', component: CarDetailView, props: true },
  { path: '/about', name: 'about', component: AboutView },

  // Yati
  { path: '/register', name: 'register', component: RegisterView },
  { path: '/login', name: 'login', component: LoginView },
  { path: '/profile', name: 'profile', component: ProfileView, meta: { requiresAuth: true } },
  { path: '/liked', name: 'liked', component: LikedCarsView, meta: { requiresAuth: true } },

  // Lynn
  { path: '/sell', name: 'sell', component: SellCarView, meta: { requiresAuth: true } },
  {
    path: '/cars/:id/edit',
    name: 'edit-listing',
    component: EditListingView,
    props: true,
    meta: { requiresAuth: true, ownerOnly: true }
  },
  {
    path: '/my-listings',
    name: 'my-listings',
    component: MyListingsView,
    meta: { requiresAuth: true }
  },

  // Lai - must stay last: catches every unknown URL
  { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundView }
]

const router = createRouter({
  // Hash history (URLs like #/cars/3) works on Mercury with no server setup
  history: createWebHashHistory(),
  routes,
  linkActiveClass: 'active', // Bootstrap's class for the current nav link
  scrollBehavior: () => ({ top: 0 }) // start each new page at the top
})

router.beforeEach(authGuard)

export default router
