// Route guards. OWNER: Yati.
// Runs before every page change (hooked up in router/index.js, so no one needs to edit that file).
// Return true to allow the page, or a route like { name: 'login' } to redirect.
import { isLoggedIn } from '../services/auth'

export function authGuard(to) {
  if (to.meta.requiresAuth && !isLoggedIn()) {
    // Not enforced yet, so nobody is blocked while building their pages.
    // TODO (Yati): replace the line below with:
    //   return { name: 'login', query: { redirect: to.fullPath } }
    console.info(`[guard] ${to.path} needs login (not enforced yet)`)
  }
  return true
}
