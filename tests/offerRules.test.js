// Unit tests for the Make-an-Offer & Negotiation rules (Lynn's individual feature).
// Run: node --test "tests/*.test.js"
import test from 'node:test'
import assert from 'node:assert/strict'
import {
  describeEvent,
  formatRM,
  gapPercent,
  isWithinRange,
  minimumOffer,
  quickOffers,
  suggestedCounter,
  summarise,
  testDriveDateRange,
  validateCounter,
  validateFirstOffer,
  validateTestDrive
} from '../src/services/offerRules.js'

const offer = (actor, action, amount) => ({ actor, action, amount })

test('formats ringgit amounts', () => {
  assert.equal(formatRM(46000), 'RM 46,000')
  assert.equal(formatRM('1234.4'), 'RM 1,234')
})

test('gap is a percentage of the seller price', () => {
  assert.equal(gapPercent(42000, 46000), 8.7)
  assert.equal(gapPercent(38000, 49000), 22.4)
  assert.equal(gapPercent(50000, 49000), 0) // never negative
  assert.equal(gapPercent(1000, 0), null)
})

test('test drive unlocks at 10% or less', () => {
  assert.equal(isWithinRange(10), true)
  assert.equal(isWithinRange(9.9), true)
  assert.equal(isWithinRange(10.1), false)
  assert.equal(isWithinRange(null), false)
})

test('summary uses the asking price until the seller counters', () => {
  const s = summarise([offer('buyer', 'offer', 38000)], 49000)
  assert.deepEqual(s, {
    buyerAmount: 38000,
    sellerAmount: 49000,
    gapPercent: 22.4,
    withinRange: false,
    turn: 'seller'
  })
})

test('summary follows counter-offers and whose turn it is', () => {
  const events = [offer('buyer', 'offer', 42000), offer('seller', 'counter', 46000)]
  const s = summarise(events, 49000)
  assert.equal(s.buyerAmount, 42000)
  assert.equal(s.sellerAmount, 46000)
  assert.equal(s.withinRange, true)
  assert.equal(s.turn, 'buyer')
  assert.equal(summarise(events, 49000, 'accepted').turn, null) // ended: nobody's turn
})

test('first offer must be 50% to 100% of the asking price, in whole ringgit', () => {
  assert.equal(minimumOffer(49000), 24500)
  assert.match(validateFirstOffer(24000, 49000), /at least RM 24,500/)
  assert.match(validateFirstOffer(50000, 49000), /more than the asking price/)
  assert.match(validateFirstOffer(40000.5, 49000), /whole amount/)
  assert.match(validateFirstOffer('', 49000), /whole amount/)
  assert.equal(validateFirstOffer(24500, 49000), '')
  assert.equal(validateFirstOffer(49000, 49000), '')
})

test('a buyer can only go up, and must stay under the seller price', () => {
  const s = { buyerAmount: 42000, sellerAmount: 46000 }
  assert.match(validateCounter('buyer', 42000, s), /higher than your last offer/)
  assert.match(validateCounter('buyer', 46000, s), /accept their price/)
  assert.equal(validateCounter('buyer', 44000, s), '')
})

test('a seller can only come down, and must stay above the buyer offer', () => {
  const s = { buyerAmount: 42000, sellerAmount: 46000 }
  assert.match(validateCounter('seller', 46000, s), /lower than your last price/)
  assert.match(validateCounter('seller', 42000, s), /accept their offer/)
  assert.equal(validateCounter('seller', 45000, s), '')
})

test('suggested counter sits between the two prices', () => {
  const s = { buyerAmount: 42000, sellerAmount: 46000 }
  assert.equal(suggestedCounter('buyer', s), 44000)
  const close = { buyerAmount: 45950, sellerAmount: 46000 }
  const value = suggestedCounter('seller', close)
  assert.ok(value > 45950 && value < 46000)
})

test('quick offers are below the asking price and above the minimum', () => {
  const quick = quickOffers(49000)
  assert.deepEqual(
    quick.map((q) => q.amount),
    [46600, 44100, 41700]
  )
})

test('test drive dates run from tomorrow to 30 days ahead', () => {
  const today = new Date(2026, 10, 13) // 13 Nov 2026
  assert.deepEqual(testDriveDateRange(today), { min: '2026-11-14', max: '2026-12-13' })
  assert.match(validateTestDrive({ date: '2026-11-13', time: '10:00' }, today), /from tomorrow/)
  assert.match(validateTestDrive({ date: '2026-12-14', time: '10:00' }, today), /from tomorrow/)
  assert.match(validateTestDrive({ date: '2026-11-20', time: '08:00' }, today), /between 09:00/)
  assert.match(validateTestDrive({ date: '', time: '10:00' }, today), /Choose a date/)
  assert.match(
    validateTestDrive({ date: '2026-11-20', time: '10:00', note: 'x'.repeat(301) }, today),
    /300 characters/
  )
  assert.equal(validateTestDrive({ date: '2026-11-20', time: '17:00' }, today), '')
})

test('history lines are written from the viewer side', () => {
  const event = offer('seller', 'counter', 46000)
  assert.equal(describeEvent(event, 'seller'), 'You countered with RM 46,000')
  assert.equal(describeEvent(event, 'buyer'), 'Seller countered with RM 46,000')
  assert.equal(
    describeEvent(offer('buyer', 'accept', 46000), 'seller'),
    'Buyer accepted RM 46,000 - deal agreed'
  )
  assert.equal(describeEvent({ actor: 'system', action: 'close' }, 'buyer'), 'Negotiation closed')
})
