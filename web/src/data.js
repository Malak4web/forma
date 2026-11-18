// Content comes from the Laravel API (/api/content) — loaded once before the app mounts.
// The arrays are reactive and filled in place, so `import { videos } from './data.js'` stays valid.
import { reactive } from 'vue'
import { api } from './api.js'

export const videos = reactive([])
export const programs = reactive([])
export const plans = reactive([])
export const transformations = reactive([])
export const payMethods = reactive([])
export const meta = reactive({ freeDays: 3, loaded: false })

export async function loadContent() {
  const c = await api('/content')
  videos.splice(0, videos.length, ...c.exercises)
  programs.splice(0, programs.length, ...c.programs)
  plans.splice(0, plans.length, ...c.plans)
  transformations.splice(0, transformations.length, ...(c.transformations || []))
  payMethods.splice(0, payMethods.length, ...c.payment_methods)
  meta.freeDays = c.free_days
  meta.loaded = true
}

export const videoById = (id) => videos.find((v) => v.id === id)
export const programById = (id) => programs.find((p) => p.id === id)
export const planById = (id) => plans.find((p) => p.id === id)
export const methodById = (id) => payMethods.find((m) => m.id === id)

export const categories = [
  { id: 'all', label: 'الكل' },
  { id: 'belly', label: 'الكرش والبطن' },
  { id: 'legs', label: 'رجل وأرداف' },
  { id: 'upper', label: 'صدر وكتف ودراع' },
  { id: 'cardio', label: 'كارديو' },
  { id: 'pain', label: 'وجع وإطالة' },
  { id: 'food', label: 'الأكلة دي محتاجة كام؟' },
  { id: 'body', label: 'جوه جسمك' },
]

// Outline only (program page). The workout itself is fetched from /api/programs/:id/days/:n,
// which enforces the paywall. Keep in sync with app/Support/ProgramDays.php.
const LOAD = [[30, 30], [40, 20], [45, 15], [50, 10], [50, 10]]
export function programDay(program, n) {
  const w = Math.min(Math.ceil(n / 7), program.weeks.length) - 1
  const week = program.weeks[w]
  if (n % 7 === 0) return { n, week: w + 1, rest: true, title: 'راحة نشيطة', items: [videoById('toetouch')].filter(Boolean), rounds: 2 }
  const pool = week.pool
  const start = ((n - 1) * 2) % pool.length
  const [work, rest] = LOAD[Math.min(w, LOAD.length - 1)]
  const items = [0, 1, 2].map((k) => videoById(pool[(start + k) % pool.length])).filter(Boolean).map((v) => ({ ...v, work, rest }))
  return { n, week: w + 1, rest: false, title: week.t, items, rounds: 3 }
}

export const steps = [
  { n: '01', title: 'قولنا إنت مين', body: 'سنّك ووزنك وطولك وهدفك — تخسيس، تنشيف، ولا عضل.' },
  { n: '02', title: 'خد نظامك', body: 'نظام أكل مبدئي بسعرات تقريبية + برنامج تمرين يناسبك.' },
  { n: '03', title: 'اتمرن مع الكوتش', body: 'فيديو قصير لكل تمرين، المؤقّت شغّال، وعلّم على اليوم لما تخلّصه.' },
]

export const faqs = [
  { q: 'محتاج أدوات أو جيم؟', a: 'لأ. البرامج من البيت بوزن جسمك بس.' },
  { q: 'نظام الأكل ببلاش بجد؟', a: 'آه. بتكتب بياناتك وبتاخد نظام مبدئي فورًا. السعرات فيه تقريبية مش حسابات دكتور.' },
  { q: 'أنا مبتدئ خالص، ينفع؟', a: 'ينفع. أول أسبوع خفيف، وكل تمرين ليه نسخة أسهل.' },
  { q: 'بدفع إزاي؟', a: 'فودافون كاش أو InstaPay. بتحوّل وترفع صورة الإيصال، وبنفعّل اشتراكك بعد ما نراجعه.' },
  { q: 'عندي وجع في الضهر أو الركبة؟', a: 'فيه تمارين خفيفة ممكن تساعد، بس لو الوجع شديد أو مستمر اكشف عند دكتور الأول.' },
]

export const fmt = (s) => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`
export const egp = (n) => Number(n).toLocaleString('en-US')
