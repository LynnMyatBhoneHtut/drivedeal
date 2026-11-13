// API calls for the Make-an-Offer & Negotiation System (Lynn's individual feature).
// Every call that changes something returns the updated negotiation from the server.
import { apiGet, apiPost, apiPut } from './api'

/** { buyer_id } | { seller_id } | { listing_id, user_id } -> array of negotiations */
export async function fetchOffers(params) {
  const data = await apiGet('offers.php', params)
  return data.offers || []
}

/** Start a negotiation with a first offer. */
export async function startOffer({ listingId, buyerId, amount, message }) {
  const data = await apiPost('offers.php', {
    listing_id: listingId,
    buyer_id: buyerId,
    amount: Number(amount),
    message
  })
  return data.offer
}

/** action: 'counter' (with amount) | 'accept' | 'reject' | 'withdraw' */
export async function respondToOffer({ offerId, userId, action, amount, message }) {
  const body = { offer_id: offerId, user_id: userId, action, message }
  if (action === 'counter') body.amount = Number(amount)
  const data = await apiPut('offers.php', body)
  return data.offer
}

/** Buyer asks for a test drive. */
export async function requestTestDrive({ offerId, buyerId, date, time, note }) {
  const data = await apiPost('test_drives.php', {
    offer_id: offerId,
    buyer_id: buyerId,
    preferred_date: date,
    preferred_time: time,
    note
  })
  return data.offer
}

/** action: 'confirm' | 'decline' (seller) | 'cancel' (buyer) */
export async function updateTestDrive({ testDriveId, userId, action }) {
  const data = await apiPut('test_drives.php', {
    test_drive_id: testDriveId,
    user_id: userId,
    action
  })
  return data.offer
}
