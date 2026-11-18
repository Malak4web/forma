// App state backed by the Laravel API. Only guest-side bits (calculator input, guest workout
// progress) live in localStorage; everything about the account comes from /api/me.
import { reactive, watch } from 'vue'
import { api } from '../api.js'

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
  calc: local.calc || null,                 // last calculator input + result (+ leadName)
  guestCompleted: local.guestCompleted || {}, // progress before signing up
  toast: null,
})

watch(() => [store.calc, store.guestCompleted], () => {
  try { localStorage.setItem(KEY, JSON.stringify({ calc: store.calc, guestCompleted: store.guestCompleted })) } catch { /* private mode */ }
}, { deep: true })

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
}

export async function loadMe() {
  try { apply(await api('/me')) } catch { apply({ user: null }) }
  store.ready = true
}

/* ---------- auth ---------- */
async function afterAuth(state) {
  apply(state)
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

/* ---------- leads ---------- */
export async function saveLead(lead) {
  await api('/leads', { method: 'POST', body: lead })
  store.calc = { ...store.calc, leadName: lead.name, leadPhone: lead.phone }
}
