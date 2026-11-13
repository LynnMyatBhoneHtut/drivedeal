// Tests for the watchlist price maths (Yati's D/HD feature). Run: node --test tests/
import test from 'node:test'
import assert from 'node:assert/strict'
import {
  formatRM,
  priceChange,
  targetPriceError,
  targetProgress,
  timeAgo
} from '../src/services/priceChange.js'

test('priceChange works out a drop in RM and %', () => {
  assert.deepEqual(priceChange(48000, 45500), { amount: 2500, percent: 5.2, direction: 'down' })
})

test('priceChange reports rises and no change', () => {
  assert.equal(priceChange(45500, 47000).direction, 'up')
  assert.equal(priceChange(47000, 47000).direction, 'same')
  assert.equal(priceChange(0, 100).percent, 0) // no divide by zero
})

test('formatRM shows sen only when there are any', () => {
  assert.equal(formatRM(105000), 'RM 105,000')
  assert.equal(formatRM(99.5), 'RM 99.50')
})

test('targetProgress goes from 0 to 100 as the price falls to the target', () => {
  assert.equal(targetProgress(50000, 50000, 45000), 0)
  assert.equal(targetProgress(50000, 47500, 45000), 50)
  assert.equal(targetProgress(50000, 44000, 45000), 100)
  assert.equal(targetProgress(50000, 52000, 45000), 0) // price went up
  assert.equal(targetProgress(50000, 47000, null), 0) // no target
})

test('targetPriceError allows empty, rejects bad or too-high targets', () => {
  assert.equal(targetPriceError('', 48000), '')
  assert.equal(targetPriceError(45000, 48000), '')
  assert.match(targetPriceError(-5, 48000), /above 0/)
  assert.match(targetPriceError(48000, 48000), /lower than the current price/)
})

test('timeAgo gives short relative times', () => {
  const now = new Date('2026-11-13T12:00:00').getTime()
  assert.equal(timeAgo('2026-11-13 11:59:30', now), 'just now')
  assert.equal(timeAgo('2026-11-13 11:55:00', now), '5 min ago')
  assert.equal(timeAgo('2026-11-13 09:00:00', now), '3 h ago')
  assert.equal(timeAgo('2026-11-11 12:00:00', now), '2 days ago')
})
