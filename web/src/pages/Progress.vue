<script setup>
// "Am I on track?" — weight curve, last 5 weeks of activity, this week's adherence, tape measurements.
import { ref, computed, onMounted } from 'vue'
import MAppBar from '../mobile/MAppBar.vue'
import { MOBILE } from '../mobile/device.js'
import { store, logWeight, streak, coachApi, toast } from '../stores/app.js'
import { lastDays, todayMeals, WATER_GOAL, WD1, ago } from '../member.js'

/* stats */
const ws = computed(() => store.weights)
const last = computed(() => ws.value.at(-1))
const diff = computed(() => (ws.value.length > 1 ? +(ws.value.at(-1).kg - ws.value[0].kg).toFixed(1) : null))
const streakN = computed(() => streak())
const days35 = computed(() => lastDays(35))
const workouts30 = computed(() => days35.value.slice(-30).filter((d) => d.workout).length)

/* weight chart */
const W = MOBILE ? 340 : 600, H = MOBILE ? 190 : 220, P = { l: 34, r: 10, t: 12, b: 24 }
const chart = computed(() => {
  const pts = ws.value.slice(-30)
  if (pts.length < 2) return null
  const v = pts.map((p) => p.kg)
  const lo = Math.floor(Math.min(...v) - 1), hi = Math.ceil(Math.max(...v) + 1)
  const x = (i) => P.l + (i / (pts.length - 1)) * (W - P.l - P.r)
  const y = (kg) => P.t + (1 - (kg - lo) / (hi - lo)) * (H - P.t - P.b)
  const line = pts.map((p, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)} ${y(p.kg).toFixed(1)}`).join(' ')
  const ticks = [lo, (lo + hi) / 2, hi].map((k) => ({ k: +k.toFixed(1), y: y(k) }))
  return {
    line, area: `${line} L${x(pts.length - 1)} ${H - P.b} L${x(0)} ${H - P.b} Z`, ticks,
    dots: pts.map((p, i) => ({ x: x(i), y: y(p.kg), kg: p.kg, date: p.date })),
    first: pts[0].date.slice(5), lastD: pts.at(-1).date.slice(5),
  }
})
const kg = ref('')
async function save() {
  const v = Number(kg.value)
  if (!v || v < 30 || v > 300) return toast('اكتب وزنك بالكيلو', 'err')
  try { await logWeight(v); kg.value = ''; toast('اتسجّل ✓') } catch (e) { toast(e.message, 'err') }
}

/* calendar: 5 weeks, level = how many of (workout, meals, water) were done */
const cells = computed(() => days35.value.map((d) => ({ ...d, lv: (d.workout ? 1 : 0) + (d.meals ? 1 : 0) + (d.water >= WATER_GOAL / 2 ? 1 : 0) })))
const lead = computed(() => cells.value[0]?.d.getDay() ?? 0) // pad so columns line up with weekdays

/* this week */
const week = computed(() => lastDays(7))
const mealsPer = computed(() => todayMeals()?.meals.length || 4)
const adh = computed(() => ({
  workouts: week.value.filter((d) => d.workout).length,
  meals: Math.round((week.value.reduce((s, d) => s + Math.min(d.meals, mealsPer.value), 0) / (7 * mealsPer.value)) * 100),
  water: +(week.value.reduce((s, d) => s + d.water, 0) / 7 * 0.25).toFixed(1),
}))

/* tape measurements from check-ins */
const checkins = ref([])
onMounted(async () => {
  if (!store.coach.access) return
  try { checkins.value = (await coachApi.load()).checkins || [] } catch { /* optional */ }
})
const meas = computed(() => checkins.value.filter((c) => c.waist_cm || c.chest_cm || c.arm_cm || c.hips_cm))
</script>

<template>
  <div class="prog" :class="{ mob: MOBILE, 'm-screen': MOBILE, 'with-tabs': MOBILE }">
    <MAppBar v-if="MOBILE" title="تقدّمي" :back="false" />
    <div :class="MOBILE ? 'm-pad' : 'wrap'" class="in">
      <header v-if="!MOBILE" class="ph">
        <span class="kicker">تقدّمك</span>
        <h1>ماشي <span class="hl">صح؟</span></h1>
      </header>

      <div class="stats">
        <div class="st"><b class="num">{{ last?.kg ?? '—' }}</b><span>وزنك دلوقتي</span></div>
        <div class="st"><b class="num" :class="{ down: diff !== null && diff < 0 }">{{ diff === null ? '—' : (diff > 0 ? '+' : '') + diff }}</b><span>من البداية (كجم)</span></div>
        <div class="st"><b class="num">{{ streakN }}</b><span>يوم ورا بعض 🔥</span></div>
        <div class="st"><b class="num">{{ workouts30 }}</b><span>تمرين في 30 يوم</span></div>
      </div>

      <div class="cols">
        <section class="card">
          <div class="c-h"><h2>منحنى الوزن</h2><small v-if="last" class="muted">آخر تسجيل {{ ago(last.date) }}</small></div>
          <svg v-if="chart" :viewBox="`0 0 ${W} ${H}`" class="chart" role="img" :aria-label="`الوزن من ${ws[0].kg} لـ ${last.kg} كجم`">
            <defs><linearGradient id="wg" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#ff6a00" stop-opacity=".35" /><stop offset="1" stop-color="#ff6a00" stop-opacity="0" /></linearGradient></defs>
            <g v-for="t in chart.ticks" :key="t.k">
              <line :x1="P.l" :x2="W - P.r" :y1="t.y" :y2="t.y" class="grid" />
              <text :x="P.l - 6" :y="t.y + 4" text-anchor="end" class="ax">{{ t.k }}</text>
            </g>
            <path :d="chart.area" fill="url(#wg)" />
            <path :d="chart.line" class="ln" />
            <circle v-for="(d, i) in chart.dots" :key="i" :cx="d.x" :cy="d.y" :r="i === chart.dots.length - 1 ? 6 : 3.5" class="dt"><title>{{ d.date }}: {{ d.kg }} كجم</title></circle>
            <text :x="P.l" :y="H - 6" class="ax">{{ chart.first }}</text>
            <text :x="W - P.r" :y="H - 6" text-anchor="end" class="ax">{{ chart.lastD }}</text>
          </svg>
          <p v-else class="muted empty">سجّل وزنك مرتين على الأقل (مرة كل أسبوع) وهتشوف المنحنى هنا.</p>
          <form class="wf" @submit.prevent="save">
            <input v-model="kg" class="input ltr" type="number" step="0.1" inputmode="decimal" :placeholder="last?.kg ?? '80'" aria-label="وزنك النهارده" />
            <button class="btn btn-primary btn-sm">سجّل وزن النهارده</button>
          </form>
          <small class="muted">اوزن نفسك الصبح قبل الفطار ونفس اليوم كل أسبوع — الوزن بيتحرك يوم بيوم بسبب المية والأكل، المهم الاتجاه.</small>
        </section>

        <div class="side">
          <section class="card">
            <div class="c-h"><h2>الأسبوع ده</h2></div>
            <div class="adh">
              <div><b class="num">{{ adh.workouts }}</b><span>تمارين</span></div>
              <div><b class="num">{{ adh.meals }}%</b><span>من الوجبات</span></div>
              <div><b class="num">{{ adh.water }}</b><span>لتر مية/يوم</span></div>
            </div>
          </section>

          <section class="card">
            <div class="c-h"><h2>آخر 5 أسابيع</h2></div>
            <div class="cal">
              <small v-for="(w, i) in WD1" :key="'h' + i" class="wh">{{ w }}</small>
              <span v-for="n in lead" :key="'p' + n" class="cell pad"></span>
              <span v-for="c in cells" :key="c.date" class="cell" :class="'lv' + c.lv" :title="`${c.date}: ${c.workout ? 'تمرين · ' : ''}${c.meals} وجبات · ${c.water} كوبايات`">
                <span class="num">{{ c.d.getDate() }}</span>
              </span>
            </div>
            <div class="lg"><span>أقل</span><i class="cell lv0"></i><i class="cell lv1"></i><i class="cell lv2"></i><i class="cell lv3"></i><span>تمرين + أكل + مية</span></div>
          </section>
        </div>
      </div>

      <section v-if="meas.length" class="card">
        <div class="c-h"><h2>القياسات</h2><small class="muted">من متابعاتك مع الكوتش</small></div>
        <div class="tbl-w">
          <table class="tbl">
            <thead><tr><th>التاريخ</th><th>الوزن</th><th>الوسط</th><th>الصدر</th><th>الدراع</th><th>الأرداف</th></tr></thead>
            <tbody>
              <tr v-for="c in meas" :key="c.id">
                <td class="num">{{ c.date }}</td><td class="num">{{ c.weight_kg ?? '—' }}</td><td class="num">{{ c.waist_cm ?? '—' }}</td>
                <td class="num">{{ c.chest_cm ?? '—' }}</td><td class="num">{{ c.arm_cm ?? '—' }}</td><td class="num">{{ c.hips_cm ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      <RouterLink v-else to="/coach?tab=checkin" class="card hint">
        <b>📏 القياسات بتفرق أكتر من الميزان</b>
        <small class="muted">ابعت مقاس وسطك مع متابعة الأسبوع — ساعات الوزن بيقف والوسط بيقل.</small>
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.prog { padding-top: calc(var(--header) + 24px); padding-bottom: 40px; }
.prog.mob { padding-top: 0; }
.in { display: grid; gap: 14px; }
.ph h1 { font-size: clamp(2rem, 5vw, 3rem); line-height: 1.1; }
.stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
@media (min-width: 720px) { .stats { grid-template-columns: repeat(4, 1fr); gap: 12px; } }
.st { display: grid; padding: 14px; border-radius: 16px; background: #141414; border: 1px solid #222; }
.st b { font-size: 2.2rem; line-height: 1; justify-self: start; }
.st b.down { color: var(--ok); }
.st span { font-size: .74rem; font-weight: 800; color: var(--muted); }
.cols { display: grid; gap: 14px; }
.side { display: grid; gap: 14px; align-content: start; }
@media (min-width: 960px) { .cols { grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); align-items: start; } }
.card { display: grid; gap: 12px; padding: 16px; border-radius: 20px; background: #121212; border: 1px solid #232323; }
.c-h { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
.c-h h2 { font-size: 1.35rem; }
.chart { width: 100%; height: auto; direction: ltr; }
.grid { stroke: #232323; stroke-width: 1; }
.ax { fill: rgba(255,255,255,.5); font-family: var(--f-num); font-size: 13px; }
.ln { fill: none; stroke: var(--orange); stroke-width: 3; stroke-linejoin: round; stroke-linecap: round; }
.dt { fill: #0a0a0a; stroke: var(--orange); stroke-width: 2.5; }
.empty { padding: 30px 0; text-align: center; }
.wf { display: flex; gap: 8px; }
.wf .input { flex: 1; min-width: 0; min-height: 44px; }
.adh { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.adh div { display: grid; padding: 10px; border-radius: 14px; background: #181818; }
.adh b { font-size: 1.7rem; line-height: 1; color: var(--amber); justify-self: start; }
.adh span { font-size: .7rem; font-weight: 800; color: var(--muted); }
.cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
.wh { text-align: center; font-size: .7rem; font-weight: 800; color: var(--muted); }
.cell { aspect-ratio: 1; border-radius: 8px; display: grid; place-items: center; background: #1b1b1b; }
.cell .num { font-size: .85rem; color: rgba(255,255,255,.55); }
.cell.pad { background: none; }
.lv1 { background: rgba(255,106,0,.28); }
.lv2 { background: rgba(255,106,0,.55); }
.lv3 { background: var(--orange); }
.lv3 .num { color: #0a0a0a; }
.lg { display: flex; align-items: center; gap: 4px; font-size: .7rem; font-weight: 800; color: var(--muted); }
.lg .cell { width: 14px; height: 14px; aspect-ratio: auto; border-radius: 4px; }
.lg span:last-child { margin-inline-start: 6px; }
.tbl-w { overflow-x: auto; }
.tbl { width: 100%; border-collapse: collapse; font-size: .85rem; }
.tbl th { text-align: start; font-size: .74rem; color: var(--muted); padding: 6px 8px; border-bottom: 1px solid #262626; }
.tbl td { padding: 8px; border-bottom: 1px solid #1d1d1d; font-size: 1.05rem; }
.hint { gap: 4px; border-style: dashed; }
</style>
