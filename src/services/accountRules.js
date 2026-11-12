// Validation rules for the account forms (Register, Profile). OWNER: Yati.
// They match api/accounts_common.php, so the browser and the server agree.
// Each function returns an error message, or '' when the value is fine.

const NAME_PATTERN = /^[\p{L} .'-]+$/u
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/
const PHONE_PATTERN = /^\+?[0-9][0-9 -]{6,18}$/

export function fullNameError(value) {
  const name = String(value ?? '').trim()
  if (!name) return 'Full name is required.'
  if (name.length < 2 || name.length > 100) return 'Full name must be 2 to 100 characters.'
  if (!NAME_PATTERN.test(name)) {
    return 'Full name can only contain letters, spaces, apostrophes, dots and hyphens.'
  }
  return ''
}

export function emailError(value) {
  const email = String(value ?? '').trim()
  if (!email) return 'Email is required.'
  if (email.length > 150 || !EMAIL_PATTERN.test(email)) return 'Enter a valid email address.'
  return ''
}

/** Phone is optional. */
export function phoneError(value) {
  const phone = String(value ?? '').trim()
  if (!phone) return ''
  if (!PHONE_PATTERN.test(phone))
    return 'Enter a valid phone number (digits, spaces, + and - only).'
  return ''
}

export function passwordError(value) {
  const password = String(value ?? '')
  if (!password) return 'Password is required.'
  if (password.length < 8 || password.length > 72) return 'Password must be 8 to 72 characters.'
  if (!/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) {
    return 'Password must include at least one letter and one number.'
  }
  return ''
}

/**
 * A rough 0-4 strength score for the password meter.
 * Length and mixing letters, numbers and symbols all add points.
 */
export function passwordStrength(value) {
  const password = String(value ?? '')
  if (!password) return 0
  let score = 0
  if (password.length >= 8) score++
  if (password.length >= 12) score++
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++
  if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) score++
  return Math.min(score, 4)
}

/**
 * Where to go after logging in: the ?redirect= page the guard saved, but only if it is
 * a page inside this app ("/profile"), never an outside link ("//evil.com").
 */
export function safeRedirect(value) {
  const path = typeof value === 'string' ? value : ''
  return path.startsWith('/') && !path.startsWith('//') ? path : '/'
}

/** Turn a server error message into the field it belongs to (the API joins field errors). */
export function fieldForServerError(message) {
  const text = String(message || '').toLowerCase()
  if (text.includes('current password')) return 'current_password'
  if (text.includes('email')) return 'email'
  if (text.includes('phone')) return 'phone'
  if (text.includes('full name')) return 'full_name'
  if (text.includes('password')) return 'password'
  return ''
}
