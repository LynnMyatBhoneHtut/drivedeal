// Make-an-Offer & Negotiation rules (Lynn's individual feature).
// The same rules are enforced on the server in api/offers_common.php; these copies let the
// page explain a problem before sending anything. Pure functions, unit-tested in
// tests/offerRules.test.js (node --test).

export const MIN_OFFER_RATIO = 0.5 // first offer: at least 50% of the asking price
export const TEST_DRIVE_RANGE = 10 // percent: test drive unlocks within 10% of the seller's price
export const TEST_DRIVE_MAX_DAYS = 30
export const MESSAGE_MAX = 500
export const NOTE_MAX = 300
export const TIME_SLOTS = [
  '09:00',
  '10:00',
  '11:00',
  '12:00',
  '13:00',
  '14:00',
  '15:00',
  '16:00',
  '17:00'
]

/** 46000 -> "RM 46,000" */
export function formatRM(amount) {
  return `RM ${Math.round(Number(amount) || 0).toLocaleString('en-MY')}`
}

/** The smallest first offer allowed for an asking price. */
export function minimumOffer(askingPrice) {
  return Math.ceil(Number(askingPrice) * MIN_OFFER_RATIO)
}

/** How far apart two prices are, as a % of the seller's price (one decimal place). */
export function gapPercent(buyerAmount, sellerAmount) {
  const buyer = Number(buyerAmount)
  const seller = Number(sellerAmount)
  if (!Number.isFinite(buyer) || !Number.isFinite(seller) || seller <= 0) return null
  return Math.round((Math.max(0, seller - buyer) / seller) * 1000) / 10
}

/** True when the gap is small enough to unlock a test drive. */
export function isWithinRange(gap) {
  return gap !== null && gap !== undefined && gap <= TEST_DRIVE_RANGE
}

/**
 * Where a negotiation stands, from its events (oldest first) - same result as the server.
 * -> { buyerAmount, sellerAmount, gapPercent, withinRange, turn }
 */
export function summarise(events, askingPrice, status = 'open') {
  let buyerAmount = null
  let sellerAmount = null
  let lastMover = null
  for (const event of events || []) {
    if (event.action === 'offer' || event.action === 'counter') {
      if (event.actor === 'buyer') buyerAmount = Number(event.amount)
      if (event.actor === 'seller') sellerAmount = Number(event.amount)
      lastMover = event.actor
    }
  }
  if (sellerAmount === null) sellerAmount = Number(askingPrice)
  const gap = buyerAmount === null ? null : gapPercent(buyerAmount, sellerAmount)
  let turn = null
  if (status === 'open' && lastMover) turn = lastMover === 'buyer' ? 'seller' : 'buyer'
  return { buyerAmount, sellerAmount, gapPercent: gap, withinRange: isWithinRange(gap), turn }
}

function wholeAmount(value) {
  if (value === '' || value === null || value === undefined) return null
  const number = Number(value)
  return Number.isInteger(number) && number > 0 && number <= 10000000 ? number : null
}

/** '' when a first offer is fine, otherwise the message to show. */
export function validateFirstOffer(amount, askingPrice) {
  const value = wholeAmount(amount)
  if (value === null) return 'Enter your offer as a whole amount in RM.'
  const minimum = minimumOffer(askingPrice)
  if (value < minimum)
    return `Your offer must be at least ${formatRM(minimum)} (half the asking price).`
  if (value > Number(askingPrice)) {
    return `Your offer cannot be more than the asking price of ${formatRM(askingPrice)}.`
  }
  return ''
}

/**
 * '' when a counter-offer is fine, otherwise the message to show.
 * role: 'buyer' | 'seller'; summary: from summarise() (buyerAmount, sellerAmount).
 */
export function validateCounter(role, amount, summary) {
  const value = wholeAmount(amount)
  if (value === null) return 'Enter your counter-offer as a whole amount in RM.'
  const { buyerAmount, sellerAmount } = summary
  if (role === 'buyer') {
    if (value <= buyerAmount) {
      return `Your new offer must be higher than your last offer of ${formatRM(buyerAmount)}.`
    }
    if (value >= sellerAmount) {
      return `The seller is asking ${formatRM(sellerAmount)} - accept their price instead of offering the same or more.`
    }
  } else {
    if (value >= sellerAmount) {
      return `Your counter-offer must be lower than your last price of ${formatRM(sellerAmount)}.`
    }
    if (value <= buyerAmount) {
      return `The buyer offered ${formatRM(buyerAmount)} - accept their offer instead of asking the same or less.`
    }
  }
  return ''
}

/** The price a counter-offer box starts at: halfway between the two latest prices. */
export function suggestedCounter(role, summary) {
  const { buyerAmount, sellerAmount } = summary
  if (buyerAmount === null) return ''
  const middle = Math.round((buyerAmount + sellerAmount) / 2 / 100) * 100
  if (middle > buyerAmount && middle < sellerAmount) return middle
  return role === 'buyer' ? buyerAmount + 1 : sellerAmount - 1
}

/** Quick first-offer suggestions: 95%, 90% and 85% of the asking price, to the nearest RM 100. */
export function quickOffers(askingPrice) {
  const asking = Number(askingPrice)
  return [0.95, 0.9, 0.85]
    .map((ratio) => ({ ratio, amount: Math.round((asking * ratio) / 100) * 100 }))
    .filter(({ amount }) => amount >= minimumOffer(asking) && amount <= asking)
}

/** 'YYYY-MM-DD' in local time */
export function isoDate(date) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

/** First and last date a test drive can be booked: tomorrow to 30 days ahead. */
export function testDriveDateRange(today = new Date()) {
  const first = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1)
  const last = new Date(
    today.getFullYear(),
    today.getMonth(),
    today.getDate() + TEST_DRIVE_MAX_DAYS
  )
  return { min: isoDate(first), max: isoDate(last) }
}

/** '' when a test drive request is fine, otherwise the message to show. */
export function validateTestDrive({ date, time, note = '' }, today = new Date()) {
  const { min, max } = testDriveDateRange(today)
  if (!/^\d{4}-\d{2}-\d{2}$/.test(date || '')) return 'Choose a date for the test drive.'
  if (date < min || date > max) {
    return `Choose a date from tomorrow up to ${TEST_DRIVE_MAX_DAYS} days ahead.`
  }
  if (!TIME_SLOTS.includes(time)) return 'Choose a time between 09:00 and 17:00.'
  if (String(note).trim().length > NOTE_MAX) {
    return `Your note must be ${NOTE_MAX} characters or fewer.`
  }
  return ''
}

const STATUS_LABELS = {
  open: 'Negotiating',
  accepted: 'Deal agreed',
  rejected: 'Rejected',
  withdrawn: 'Withdrawn',
  closed: 'Closed'
}

/** Human label for a negotiation status. */
export function statusLabel(status) {
  return STATUS_LABELS[status] || status
}

/** One history line, from the point of view of `viewer` ('buyer' | 'seller'). */
export function describeEvent(event, viewer) {
  const mine = event.actor === viewer
  const who =
    event.actor === 'system'
      ? 'DriveDeal'
      : mine
        ? 'You'
        : event.actor === 'buyer'
          ? 'Buyer'
          : 'Seller'
  const amount = event.amount !== null && event.amount !== undefined ? formatRM(event.amount) : ''
  switch (event.action) {
    case 'offer':
      return `${who} offered ${amount}`
    case 'counter':
      return `${who} countered with ${amount}`
    case 'accept':
      return `${who} accepted ${amount} - deal agreed`
    case 'reject':
      return `${who} rejected the offer`
    case 'withdraw':
      return `${who} withdrew the offer`
    case 'close':
      return 'Negotiation closed'
    default:
      return `${who}: ${event.action}`
  }
}
