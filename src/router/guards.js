// Route guards. OWNER: Yati.
// Runs before every page change (hooked up in router/index.js, so no one needs to edit that file).
// Return true to allow the page, or a route like { name: 'login' } to redirect.
import { isLoggedIn } from '../services/auth'

export function authGuard(to) {
  // Pages with meta.requiresAuth (Profile, Liked, Sell, Edit, My listings) need a logged-in user.
  // Guests go to the login page, which sends them back here afterwards (?redirect=).
  if (to.meta.requiresAuth && !isLoggedIn()) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  return true
}
