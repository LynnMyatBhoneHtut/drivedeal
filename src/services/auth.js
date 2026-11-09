// The logged-in user. Shared file: don't change it without telling the group.
//
// Stored in localStorage under the key "user", always this shape:
//   { "user_id": 5, "full_name": "Sarah Lim", "email": "sarah@example.com", "role": "user" }
//
// currentUser is reactive: anything that shows it (Navbar, v-if on pages)
// updates by itself after setUser() or logout().
//
// Fake login until Yati's login page is merged - paste in the browser console, then refresh:
//   localStorage.setItem('user', JSON.stringify({user_id:2, full_name:'Ahmad Faizal', email:'ahmad@example.com', role:'user'}))
import { ref } from 'vue'

const KEY = 'user'

function readStoredUser() {
  try {
    return JSON.parse(localStorage.getItem(KEY))
  } catch {
    return null // missing or broken value -> treat as logged out
  }
}

export const currentUser = ref(readStoredUser())

/** The logged-in user object, or null for a guest. */
export function getUser() {
  return currentUser.value
}

/** Save the user returned by login.php / register.php. */
export function setUser(user) {
  localStorage.setItem(KEY, JSON.stringify(user))
  currentUser.value = user
}

/** Forget the user. */
export function logout() {
  localStorage.removeItem(KEY)
  currentUser.value = null
}

/** true when someone is logged in. */
export function isLoggedIn() {
  return currentUser.value !== null
}
