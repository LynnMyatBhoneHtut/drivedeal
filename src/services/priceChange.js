// Price maths for the watchlist (Yati's D/HD feature). OWNER: Yati.
// Plain functions with no Vue in them, so they are easy to test (tests/priceChange.test.js)
// and the pages can wrap them in computed properties.

/** "RM 105,000" - whole ringgit, or 2 decimals when there are sen. */
export function formatRM(amount) {
  const value = Number(amount) || 0
  const hasSen = Math.round(value * 100) % 100 !== 0
  return (
    'RM ' +
    value.toLocaleString('en-MY', {
      minimumFractionDigits: hasSen ? 2 : 0,
      maximumFractionDigits: 2
    })
  )
}

/**
 * How a price moved since it was saved.
 * priceChange(48000, 45500) -> { amount: 2500, percent: 5.2, direction: 'down' }
 * amount is always positive; direction says which way it went.
 */
export function priceChange(before, now) {
  const oldPrice = Number(before) || 0
  const newPrice = Number(now) || 0
  const diff = Math.round((oldPrice - newPrice) * 100) / 100
  let direction = 'same'
  if (diff > 0) direction = 'down'
  if (diff < 0) direction = 'up'
  const percent = oldPrice > 0 ? Math.round((Math.abs(diff) / oldPrice) * 1000) / 10 : 0
  return { amount: Math.abs(diff), percent, direction }
}

/**
 * How far the price has travelled from the saved price towards the target, 0-100.
 * Saved RM 50,000, target RM 45,000, now RM 47,500 -> 50 (halfway there).
 */
export function targetProgress(savedPrice, currentPrice, targetPrice) {
  const saved = Number(savedPrice)
  const now = Number(currentPrice)
  const target = Number(targetPrice)
  if (!targetPrice || !(saved > target)) return 0
  if (now <= target) return 100
  const progress = ((saved - now) / (saved - target)) * 100
  return Math.max(0, Math.min(100, Math.round(progress)))
}

/** "just now", "5 min ago", "3 h ago", "2 days ago" from a MySQL "YYYY-MM-DD HH:MM:SS" time. */
export function timeAgo(mysqlDate, now = Date.now()) {
  const then = new Date(String(mysqlDate).replace(' ', 'T')).getTime()
  if (Number.isNaN(then)) return ''
  const seconds = Math.max(0, Math.round((now - then) / 1000))
  if (seconds < 60) return 'just now'
  const minutes = Math.round(seconds / 60)
  if (minutes < 60) return `${minutes} min ago`
  const hours = Math.round(minutes / 60)
  if (hours < 24) return `${hours} h ago`
  const days = Math.round(hours / 24)
  return days === 1 ? '1 day ago' : `${days} days ago`
}

/** Check a target price typed by the user. '' if fine (or empty = no target). */
export function targetPriceError(value, currentPrice) {
  if (value === '' || value === null || value === undefined) return ''
  const target = Number(value)
  if (!Number.isFinite(target) || target <= 0) return 'Enter a price above 0, or leave it empty.'
  if (target >= Number(currentPrice)) {
    return `Must be lower than the current price (${formatRM(currentPrice)}).`
  }
  return ''
}
