<script setup>
import { ref, reactive, computed, watch, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import NotFound from './NotFound.vue'
import MAppBar from '../mobile/MAppBar.vue'
import { MOBILE } from '../mobile/device.js'
import DietVisual from '../diets/DietVisual.vue'
import { DIETS, dietBySlug, dietDay, CYCLE_LABEL } from '../diets/data.js'
import { motifBg } from '../diets/motifs.js'
import { store } from '../stores/app.js'

const route = useRoute()
const d = computed(() => dietBySlug(route.params.slug))
watchEffect(() => { if (d.value) document.title = `نظام ${d.value.name} (${d.value.en}) — خطة 30 يوم | فورمة` })

const vars = computed(() => {
  const t = d.value.theme
  return {
    '--bg': t.bg, '--sf': t.surface, '--ink': t.ink, '--a': t.a, '--b': t.b,
    '--line': t.mode === 'light' ? 'rgba(0,0,0,.09)' : 'rgba(255,255,255,.1)',
    '--soft': t.mode === 'light' ? 'rgba(0,0,0,.55)' : 'rgba(255,255,255,.62)',
    '--motif': motifBg(t.motif, t.a, t.mode === 'light' ? 0.22 : 0.14),
  }
})

/* ───── 30-day plan: week tabs + 7-day row + selected day ───── */
const week = ref(1)
const day = ref(1)
const KEY = (s) => `forma:diet:${s}`
const saved = reactive({ done: [], shop: [] })
watch(d, (v) => {
  if (!v) return
  let s = {}
  try { s = JSON.parse(localStorage.getItem(KEY(v.slug)) || '{}') } catch { /* private mode */ }
  saved.done = s.done || []
  saved.shop = s.shop || []
  const first = Array.from({ length: 30 }, (_, i) => i + 1).find((n) => !saved.done.includes(n)) || 1
  day.value = first
  week.value = Math.ceil(first / 7)
}, { immediate: true })
watch(saved, () => { try { localStorage.setItem(KEY(d.value.slug), JSON.stringify(saved)) } catch { /* ignore */ } }, { deep: true })

const weeks = [1, 2, 3, 4, 5]
const weekDays = computed(() => Array.from({ length: week.value === 5 ? 2 : 7 }, (_, i) => (week.value - 1) * 7 + i + 1))
const plan = computed(() => (d.value ? dietDay(d.value, day.value) : null))
function pickWeek(w) { week.value = w; day.value = (w - 1) * 7 + 1 }
const toggleDone = () => {
  const i = saved.done.indexOf(day.value)
  i >= 0 ? saved.done.splice(i, 1) : saved.done.push(day.value)
}
const isDone = computed(() => saved.done.includes(day.value))
const nextDay = () => { if (day.value < 30) { day.value++; week.value = Math.ceil(day.value / 7) } }

// scale portions to the visitor's calculator result, if any
const target = computed(() => store.calc?.kcal || null)
const scale = computed(() => (target.value && plan.value ? Math.round(((target.value - plan.value.kcal) / plan.value.kcal) * 100) : null))

const tab = ref('eat')
const toggleShop = (item) => { const i = saved.shop.indexOf(item); i >= 0 ? saved.shop.splice(i, 1) : saved.shop.push(item) }
const others = computed(() => DIETS.filter((x) => x.slug !== d.value?.slug))
const scrollToPlan = () => document.getElementById('plan')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
const levelTxt = (n) => ['', 'سهل', 'متوسط', 'صعب', 'صعب جدًا'][n]
</script>

<template>
  <NotFound v-if="!d" />
  <article v-else class="diet" :class="[d.theme.mode, `vis-${d.visual}`, { mob: MOBILE }]" :style="vars">
    <MAppBar v-if="MOBILE" transparent fallback="/diets" />

    <!-- ───── HERO ───── -->
    <header class="hero">
      <div class="motif" aria-hidden="true"></div>
      <div class="wrap hero-in">
        <div class="copy">
          <RouterLink v-if="!MOBILE" to="/diets" class="crumb">أنظمة الأكل ‹</RouterLink>
          <span class="badge"><span aria-hidden="true">{{ d.emoji }}</span> خطة <span class="num">30</span> يوم</span>
          <span class="en">{{ d.en }}</span>
          <h1>نظام {{ d.name }}</h1>
          <p class="tag">{{ d.tagline }}</p>
          <div class="facts">
            <span>الصعوبة: <b>{{ levelTxt(d.levelN) }}</b></span>
            <span v-for="t in d.tags" :key="t">{{ t }}</span>
          </div>
          <button class="cta" @click="scrollToPlan">ابدأ اليوم <span class="num">{{ day }}</span> ←</button>
        </div>
        <div class="art">
          <div class="ring"><DietVisual :diet="d" :size="MOBILE ? 220 : 300" /></div>
        </div>
      </div>
    </header>

    <!-- ───── MACROS STRIP ───── -->
    <section class="wrap strip">
      <div class="mac"><b class="num">{{ d.macros.c }}%</b><span>كارب</span><i :style="{ width: d.macros.c + '%' }" class="ic"></i></div>
      <div class="mac"><b class="num">{{ d.macros.p }}%</b><span>بروتين</span><i :style="{ width: d.macros.p + '%' }" class="ip"></i></div>
      <div class="mac"><b class="num">{{ d.macros.f }}%</b><span>دهون</span><i :style="{ width: d.macros.f + '%' }" class="if"></i></div>
    </section>

    <!-- ───── HOW ───── -->
    <section class="wrap sec">
      <h2 class="h">بيشتغل <span class="acc">إزاي؟</span></h2>
      <ol class="how">
        <li v-for="(h, i) in d.how" :key="h.t"><span class="n num">0{{ i + 1 }}</span><b>{{ h.t }}</b><p>{{ h.d }}</p></li>
      </ol>
    </section>

    <!-- ───── EAT / AVOID / FOR WHO ───── -->
    <section class="wrap sec">
      <div class="seg" role="tablist">
        <button role="tab" :aria-selected="tab === 'eat'" :class="{ on: tab === 'eat' }" @click="tab = 'eat'">✓ كُل</button>
        <button role="tab" :aria-selected="tab === 'avoid'" :class="{ on: tab === 'avoid' }" @click="tab = 'avoid'">✕ ابعد عن</button>
        <button role="tab" :aria-selected="tab === 'who'" :class="{ on: tab === 'who' }" @click="tab = 'who'">مناسب لمين؟</button>
      </div>
      <div class="pane">
        <ul v-if="tab === 'eat'" class="tick yes"><li v-for="x in d.eat" :key="x">{{ x }}</li></ul>
        <ul v-else-if="tab === 'avoid'" class="tick no"><li v-for="x in d.avoid" :key="x">{{ x }}</li></ul>
        <div v-else class="who">
          <div><b>مناسب لـ</b><ul class="tick yes"><li v-for="x in d.goodFor" :key="x">{{ x }}</li></ul></div>
          <div><b>مش مناسب لـ</b><ul class="tick no"><li v-for="x in d.notFor" :key="x">{{ x }}</li></ul></div>
        </div>
      </div>
    </section>

    <!-- ───── 30-DAY PLAN ───── -->
    <section id="plan" class="wrap sec">
      <div class="plan-h">
        <h2 class="h">خطة الـ <span class="acc num">30</span> يوم</h2>
        <span class="prog"><span class="num">{{ saved.done.length }}/30</span> يوم خلّصته</span>
      </div>
      <div class="weeks" role="tablist">
        <button v-for="w in weeks" :key="w" role="tab" :aria-selected="week === w" :class="{ on: week === w }" @click="pickWeek(w)">
          {{ w === 5 ? 'آخر يومين' : `أسبوع ${w}` }}
        </button>
      </div>
      <div class="days">
        <button
          v-for="n in weekDays" :key="n" class="dd"
          :class="{ sel: day === n, done: saved.done.includes(n), [`t-${dietDay(d, n).type}`]: d.cycle }"
          :aria-label="`اليوم ${n}`" @click="day = n"
        ><span class="num">{{ n }}</span></button>
      </div>

      <Transition name="fade" mode="out-in">
        <div v-if="plan" :key="day" class="daycard">
          <header class="dc-h">
            <div>
              <b>اليوم <span class="num">{{ day }}</span></b>
              <small v-if="plan.type" class="ty">{{ CYCLE_LABEL[plan.type] }}</small>
            </div>
            <span class="kc">≈ <span class="num">{{ plan.kcal }}</span> سعرة</span>
          </header>
          <ul class="meals">
            <li v-for="(meal, i) in plan.meals" :key="i">
              <span class="ic">{{ meal.icon }}</span>
              <span class="x"><small>{{ meal.label }}</small><b>{{ meal.n }}</b></span>
              <span class="k"><span class="num">{{ meal.k }}</span></span>
            </li>
          </ul>
          <p v-if="scale !== null && Math.abs(scale) >= 5" class="scale">
            احتياجك حسب الحاسبة <b class="num">{{ target }}</b> سعرة — {{ scale > 0 ? 'زوّد' : 'قلّل' }} الكميات حوالي <b class="num">{{ Math.abs(scale) }}%</b>.
          </p>
          <p v-else-if="scale === null" class="scale">الكميات تقريبية. <RouterLink to="/calculator">احسب سعراتك</RouterLink> عشان نظبطها على قدّك.</p>
          <div class="dc-btns">
            <button class="done-btn" :class="{ on: isDone }" @click="toggleDone">{{ isDone ? '✓ خلّصته' : 'علّم إنه خلص' }}</button>
            <button v-if="day < 30" class="next" @click="nextDay">اليوم الجاي ‹</button>
          </div>
        </div>
      </Transition>
    </section>

    <!-- ───── SHOPPING + TIPS ───── -->
    <section class="wrap sec two">
      <div class="box">
        <h3>🛒 قايمة المشتريات</h3>
        <ul class="shop">
          <li v-for="item in d.shopping" :key="item">
            <button :class="{ got: saved.shop.includes(item) }" @click="toggleShop(item)"><i></i>{{ item }}</button>
          </li>
        </ul>
      </div>
      <div class="box">
        <h3>💡 نصايح</h3>
        <ul class="tips"><li v-for="t in d.tips" :key="t">{{ t }}</li></ul>
        <div class="warn"><b>⚠️ قبل ما تبدأ</b><p>{{ d.warning }}</p></div>
      </div>
    </section>

    <!-- ───── OTHER DIETS ───── -->
    <section class="sec others">
      <h2 class="h wrap">أنظمة <span class="acc">تانية</span></h2>
      <div class="rail">
        <RouterLink v-for="o in others" :key="o.slug" :to="`/diets/${o.slug}`" class="oc" :style="{ '--oa': o.theme.a, '--obg': o.theme.bg, '--oink': o.theme.ink }">
          <span class="oe">{{ o.emoji }}</span><b>{{ o.name }}</b><small class="en">{{ o.en }}</small>
        </RouterLink>
      </div>
    </section>
  </article>
</template>

<style scoped>
.diet { background: var(--bg); color: var(--ink); min-height: 100vh; padding-bottom: 60px; }
.diet.mob { padding-bottom: 30px; }
.acc { color: var(--a); }
.light .acc { filter: brightness(.9); }

/* hero */
.hero { position: relative; overflow: hidden; padding: 36px 0 30px; isolation: isolate; }
.mob .hero { padding-top: calc(64px + env(safe-area-inset-top)); }
.motif { position: absolute; inset: 0; z-index: -1; background-image: var(--motif); -webkit-mask: linear-gradient(to bottom, #000 40%, transparent); mask: linear-gradient(to bottom, #000 40%, transparent); }
.hero::after { content: ''; position: absolute; z-index: -1; width: 520px; height: 520px; border-radius: 50%; inset-inline-start: -160px; top: -200px; background: radial-gradient(circle, color-mix(in srgb, var(--a) 30%, transparent), transparent 65%); }
.hero-in { display: grid; gap: 20px; align-items: center; }
@media (min-width: 900px) { .hero-in { grid-template-columns: 1.2fr 1fr; } }
.copy { display: grid; gap: 10px; justify-items: start; }
.crumb { font-weight: 800; font-size: .85rem; color: var(--soft); }
.badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 99px; background: var(--a); color: #0a0a0a; font-weight: 900; font-size: .8rem; }
.badge .num { font-size: 1rem; }
.en { font-family: var(--f-num); letter-spacing: .08em; font-size: clamp(2.6rem, 11vw, 5.2rem); line-height: .9; color: transparent; -webkit-text-stroke: 2px var(--a); direction: ltr; }
.vis-dial .en, .vis-cycle .en { font-size: clamp(2rem, 8vw, 4rem); }
.copy h1 { font-size: clamp(2rem, 7vw, 3.2rem); line-height: 1.1; }
.tag { font-size: 1.05rem; color: var(--soft); max-width: 50ch; }
.facts { display: flex; flex-wrap: wrap; gap: 6px; }
.facts span { padding: 4px 12px; border-radius: 99px; border: 1px solid var(--line); background: var(--sf); font-size: .8rem; font-weight: 700; }
.facts b { color: var(--a); }
.cta { margin-top: 6px; min-height: 52px; padding: 0 22px; border-radius: 14px; background: var(--a); color: #0a0a0a; font-family: var(--f-head); font-size: 1.15rem; box-shadow: 0 12px 30px -14px var(--a); }
.cta:active { transform: scale(.97); }
.art { display: grid; place-items: center; }
.ring { padding: 22px; border-radius: 50%; background: radial-gradient(circle, var(--sf) 55%, transparent 72%); border: 1px solid var(--line); box-shadow: 0 0 0 14px color-mix(in srgb, var(--a) 7%, transparent); }
.vis-protein .ring, .vis-gauge .ring, .vis-cycle .ring, .vis-pulse .ring { border-radius: 30px; padding: 26px 22px; }

/* macros strip */
.strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.mac { position: relative; overflow: hidden; display: grid; padding: 12px 14px 16px; border-radius: 16px; background: var(--sf); border: 1px solid var(--line); }
.mac b { font-size: 1.9rem; line-height: 1; }
.mac span { font-size: .75rem; font-weight: 800; color: var(--soft); }
.mac i { position: absolute; bottom: 0; inset-inline-start: 0; height: 5px; }
.mac .ic { background: var(--soft); } .mac .ip { background: var(--b); } .mac .if { background: var(--a); }

/* sections */
.sec { padding-top: 40px; }
.h { font-size: clamp(1.7rem, 5vw, 2.4rem); margin-bottom: 14px; }
.how { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
@media (min-width: 800px) { .how { grid-template-columns: repeat(3, 1fr); } }
.how li { padding: 16px; border-radius: 18px; background: var(--sf); border: 1px solid var(--line); display: grid; gap: 4px; align-content: start; }
.how .n { font-size: 2.4rem; line-height: .9; color: var(--a); }
.how b { font-family: var(--f-head); font-weight: 400; font-size: 1.25rem; }
.how p { color: var(--soft); font-size: .92rem; }

.seg { display: flex; gap: 2px; padding: 4px; border-radius: 14px; background: var(--sf); border: 1px solid var(--line); max-width: 520px; }
.seg button { flex: 1; min-height: 40px; border-radius: 10px; font-weight: 800; font-size: .85rem; color: var(--soft); }
.seg button.on { background: var(--a); color: #0a0a0a; }
.pane { padding-top: 14px; min-height: 180px; }
.tick { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
@media (min-width: 700px) { .tick { grid-template-columns: 1fr 1fr; } }
.tick li { display: flex; gap: 10px; align-items: baseline; padding: 10px 12px; border-radius: 12px; background: var(--sf); border: 1px solid var(--line); font-weight: 700; font-size: .92rem; }
.tick.yes li::before { content: '✓'; color: var(--a); font-weight: 900; }
.tick.no li::before { content: '✕'; color: #ff5a5a; font-weight: 900; }
.who { display: grid; gap: 14px; }
@media (min-width: 800px) { .who { grid-template-columns: 1fr 1fr; } .who .tick { grid-template-columns: 1fr; } }
.who > div > b { display: block; margin-bottom: 8px; font-family: var(--f-head); font-weight: 400; font-size: 1.2rem; }

/* plan */
.plan-h { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.prog { font-size: .82rem; font-weight: 800; color: var(--soft); }
.prog .num { color: var(--a); font-size: 1.2rem; }
.weeks { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; margin-inline: calc(var(--gutter) * -1); padding: 2px var(--gutter); }
.weeks::-webkit-scrollbar { display: none; }
.weeks button { flex: none; min-height: 38px; padding: 0 14px; border-radius: 10px; border: 1.5px solid var(--line); font-weight: 800; font-size: .84rem; color: var(--soft); background: var(--sf); }
.weeks button.on { border-color: var(--a); color: var(--ink); box-shadow: inset 0 -3px 0 var(--a); }
.days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-top: 12px; max-width: 560px; }
.dd { aspect-ratio: 1; border-radius: 12px; background: var(--sf); border: 1.5px solid var(--line); color: var(--soft); font-size: 1.2rem; display: grid; place-items: center; transition: transform .1s; }
.dd:active { transform: scale(.92); }
.dd.done { background: color-mix(in srgb, var(--a) 28%, var(--sf)); color: var(--ink); }
.dd.sel { border-color: var(--a); background: var(--a); color: #0a0a0a; box-shadow: 0 8px 18px -10px var(--a); }
.dd.t-high { border-top: 4px solid var(--b); } .dd.t-low { border-top: 4px solid var(--line); } .dd.t-mid { border-top: 4px solid var(--a); }
.daycard { margin-top: 14px; max-width: 760px; padding: 16px; border-radius: 22px; background: var(--sf); border: 1px solid var(--line); box-shadow: 0 20px 40px -30px var(--a); }
.dc-h { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; margin-bottom: 6px; border-bottom: 1px dashed var(--line); }
.dc-h > div { display: grid; }
.dc-h b { font-family: var(--f-head); font-weight: 400; font-size: 1.5rem; }
.ty { font-weight: 800; font-size: .78rem; color: var(--b); }
.kc { font-weight: 800; font-size: .85rem; color: var(--soft); }
.kc .num { font-size: 1.5rem; color: var(--a); }
.meals { list-style: none; margin: 0; padding: 0; display: grid; }
.meals li { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); }
.meals li:last-child { border-bottom: 0; }
.meals .ic { width: 42px; height: 42px; flex: none; border-radius: 13px; display: grid; place-items: center; font-size: 1.25rem; background: color-mix(in srgb, var(--a) 14%, transparent); }
.meals .x { flex: 1; display: grid; }
.meals .x small { font-size: .72rem; font-weight: 800; color: var(--soft); }
.meals .x b { font-size: .98rem; line-height: 1.5; }
.meals .k { font-size: 1.15rem; color: var(--a); }
.scale { margin-top: 8px; font-size: .82rem; color: var(--soft); }
.scale b { color: var(--ink); }
.scale a { color: var(--a); font-weight: 800; }
.dc-btns { display: flex; gap: 8px; margin-top: 12px; }
.done-btn, .next { flex: 1; min-height: 46px; border-radius: 13px; font-weight: 900; font-size: .9rem; border: 1.5px solid var(--line); }
.done-btn.on { background: var(--a); color: #0a0a0a; border-color: var(--a); }
.next { color: var(--a); }

/* shopping + tips */
.two { display: grid; gap: 14px; }
@media (min-width: 900px) { .two { grid-template-columns: 1fr 1fr; } }
.box { padding: 18px; border-radius: 22px; background: var(--sf); border: 1px solid var(--line); display: grid; gap: 10px; align-content: start; }
.box h3 { font-size: 1.35rem; }
.shop { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.shop button { width: 100%; display: flex; align-items: center; gap: 8px; text-align: start; padding: 8px 10px; border-radius: 10px; font-weight: 700; font-size: .86rem; border: 1px solid var(--line); }
.shop i { width: 18px; height: 18px; flex: none; border-radius: 6px; border: 2px solid var(--a); }
.shop .got { opacity: .55; text-decoration: line-through; }
.shop .got i { background: var(--a); }
.tips { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
.tips li { display: flex; gap: 8px; font-weight: 600; font-size: .92rem; }
.tips li::before { content: '▸'; color: var(--a); }
.warn { padding: 12px 14px; border-radius: 14px; background: rgba(255,90,90,.1); border: 1px solid rgba(255,90,90,.35); display: grid; gap: 4px; font-size: .88rem; }

/* others */
.others .h { margin-bottom: 12px; }
.rail { display: flex; gap: 10px; overflow-x: auto; scrollbar-width: none; scroll-snap-type: x mandatory; padding-inline: var(--gutter); }
.rail::-webkit-scrollbar { display: none; }
.oc { flex: none; scroll-snap-align: start; width: 150px; padding: 14px; border-radius: 18px; background: var(--obg); color: var(--oink); border: 1px solid color-mix(in srgb, var(--oa) 40%, transparent); display: grid; gap: 2px; }
.oc .oe { font-size: 1.8rem; }
.oc b { font-family: var(--f-head); font-weight: 400; font-size: 1.2rem; }
.oc small { color: var(--oa); font-size: .8rem; letter-spacing: .12em; justify-self: start; }

.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
