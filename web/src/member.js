// What a signed-in member should do today, built from their saved plan (store.nutrition / store.calc).
import { store, ymd, logOf } from './stores/app.js'
import { dietBySlug, dietDay, BALANCED, CYCLE_LABEL } from './diets/data.js'
import { starterDay } from './nutrition.js'

const BAL_ICON = { b: '🍳', l: '🍗', s: '🍎', d: '🥗' }
export const WATER_GOAL = 8 // cups ≈ 2 liters

/** Day 1..30 of the member's eating plan, counted from the day they picked it. */
export function planDayNo(date = new Date()) {
  const since = store.nutrition?.since
  if (!since) return 1
  const [y, m, d] = since.split('-').map(Number)
  const diff = Math.floor((new Date(date.getFullYear(), date.getMonth(), date.getDate()) - new Date(y, m - 1, d)) / 864e5)
  return (((Math.max(0, diff)) % 30) + 30) % 30 + 1
}

/** Today's meals for the member: their diet's day, portions scaled to their calories. */
export function todayMeals(date = new Date()) {
  const c = store.calc
  if (!c?.kcal) return null
  const diet = dietBySlug(c.input?.diet)
  const n = planDayNo(date)
  if (!diet) {
    return {
      diet: BALANCED, n, noKcal: false, kcal: c.kcal, type: null,
      meals: starterDay(c.kcal).map((m) => ({ icon: BAL_ICON[m.id], label: m.name, n: m.items.join(' · '), kcal: m.kcal })),
    }
  }
  const day = dietDay(diet, n)
  const f = diet.noKcal || !day.kcal ? 1 : c.kcal / day.kcal
  return {
    diet, n, noKcal: !!diet.noKcal, kcal: diet.noKcal ? null : c.kcal,
    type: day.type ? (diet.cycleLabels || CYCLE_LABEL)[day.type] : null,
    scale: Math.round((f - 1) * 100),
    meals: day.meals.map((m) => ({ icon: m.icon, label: m.label, n: m.n, kcal: m.k && !diet.noKcal ? Math.round((m.k * f) / 10) * 10 : null })),
  }
}

/** Ticks for today: workout / meals / water. */
export function todayScore(meals = todayMeals()) {
  const log = logOf(ymd())
  const workout = store.workoutDates.includes(ymd())
  const mealsDone = meals ? log.meals.filter((i) => i < meals.meals.length).length : 0
  return { workout, mealsDone, mealsTotal: meals?.meals.length || 0, water: log.water }
}

/** Last N days, oldest first, with what happened on each. */
export function lastDays(n = 7) {
  const out = []
  for (let i = n - 1; i >= 0; i--) {
    const d = new Date()
    d.setDate(d.getDate() - i)
    const key = ymd(d)
    const l = logOf(key)
    out.push({ date: key, d, workout: store.workoutDates.includes(key), meals: l.meals.length, water: l.water })
  }
  return out
}

export const WD = ['الحد', 'الاتنين', 'التلات', 'الأربع', 'الخميس', 'الجمعة', 'السبت']
export const WD1 = ['ح', 'ن', 'ث', 'ر', 'خ', 'ج', 'س']
export const MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
export const fmtDay = (d = new Date()) => `${WD[d.getDay()]} ${d.getDate()} ${MONTHS[d.getMonth()]}`
export const daysAgo = (iso) => {
  if (!iso) return null
  const [y, m, d] = iso.slice(0, 10).split('-').map(Number)
  const t = new Date(); t.setHours(0, 0, 0, 0)
  return Math.round((t - new Date(y, m - 1, d)) / 864e5)
}
export const ago = (iso) => {
  const n = daysAgo(iso)
  return n === null ? '' : n === 0 ? 'النهارده' : n === 1 ? 'امبارح' : n === 2 ? 'من يومين' : n <= 10 ? `من ${n} أيام` : `من ${n} يوم`
}
