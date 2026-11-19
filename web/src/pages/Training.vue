<script setup>
import { ref, reactive, computed, watch, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import NotFound from './NotFound.vue'
import MAppBar from '../mobile/MAppBar.vue'
import { MOBILE } from '../mobile/device.js'
import TrainVisual from '../training/TrainVisual.vue'
import { TRAININGS, trainingBySlug, trainingDay, fmtRest, PLACE, WEEKDAYS, WD_SHORT, shortKey } from '../training/data.js'
import { motifBg } from '../diets/motifs.js'
import { videoById } from '../data.js'

const route = useRoute()
const t = computed(() => trainingBySlug(route.params.slug))
watchEffect(() => { if (t.value) document.title = `نظام ${t.value.name} (${t.value.en}) — برنامج 4 أسابيع | فورمة` })

const vars = computed(() => {
  const th = t.value.theme
  return {
    '--bg': th.bg, '--sf': th.surface, '--ink': th.ink, '--a': th.a, '--b': th.b,
    '--line': th.mode === 'light' ? 'rgba(0,0,0,.09)' : 'rgba(255,255,255,.1)',
    '--soft': th.mode === 'light' ? 'rgba(0,0,0,.58)' : 'rgba(255,255,255,.62)',
    '--motif': motifBg(th.motif, th.a, th.mode === 'light' ? 0.22 : 0.14),
  }
})

/* ───── 4-week program: week tabs + 7-day row + selected day ───── */
const TOTAL = 28
const week = ref(1)
const day = ref(1)
const KEY = (s) => `forma:training:${s}`
const saved = reactive({ done: [] })
const isTraining = (n) => !trainingDay(t.value, n).rest
const trainingDays = computed(() => (t.value ? Array.from({ length: TOTAL }, (_, i) => i + 1).filter(isTraining) : []))
watch(t, (v) => {
  if (!v) return
  let s = {}
  try { s = JSON.parse(localStorage.getItem(KEY(v.slug)) || '{}') } catch { /* private mode */ }
  saved.done = s.done || []
  const first = trainingDays.value.find((n) => !saved.done.includes(n)) || 1
  day.value = first
  week.value = Math.ceil(first / 7)
}, { immediate: true })
watch(saved, () => { try { localStorage.setItem(KEY(t.value.slug), JSON.stringify(saved)) } catch { /* ignore */ } }, { deep: true })

const weekDays = computed(() => Array.from({ length: 7 }, (_, i) => (week.value - 1) * 7 + i + 1))
const plan = computed(() => (t.value ? trainingDay(t.value, day.value) : null))
const wk = computed(() => t.value.weeks[week.value - 1])
function pickWeek(w) { week.value = w; day.value = (w - 1) * 7 + 1 }
const isDone = computed(() => saved.done.includes(day.value))
const toggleDone = () => { const i = saved.done.indexOf(day.value); i >= 0 ? saved.done.splice(i, 1) : saved.done.push(day.value) }
const nextDay = () => {
  const n = trainingDays.value.find((x) => x > day.value)
  if (n) { day.value = n; week.value = Math.ceil(n / 7) }
}
const hasNext = computed(() => trainingDays.value.some((x) => x > day.value))
const doneCount = computed(() => saved.done.filter((n) => trainingDays.value.includes(n)).length)

// rough session length: sets × (work + rest)
const estMin = computed(() => {
  const p = plan.value
  if (!p || p.rest) return 0
  if (p.minutes) return p.minutes
  if (p.format === 'interval') {
    const per = p.items.reduce((s, x) => s + (parseInt(x.reps) || 40) + (x.rest || 0), 0)
    return Math.round(((p.blocks ? per * p.rounds : per * p.rounds) + 60 * ((p.blocks || p.rounds) - 1)) / 60)
  }
  if (p.format === 'chipper') return null
  return Math.round(p.items.reduce((s, x) => s + (x.sets || 1) * (40 + (x.rest || 60)), 0) / 60)
})
const fmtLabel = { sets: 'مجموعات وعدّات', interval: 'إنترفال', amrap: 'AMRAP', emom: 'EMOM', chipper: 'تشيبر' }

const vid = (id) => (id ? videoById(id) : null)
const tab = ref('good')
const others = computed(() => TRAININGS.filter((x) => x.slug !== t.value?.slug))
const scrollToPlan = () => document.getElementById('plan')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
const dayKeyShort = (n) => {
  const d = trainingDay(t.value, n)
  return d.rest ? '' : shortKey(d.key)
}
</script>

<template>
  <NotFound v-if="!t" />
  <article v-else class="trn" :class="[t.theme.mode, `vis-${t.visual}`, { mob: MOBILE }]" :style="vars">
    <MAppBar v-if="MOBILE" transparent fallback="/training" />

    <!-- ───── HERO ───── -->
    <header class="hero">
      <div class="motif" aria-hidden="true"></div>
      <div class="wrap hero-in">
        <div class="copy">
          <RouterLink v-if="!MOBILE" to="/training" class="crumb">أنظمة التدريب ‹</RouterLink>
          <span class="badge"><span aria-hidden="true">{{ t.emoji }}</span> برنامج <span class="num">4</span> أسابيع</span>
          <span class="en">{{ t.en }}</span>
          <h1>نظام {{ t.name }}</h1>
          <p class="tag">{{ t.tagline }}</p>
          <div class="facts">
            <span><b class="num">{{ t.daysPerWeek }}</b> أيام/أسبوع</span>
            <span>⏱ <b class="num">{{ t.minutes }}</b> د</span>
            <span>📍 {{ PLACE[t.place] }}</span>
            <span>المستوى: <b>{{ t.level }}</b></span>
          </div>
          <button class="cta" @click="scrollToPlan">ابدأ اليوم <span class="num">{{ day }}</span> ←</button>
        </div>
        <div class="art">
          <div class="ring"><TrainVisual :t="t" :size="MOBILE ? 230 : 300" /></div>
        </div>
      </div>
    </header>

    <!-- ───── HOW ───── -->
    <section class="wrap sec">
      <h2 class="h">بيشتغل <span class="acc">إزاي؟</span></h2>
      <ol class="how">
        <li v-for="(h, i) in t.how" :key="h.t"><span class="n num">0{{ i + 1 }}</span><b>{{ h.t }}</b><p>{{ h.d }}</p></li>
      </ol>
    </section>

    <!-- ───── WEEK MAP + FOR WHO ───── -->
    <section class="wrap sec two">
      <div class="box">
        <h3>🗓 شكل الأسبوع</h3>
        <ul class="map">
          <li v-for="(k, i) in t.split" :key="i" :class="{ rest: k === 'rest' }">
            <small>{{ WEEKDAYS[i] }}</small>
            <b>{{ k === 'rest' ? 'راحة 😌' : t.days[k].title }}</b>
          </li>
        </ul>
        <p v-if="t.splitAlt" class="hint">الأسبوع التاني والرابع بيتبدّل الترتيب عشان التمرينتين ياخدوا نفس الحق.</p>
      </div>
      <div class="box">
        <div class="seg" role="tablist">
          <button role="tab" :aria-selected="tab === 'good'" :class="{ on: tab === 'good' }" @click="tab = 'good'">✓ مناسب لـ</button>
          <button role="tab" :aria-selected="tab === 'not'" :class="{ on: tab === 'not' }" @click="tab = 'not'">✕ مش مناسب لـ</button>
        </div>
        <ul v-if="tab === 'good'" class="tick yes"><li v-for="x in t.goodFor" :key="x">{{ x }}</li></ul>
        <ul v-else class="tick no"><li v-for="x in t.notFor" :key="x">{{ x }}</li></ul>
        <div class="warn"><b>⚠️ قبل ما تبدأ</b><p>{{ t.warning }}</p></div>
      </div>
    </section>

    <!-- ───── 4-WEEK PROGRAM ───── -->
    <section id="plan" class="wrap sec">
      <div class="plan-h">
        <h2 class="h">البرنامج <span class="acc">بالتفصيل</span></h2>
        <span class="prog"><span class="num">{{ doneCount }}/{{ trainingDays.length }}</span> تمرينة خلّصتها</span>
      </div>
      <div class="weeks" role="tablist">
        <button v-for="w in 4" :key="w" role="tab" :aria-selected="week === w" :class="{ on: week === w }" @click="pickWeek(w)">
          أسبوع {{ w }}<small>{{ t.weeks[w - 1].t }}</small>
        </button>
      </div>
      <p class="wk-note"><b>{{ wk.t }}:</b> {{ wk.d }}</p>
      <div class="days">
        <button
          v-for="n in weekDays" :key="n" class="dd"
          :class="{ sel: day === n, done: saved.done.includes(n), rest: !isTraining(n) }"
          :aria-label="`اليوم ${n}`" @click="day = n"
        >
          <small>{{ WD_SHORT[(n - 1) % 7] }}</small>
          <span v-if="isTraining(n)" class="num">{{ dayKeyShort(n) }}</span>
          <span v-else>😌</span>
        </button>
      </div>

      <Transition name="fade" mode="out-in">
        <div v-if="plan" :key="day" class="daycard">
          <template v-if="plan.rest">
            <div class="rest-card">
              <span class="big-e">😌</span>
              <b>يوم راحة — {{ plan.weekday }}</b>
              <p>العضلة بتكبر وانت مرتاح. مشي خفيف 20–30 دقيقة أو إطالة لو حابب، ونوم كويس.</p>
              <RouterLink to="/training/mobility" class="ghost">جرّب 15 دقيقة مرونة ‹</RouterLink>
              <button v-if="hasNext" class="next" @click="nextDay">التمرينة الجاية ‹</button>
            </div>
          </template>
          <template v-else>
            <header class="dc-h">
              <div>
                <small class="ty">اليوم {{ plan.n }} · {{ plan.weekday }} · {{ fmtLabel[plan.format] }}</small>
                <b>{{ plan.title }}</b>
              </div>
              <span v-if="estMin" class="kc">≈ <span class="num">{{ estMin }}</span> د</span>
            </header>

            <p v-if="plan.format === 'interval' && !plan.blocks" class="fmt">كرّر الدايرة <b class="num">{{ plan.rounds }}</b> مرات — راحة دقيقة بين كل دايرة.</p>
            <p v-else-if="plan.blocks" class="fmt"><b class="num">{{ plan.blocks }}</b> بلوكات × <b class="num">8</b> جولات (20 ث شغل / 10 راحة) — دقيقة راحة بين البلوكات.</p>
            <p v-else-if="plan.format === 'amrap'" class="fmt">أكبر عدد جولات في <b class="num">{{ plan.minutes }}</b> دقيقة — سجّل رقمك.</p>
            <p v-else-if="plan.format === 'emom'" class="fmt"><b class="num">{{ plan.minutes }}</b> دقيقة: كل دقيقة تمرين، وكرّر الأربعة بالترتيب.</p>
            <p v-else-if="plan.format === 'chipper'" class="fmt">خلّص القايمة بالترتيب بأسرع وقت — قسّمها زي ما تحب.</p>

            <ol class="exs">
              <li v-for="(x, i) in plan.items" :key="i">
                <span class="idx num">{{ i + 1 }}</span>
                <RouterLink v-if="vid(x.vid)" :to="`/library/${x.vid}`" class="thumb"><img :src="vid(x.vid).img" alt="" loading="lazy" /><i>▶</i></RouterLink>
                <span class="x">
                  <b class="en-n">{{ x.n }}</b>
                  <small v-if="x.note">{{ x.note }}</small>
                </span>
                <span class="sr">
                  <span v-if="x.sets" class="chip"><b class="num">{{ x.sets }}</b>×<b class="num">{{ x.reps }}</b></span>
                  <span v-else class="chip"><b>{{ x.reps }}</b></span>
                  <small v-if="x.rest">راحة {{ fmtRest(x.rest) }}</small>
                </span>
              </li>
            </ol>
            <p class="scale">سخّن 5–10 دقايق الأول. الأرقام إرشادية — لو التكنيك باظ، قلّل الوزن أو العدد.</p>
            <div class="dc-btns">
              <button class="done-btn" :class="{ on: isDone }" @click="toggleDone">{{ isDone ? '✓ خلّصتها' : 'علّم إنها خلصت' }}</button>
              <button v-if="hasNext" class="next" @click="nextDay">التمرينة الجاية ‹</button>
            </div>
          </template>
        </div>
      </Transition>
    </section>

    <!-- ───── PROGRESSION ───── -->
    <section class="wrap sec">
      <h2 class="h">هتتطوّر <span class="acc">إزاي؟</span></h2>
      <ol class="prog-l">
        <li v-for="(w, i) in t.weeks" :key="i" :class="{ cur: week === i + 1 }">
          <span class="num">W{{ i + 1 }}</span><b>{{ w.t }}</b><p>{{ w.d }}</p>
        </li>
      </ol>
      <p class="note">بعد الـ 4 أسابيع: كرّر البرنامج بأوزان أتقل شوية، أو انقل لنظام تاني.</p>
    </section>

    <!-- ───── OTHER SYSTEMS ───── -->
    <section class="sec others">
      <h2 class="h wrap">أنظمة <span class="acc">تانية</span></h2>
      <div class="rail">
        <RouterLink v-for="o in others" :key="o.slug" :to="`/training/${o.slug}`" class="oc" :style="{ '--oa': o.theme.a, '--obg': o.theme.mode === 'light' ? '#13231d' : o.theme.bg, '--oink': o.theme.mode === 'light' ? '#eafff6' : o.theme.ink }">
          <span class="oe">{{ o.emoji }}</span><b>{{ o.name }}</b><small class="en">{{ o.en }}</small>
        </RouterLink>
      </div>
    </section>
  </article>
</template>

<style scoped>
.trn { background: var(--bg); color: var(--ink); min-height: 100vh; padding-bottom: 60px; }
.trn.mob { padding-bottom: 30px; }
.acc { color: var(--a); }
.light .acc { filter: brightness(.9); }

/* hero */
.hero { position: relative; overflow: hidden; padding: 36px 0 30px; isolation: isolate; }
.mob .hero { padding-top: calc(64px + env(safe-area-inset-top)); }
.motif { position: absolute; inset: 0; z-index: -1; background-image: var(--motif); -webkit-mask: linear-gradient(to bottom, #000 40%, transparent); mask: linear-gradient(to bottom, #000 40%, transparent); }
.hero::after { content: ''; position: absolute; z-index: -1; width: 520px; height: 520px; border-radius: 50%; inset-inline-start: -160px; top: -200px; background: radial-gradient(circle, color-mix(in srgb, var(--a) 30%, transparent), transparent 65%); }
.hero-in { display: grid; gap: 20px; align-items: center; }
@media (min-width: 900px) { .hero-in { grid-template-columns: 1.2fr 1fr; } }
.copy { display: grid; gap: 10px; justify-items: start; min-width: 0; }
.crumb { font-weight: 800; font-size: .85rem; color: var(--soft); }
.badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 99px; background: var(--a); color: #0a0a0a; font-weight: 900; font-size: .8rem; }
.badge .num { font-size: 1rem; }
.en { font-family: var(--f-num); letter-spacing: .06em; font-size: clamp(2.2rem, 10vw, 4.6rem); line-height: .9; color: transparent; -webkit-text-stroke: 2px var(--a); direction: ltr; max-width: 100%; overflow-wrap: anywhere; }
.copy h1 { font-size: clamp(2rem, 7vw, 3.2rem); line-height: 1.1; }
.tag { font-size: 1.05rem; color: var(--soft); max-width: 50ch; }
.facts { display: flex; flex-wrap: wrap; gap: 6px; }
.facts span { padding: 4px 12px; border-radius: 99px; border: 1px solid var(--line); background: var(--sf); font-size: .8rem; font-weight: 700; }
.facts b { color: var(--a); }
.cta { margin-top: 6px; min-height: 52px; padding: 0 22px; border-radius: 14px; background: var(--a); color: #0a0a0a; font-family: var(--f-head); font-size: 1.15rem; box-shadow: 0 12px 30px -14px var(--a); }
.cta:active { transform: scale(.97); }
.art { display: grid; place-items: center; }
.ring { padding: 22px; border-radius: 50%; background: radial-gradient(circle, var(--sf) 55%, transparent 72%); border: 1px solid var(--line); box-shadow: 0 0 0 14px color-mix(in srgb, var(--a) 7%, transparent); }
.vis-split .ring, .vis-ladder .ring { border-radius: 30px; padding: 26px 22px 18px; }

/* sections */
.sec { padding-top: 40px; }
.h { font-size: clamp(1.7rem, 5vw, 2.4rem); margin-bottom: 14px; }
.how { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
@media (min-width: 800px) { .how { grid-template-columns: repeat(3, 1fr); } }
.how li { padding: 16px; border-radius: 18px; background: var(--sf); border: 1px solid var(--line); display: grid; gap: 4px; align-content: start; }
.how .n { font-size: 2.4rem; line-height: .9; color: var(--a); }
.how b { font-family: var(--f-head); font-weight: 400; font-size: 1.25rem; }
.how p { color: var(--soft); font-size: .92rem; }

.two { display: grid; gap: 14px; }
@media (min-width: 900px) { .two { grid-template-columns: 1fr 1fr; } }
.box { padding: 18px; border-radius: 22px; background: var(--sf); border: 1px solid var(--line); display: grid; gap: 10px; align-content: start; }
.box h3 { font-size: 1.35rem; }
.map { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
.map li { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 12px; border: 1px solid var(--line); border-inline-start: 4px solid var(--a); }
.map li.rest { border-inline-start-color: var(--line); opacity: .65; }
.map small { width: 54px; flex: none; font-weight: 800; color: var(--soft); font-size: .76rem; }
.map b { font-size: .92rem; }
.hint { font-size: .8rem; color: var(--soft); }
.seg { display: flex; gap: 2px; padding: 4px; border-radius: 14px; background: var(--bg); border: 1px solid var(--line); }
.seg button { flex: 1; min-height: 40px; border-radius: 10px; font-weight: 800; font-size: .85rem; color: var(--soft); }
.seg button.on { background: var(--a); color: #0a0a0a; }
.tick { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; min-height: 90px; align-content: start; }
.tick li { display: flex; gap: 10px; align-items: baseline; padding: 10px 12px; border-radius: 12px; background: var(--bg); border: 1px solid var(--line); font-weight: 700; font-size: .92rem; }
.tick.yes li::before { content: '✓'; color: var(--a); font-weight: 900; }
.tick.no li::before { content: '✕'; color: #ff5a5a; font-weight: 900; }
.warn { padding: 12px 14px; border-radius: 14px; background: rgba(255,90,90,.1); border: 1px solid rgba(255,90,90,.35); display: grid; gap: 4px; font-size: .88rem; }

/* plan */
.plan-h { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.prog { font-size: .82rem; font-weight: 800; color: var(--soft); }
.prog .num { color: var(--a); font-size: 1.2rem; }
.weeks { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; margin-inline: calc(var(--gutter) * -1); padding: 2px var(--gutter); }
.weeks::-webkit-scrollbar { display: none; }
.weeks button { flex: none; display: grid; min-height: 46px; padding: 4px 14px; border-radius: 10px; border: 1.5px solid var(--line); font-weight: 800; font-size: .84rem; color: var(--soft); background: var(--sf); line-height: 1.3; }
.weeks button small { font-size: .66rem; font-weight: 700; opacity: .8; }
.weeks button.on { border-color: var(--a); color: var(--ink); box-shadow: inset 0 -3px 0 var(--a); }
.wk-note { margin-top: 10px; font-size: .86rem; color: var(--soft); max-width: 640px; }
.wk-note b { color: var(--a); }
.days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-top: 12px; max-width: 560px; }
.dd { aspect-ratio: .85; border-radius: 12px; background: var(--sf); border: 1.5px solid var(--line); color: var(--ink); display: grid; place-items: center; align-content: center; gap: 2px; transition: transform .1s; padding: 2px; }
.dd small { font-size: .62rem; font-weight: 800; color: var(--soft); }
.dd .num { font-size: .95rem; color: var(--a); direction: ltr; }
.dd:active { transform: scale(.92); }
.dd.rest { opacity: .6; }
.dd.done { background: color-mix(in srgb, var(--a) 28%, var(--sf)); }
.dd.sel { border-color: var(--a); background: var(--a); box-shadow: 0 8px 18px -10px var(--a); opacity: 1; }
.dd.sel small, .dd.sel .num { color: #0a0a0a; }
.daycard { margin-top: 14px; max-width: 760px; padding: 16px; border-radius: 22px; background: var(--sf); border: 1px solid var(--line); box-shadow: 0 20px 40px -30px var(--a); }
.dc-h { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; margin-bottom: 6px; border-bottom: 1px dashed var(--line); gap: 10px; }
.dc-h > div { display: grid; }
.dc-h b { font-family: var(--f-head); font-weight: 400; font-size: 1.5rem; line-height: 1.2; }
.ty { font-weight: 800; font-size: .76rem; color: var(--b); }
.light .ty { filter: brightness(.8); }
.kc { flex: none; font-weight: 800; font-size: .85rem; color: var(--soft); }
.kc .num { font-size: 1.5rem; color: var(--a); }
.fmt { margin: 4px 0 6px; padding: 10px 12px; border-radius: 12px; background: color-mix(in srgb, var(--a) 12%, transparent); font-size: .86rem; font-weight: 700; }
.fmt b { color: var(--a); font-size: 1.1rem; }
.exs { list-style: none; margin: 0; padding: 0; display: grid; }
.exs li { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--line); }
.exs li:last-child { border-bottom: 0; }
.idx { width: 28px; height: 28px; flex: none; border-radius: 9px; display: grid; place-items: center; font-size: 1rem; background: color-mix(in srgb, var(--a) 16%, transparent); color: var(--a); }
.thumb { position: relative; width: 44px; height: 54px; flex: none; border-radius: 10px; overflow: hidden; }
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.thumb i { position: absolute; inset: 0; display: grid; place-items: center; font-style: normal; font-size: .8rem; color: #fff; background: rgba(0,0,0,.35); }
.exs .x { flex: 1; display: grid; min-width: 0; }
.en-n { font-family: var(--f-num); font-weight: 400; font-size: 1.15rem; letter-spacing: .03em; direction: ltr; text-align: right; line-height: 1.15; }
.exs .x small { font-size: .74rem; font-weight: 700; color: var(--soft); }
.sr { flex: none; display: grid; justify-items: end; gap: 2px; }
.chip { padding: 3px 10px; border-radius: 9px; background: var(--bg); border: 1px solid var(--line); font-size: .82rem; font-weight: 800; direction: ltr; white-space: nowrap; }
.chip b.num { font-size: 1.05rem; color: var(--a); }
.chip b:not(.num) { font-size: .82rem; }
.sr small { font-size: .68rem; font-weight: 800; color: var(--soft); }
.scale { margin-top: 8px; font-size: .8rem; color: var(--soft); }
.dc-btns { display: flex; gap: 8px; margin-top: 12px; }
.done-btn, .next { flex: 1; min-height: 46px; border-radius: 13px; font-weight: 900; font-size: .9rem; border: 1.5px solid var(--line); }
.done-btn.on { background: var(--a); color: #0a0a0a; border-color: var(--a); }
.next { color: var(--a); }
.rest-card { display: grid; justify-items: center; text-align: center; gap: 8px; padding: 14px 4px; }
.big-e { font-size: 3rem; }
.rest-card b { font-family: var(--f-head); font-weight: 400; font-size: 1.5rem; }
.rest-card p { color: var(--soft); font-size: .9rem; max-width: 42ch; }
.ghost { font-weight: 800; color: var(--a); font-size: .9rem; }
.rest-card .next { width: 100%; max-width: 320px; flex: none; }

/* progression */
.prog-l { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
@media (min-width: 800px) { .prog-l { grid-template-columns: repeat(4, 1fr); } }
.prog-l li { padding: 14px; border-radius: 16px; background: var(--sf); border: 1px solid var(--line); display: grid; gap: 2px; align-content: start; }
.prog-l li.cur { border-color: var(--a); box-shadow: inset 0 3px 0 var(--a); }
.prog-l .num { color: var(--a); font-size: 1.3rem; }
.prog-l b { font-family: var(--f-head); font-weight: 400; font-size: 1.15rem; }
.prog-l p { font-size: .84rem; color: var(--soft); }
.note { margin-top: 10px; font-size: .82rem; color: var(--soft); }

/* others */
.others .h { margin-bottom: 12px; }
.rail { display: flex; gap: 10px; overflow-x: auto; scrollbar-width: none; scroll-snap-type: x mandatory; padding-inline: var(--gutter); }
.rail::-webkit-scrollbar { display: none; }
.oc { flex: none; scroll-snap-align: start; width: 150px; padding: 14px; border-radius: 18px; background: var(--obg); color: var(--oink); border: 1px solid color-mix(in srgb, var(--oa) 40%, transparent); display: grid; gap: 2px; }
.oc .oe { font-size: 1.8rem; }
.oc b { font-family: var(--f-head); font-weight: 400; font-size: 1.2rem; }
.oc small { color: var(--oa); font-size: .72rem; letter-spacing: .06em; justify-self: start; direction: ltr; }

.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
