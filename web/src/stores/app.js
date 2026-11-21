// App state backed by the Laravel API. Only guest-side bits (calculator input, guest workout
// progress) live in localStorage; everything about the account comes from /api/me.
import { reactive, watch } from 'vue'
import { api } from '../api.js'
import { programs, meta } from '../data.js'

const KEY = 'forma:v2'
const local = (() => { try { return JSON.parse(localStorage.getItem(KEY) || '{}') } catch { return {} } })()

export const store = reactive({
  ready: false,
  user: null,          // { name, phone, email, goal, is_admin, created_at }
  subscription: null,  // { plan, since, ends_at }
  unlocked: [],        // program slugs
  completed: {},       // { [programSlug]: number[] } (server)
  weights: [],         // { date, kg }
  orders: [],
  nutrition: null,     // saved plan on the account: { input, kcal, protein, carbs, fat, since }
  logs: [],            // last 35 days: { date, water, meals: number[] }
  workoutDates: [],    // 'YYYY-MM-DD' days with a finished workout
  coach: { access: false, unread: 0, last: null },
  calc: local.calc || null,                 // last calculator input + result (+ leadName)
  guestCompleted: local.guestCompleted || {}, // progress before signing up
  toast: null,
})

watch(() => [store.calc, store.guestCompleted], () => {
  try { localStorage.setItem(KEY, JSON.stringify({ calc: store.calc, guestCompleted: store.guestCompleted })) } catch { /* private mode */ }
}, { deep: true })

const planKey = (c) => (c?.kcal ? JSON.stringify([c.kcal, c.protein, c.carbs, c.fat, c.input?.diet, c.input?.weight, c.input?.goal]) : '')
const samePlan = (a, b) => planKey(a) === planKey(b)

// signed-in: whatever the calculator produces is saved on the account (debounced)
let nutTimer
watch(() => planKey(store.calc), (k) => {
  if (!store.user || !k || samePlan(store.calc, store.nutrition)) return
  clearTimeout(nutTimer)
  nutTimer = setTimeout(() => saveNutrition().catch(() => {}), 900)
})

let toastTimer
export function toast(msg, kind = 'ok') {
  store.toast = { msg, kind }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => (store.toast = null), 3200)
}

function apply(s) {
  store.user = s.user
  store.subscription = s.subscription ?? null
  store.unlocked = s.unlocked ?? []
  store.completed = Array.isArray(s.completed) ? {} : (s.completed ?? {})
  store.weights = s.weights ?? []
  store.orders = s.orders ?? []
  store.nutrition = s.nutrition ?? null
  store.logs = s.logs ?? []
  store.workoutDates = s.workout_dates ?? []
  store.coach = s.coach ?? { access: false, unread: 0, last: null }
  // the account's plan wins over this browser's calculator copy (other device, coach edits…)
  if (store.nutrition && !samePlan(store.nutrition, store.calc)) {
    const { input, kcal, protein, carbs, fat } = store.nutrition
    store.calc = { ...store.calc, input, kcal, protein, carbs, fat, weight: input.weight }
  }
}

export async function loadMe() {
  const localCalc = store.calc
  try { apply(await api('/me')) } catch { apply({ user: null }) }
  // members from before plans lived on the account: move this browser's calculator result up
  if (store.user && !store.nutrition && localCalc?.kcal) { store.calc = localCalc; saveNutrition().catch(() => {}) }
  store.ready = true
}

/* ---------- auth ---------- */
async function afterAuth(state) {
  const localCalc = store.calc
  apply(state)
  // first login after using the calculator as a guest → keep that plan on the account
  if (!store.nutrition && localCalc?.kcal) { store.calc = localCalc; saveNutrition().catch(() => {}) }
  if (Object.keys(store.guestCompleted).length) {
    try { apply(await api('/progress/import', { method: 'POST', body: { completed: store.guestCompleted } })); store.guestCompleted = {} } catch { /* keep local copy */ }
  }
}
export async function register(form) {
  await afterAuth(await api('/auth/register', { method: 'POST', body: form }))
}
export async function login(form) {
  await afterAuth(await api('/auth/login', { method: 'POST', body: form }))
}
export async function logout() {
  await api('/auth/logout', { method: 'POST' })
  apply({ user: null })
}

/* ---------- orders ---------- */
export async function createOrder({ plan, method, payerPhone, receipt }) {
  const form = new FormData()
  form.append('plan', plan)
  form.append('method', method)
  if (payerPhone) form.append('payer_phone', payerPhone)
  form.append('receipt', receipt)
  const res = await api('/orders', { method: 'POST', form })
  store.orders.unshift(res.order)
  return res.order
}
export async function fetchOrder(ref) {
  const { data } = await api(`/orders/${encodeURIComponent(ref)}`)
  const i = store.orders.findIndex((o) => o.id === ref)
  i >= 0 ? (store.orders[i] = data) : store.orders.unshift(data)
  return data
}

/* ---------- access & progress ---------- */
export const hasAccess = (programId) => store.unlocked.includes(programId)

export const doneDays = (programId) =>
  (store.user ? store.completed[programId] : store.guestCompleted[programId]) || []

export async function completeDay(programId, n) {
  if (!store.user) {
    const arr = store.guestCompleted[programId] || (store.guestCompleted[programId] = [])
    if (!arr.includes(n)) arr.push(n)
    return
  }
  apply(await api(`/progress/${programId}/${n}`, { method: 'POST' }))
}

export async function logWeight(kg) {
  apply(await api('/weights', { method: 'POST', body: { kg } }))
}

/* ---------- daily tracking ---------- */
export const ymd = (d = new Date()) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
export const logOf = (date = ymd()) => store.logs.find((l) => l.date === date) || { date, water: 0, meals: [] }

export async function saveNutrition() {
  const c = store.calc
  if (!store.user || !c?.kcal || !c.input) return
  const { input, kcal, protein, carbs, fat } = c
  apply(await api('/me/nutrition', { method: 'PUT', body: { input, kcal, protein, carbs, fat } }))
}

// optimistic: update the day locally, then sync
async function patchLog(date, patch) {
  const cur = logOf(date)
  const next = { ...cur, ...patch }
  const i = store.logs.findIndex((l) => l.date === date)
  i >= 0 ? (store.logs[i] = next) : store.logs.push(next)
  try { apply(await api(`/logs/${date}`, { method: 'PUT', body: patch })) } catch (e) {
    i >= 0 ? (store.logs[i] = cur) : store.logs.splice(store.logs.indexOf(next), 1)
    toast(e.message, 'err')
  }
}
export const setWater = (n, date = ymd()) => patchLog(date, { water: Math.max(0, Math.min(16, n)) })
export function toggleMeal(i, date = ymd()) {
  const m = logOf(date).meals
  return patchLog(date, { meals: m.includes(i) ? m.filter((x) => x !== i) : [...m, i].sort() })
}

/** A day counts when anything was done: workout, a meal ticked or water logged. */
export const activeDay = (date) => store.workoutDates.includes(date) || (() => { const l = logOf(date); return l.water > 0 || l.meals.length > 0 })()
export function streak() {
  const d = new Date()
  if (!activeDay(ymd(d))) d.setDate(d.getDate() - 1) // today not started yet doesn't break it
  let n = 0
  while (activeDay(ymd(d)) && n < 400) { n++; d.setDate(d.getDate() - 1) }
  return n
}

/* ---------- coach ---------- */
export const coachApi = {
  load: () => api('/coach'),
  send: (body) => api('/coach/messages', { method: 'POST', body: { body } }),
  checkin: (data) => api('/coach/checkins', { method: 'POST', body: data }),
}

/* ---------- leads ---------- */
export async function saveLead(lead) {
  await api('/leads', { method: 'POST', body: lead })
  store.calc = { ...store.calc, leadName: lead.name, leadPhone: lead.phone }
}

/* ---------- app shell (mobile) ---------- */

// header title for inner pages; router resets it, pages may override (e.g. program name)
export const ui = reactive({ title: '' })

/** The workout to suggest right now: first unfinished day the user can open. */
export function nextWorkout() {
  const open = (p, d) => hasAccess(p.id) || d <= meta.freeDays
  const firstOpen = (p) => {
    const done = doneDays(p.id)
    for (let d = 1; d <= p.days; d++) if (!done.includes(d)) return open(p, d) ? d : null
    return null
  }
  const active = programs.filter((p) => hasAccess(p.id) || doneDays(p.id).length)
  for (const p of active) {
    const d = firstOpen(p)
    if (d) return { program: p, day: d, started: doneDays(p.id).length > 0 }
  }
  const p = programs[0]
  return p ? { program: p, day: firstOpen(p) || 1, started: false } : null
}

export function greeting() {
  const h = new Date().getHours()
  return h < 12 ? 'صباح الخير' : h < 18 ? 'نهارك سعيد' : 'مساء الخير'
}
