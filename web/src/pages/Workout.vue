<script setup>
import { ref, computed, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import NotFound from './NotFound.vue'
import { programById, planById, egp } from '../data.js'
import { store, completeDay, doneDays, toast } from '../stores/app.js'
import { api } from '../api.js'

const route = useRoute()
const p = computed(() => programById(route.params.program))
const n = computed(() => Number(route.params.day))
const valid = computed(() => p.value && Number.isInteger(n.value) && n.value >= 1 && n.value <= p.value.days)

// The day comes from the API, which decides if it's locked (paywall is enforced server-side).
const day = ref(null)
const locked = ref(false)
const lockPlan = ref(null)
const loading = ref(true)
async function loadDay() {
  loading.value = true; locked.value = false; day.value = null
  if (!valid.value) { loading.value = false; return }
  try {
    day.value = await api(`/programs/${p.value.id}/days/${n.value}`)
  } catch (e) {
    if (e.status === 403 && e.data?.locked) { locked.value = true; lockPlan.value = e.data.plan }
    else toast(e.message, 'err')
  } finally {
    loading.value = false
  }
}
watch(() => route.fullPath, loadDay, { immediate: true })

const plan = computed(() => lockPlan.value || (p.value && planById(p.value.plan)))
const alreadyDone = computed(() => valid.value && doneDays(p.value.id).includes(n.value))

/* ---------- timer state machine ---------- */
const phase = ref('intro') // intro | work | rest | done
const idx = ref(0)
const round = ref(1)
const left = ref(0)
const total = ref(1)
const paused = ref(false)
let deadline = 0, pausedLeft = 0, tick = null

const item = computed(() => day.value?.items[idx.value])
const nextItem = computed(() => {
  if (!day.value) return null
  const last = idx.value === day.value.items.length - 1
  if (last && round.value === day.value.rounds) return null
  return day.value.items[last ? 0 : idx.value + 1]
})
const totalSteps = computed(() => (day.value ? day.value.items.length * day.value.rounds : 1))
const stepNo = computed(() => (round.value - 1) * (day.value?.items.length || 0) + idx.value + 1)
const estMin = computed(() => day.value && Math.round(day.value.items.reduce((s, i) => s + i.work + i.rest, 0) * day.value.rounds / 60))

function startPhase(ph, secs) {
  phase.value = ph
  total.value = secs
  left.value = secs
  deadline = Date.now() + secs * 1000
  navigator.vibrate?.(ph === 'work' ? [120, 60, 120] : 80)
}
function loop() {
  if (paused.value) return
  left.value = Math.max(0, Math.ceil((deadline - Date.now()) / 1000))
  if (left.value === 0) advance()
}
function start() {
  idx.value = 0; round.value = 1; paused.value = false
  startPhase('work', item.value.work)
  clearInterval(tick); tick = setInterval(loop, 200)
}
function advance() {
  if (phase.value === 'work') {
    if (!nextItem.value) return finish()
    return startPhase('rest', item.value.rest)
  }
  // rest -> next work
  if (idx.value === day.value.items.length - 1) { idx.value = 0; round.value++ } else idx.value++
  startPhase('work', item.value.work)
}
function back() {
  if (phase.value === 'rest') return startPhase('work', item.value.work)
  if (idx.value > 0) idx.value--
  else if (round.value > 1) { round.value--; idx.value = day.value.items.length - 1 }
  startPhase('work', item.value.work)
}
function togglePause() {
  if (paused.value) { deadline = Date.now() + pausedLeft * 1000; paused.value = false }
  else { pausedLeft = left.value; paused.value = true }
}
function finish() {
  clearInterval(tick)
  phase.value = 'done'
  completeDay(p.value.id, n.value).catch((e) => toast(e.message, 'err'))
}
function quit() { clearInterval(tick); phase.value = 'intro'; paused.value = false }
watch(() => route.fullPath, quit)
onBeforeUnmount(() => clearInterval(tick))

const R = 120, C = 2 * Math.PI * R
const dash = computed(() => C * (1 - left.value / total.value))
const nextDayLink = computed(() => (n.value < p.value.days ? `/workout/${p.value.id}/${n.value + 1}` : `/programs/${p.value.id}`))
const buyLink = computed(() => (store.user ? `/checkout/${plan.value.id}` : `/register?next=/checkout/${plan.value.id}`))
</script>

<template>
  <NotFound v-if="!valid" />
  <section v-else-if="loading" class="wrap loading"><span class="spin" aria-label="بنحمّل"></span></section>

  <!-- PAYWALL -->
  <section v-else-if="locked" class="wrap wall">
    <span class="lock" aria-hidden="true">🔒</span>
    <span class="kicker">{{ p.title }} · اليوم <span class="num">{{ n }}</span></span>
    <h1>اليوم ده <span class="hl">للمشتركين</span></h1>
    <p class="muted">أول <span class="num amber">3</span> أيام ببلاش — <RouterLink :to="`/workout/${p.id}/1`" class="hl">ابدأ منهم</RouterLink> لو لسه مجرّبتش.</p>
    <p class="muted">افتح البرنامج كله وكمّل من غير ما تقف. الحِمل بيزيد كل أسبوع، والتقدّم بتاعك محفوظ.</p>
    <div class="wall-plan">
      <b>{{ plan.name }}</b>
      <span><span class="num">{{ egp(plan.price) }}</span> ج.م <small>{{ plan.period }}</small></span>
    </div>
    <RouterLink :to="buyLink" class="btn btn-primary">افتح البرنامج</RouterLink>
    <RouterLink :to="`/programs/${p.id}`" class="muted back">رجوع للبرنامج</RouterLink>
  </section>

  <section v-else-if="!day" class="wrap loading">
    <p class="muted">معرفناش نحمّل اليوم ده.</p>
    <button class="btn btn-ghost btn-sm" @click="loadDay">جرّب تاني</button>
  </section>

  <!-- INTRO -->
  <section v-else-if="phase === 'intro'" class="wrap intro">
    <nav class="crumbs"><RouterLink :to="`/programs/${p.id}`">{{ p.title }}</RouterLink><span>/</span><span>اليوم <span class="num">{{ n }}</span></span></nav>
    <span class="kicker">الأسبوع <span class="num">{{ day.week }}</span> · {{ day.title }}</span>
    <h1>اليوم <span class="num hl">{{ n }}</span></h1>
    <div class="meta">
      <span><b class="num">{{ day.rounds }}</b> جولات</span>
      <span><b class="num">{{ day.items[0].work }}</b> ث شغل / <b class="num">{{ day.items[0].rest }}</b> ث راحة</span>
      <span>حوالي <b class="num">{{ estMin }}</b> دقيقة</span>
    </div>
    <ol class="list">
      <li v-for="(it, i) in day.items" :key="it.id">
        <RouterLink :to="`/library/${it.id}`" class="it">
          <img :src="it.img" alt="" loading="lazy" />
          <span class="it-n num">{{ i + 1 }}</span>
          <span class="it-t"><b class="en">{{ it.title }}</b><small>{{ it.muscles }}</small></span>
          <span class="it-l">شوف الفيديو ←</span>
        </RouterLink>
      </li>
    </ol>
    <p class="note">سخّن دقيقتين قبل ما تبدأ (مشي في المكان + لفّ كتافك). لو حسّيت وجع مش تعب، وقّف.</p>
    <p v-if="alreadyDone" class="done-badge">✓ خلّصت اليوم ده قبل كده — تقدر تعيده</p>
    <button class="btn btn-primary btn-block go" @click="start">▶ يلا نبدأ</button>
  </section>

  <!-- DONE -->
  <section v-else-if="phase === 'done'" class="wrap fin">
    <div class="trophy" aria-hidden="true">✓</div>
    <h1>عاش! <span class="hl">اليوم <span class="num">{{ n }}</span> خلص</span></h1>
    <p class="muted">خلّصت <span class="num amber">{{ doneDays(p.id).length }}</span> من <span class="num">{{ p.days }}</span> يوم في {{ p.title }}.</p>
    <div v-if="!store.user" class="save">
      <p>اعمل حساب عشان تقدّمك يتحفظ وتكمّل من أي موبايل.</p>
      <RouterLink :to="{ path: '/register', query: { next: `/programs/${p.id}` } }" class="btn btn-primary">احفظ تقدّمي</RouterLink>
    </div>
    <div class="btns">
      <RouterLink :to="nextDayLink" class="btn" :class="store.user ? 'btn-primary' : 'btn-ghost'">{{ n < p.days ? 'اليوم الجاي ←' : 'رجوع للبرنامج' }}</RouterLink>
      <RouterLink to="/account" class="btn btn-ghost">سجّل وزنك</RouterLink>
    </div>
  </section>

  <!-- PLAYER -->
  <section v-else class="player" :class="phase">
    <div class="pl-top wrap">
      <button class="x" aria-label="اقفل التمرين" @click="quit">✕</button>
      <span class="pl-step">جولة <span class="num">{{ round }}/{{ day.rounds }}</span> · تمرين <span class="num">{{ stepNo }}/{{ totalSteps }}</span></span>
    </div>
    <div class="bar"><span :style="{ width: (stepNo / totalSteps) * 100 + '%' }"></span></div>

    <div class="pl-main wrap">
      <div class="media">
        <img :src="(phase === 'rest' && nextItem ? nextItem : item).img" alt="" />
        <span class="ph-tag">{{ phase === 'work' ? 'شغل' : 'راحة' }}</span>
      </div>
      <div class="ctrl">
        <p class="label">{{ phase === 'work' ? 'دلوقتي' : 'خد نفسك… الجاي' }}</p>
        <h2 class="en">{{ (phase === 'rest' && nextItem ? nextItem : item).title }}</h2>
        <div class="ring" role="timer" :aria-label="`${left} ثانية`">
          <svg viewBox="0 0 260 260"><circle cx="130" cy="130" :r="R" class="trk" /><circle cx="130" cy="130" :r="R" class="prg" :stroke-dasharray="C" :stroke-dashoffset="dash" /></svg>
          <span class="num t">{{ left }}</span>
        </div>
        <p v-if="phase === 'work' && nextItem" class="next muted">الجاي: <span class="en">{{ nextItem.title }}</span></p>
        <div class="btns" dir="ltr">
          <button class="cb" aria-label="اللي فات" @click="back">⏮</button>
          <button class="cb main" :aria-label="paused ? 'كمّل' : 'وقف'" @click="togglePause">{{ paused ? '▶' : '❚❚' }}</button>
          <button class="cb" aria-label="اللي بعده" @click="advance">⏭</button>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.loading { padding-block: 80px; display: grid; place-items: center; }
.spin { width: 44px; height: 44px; border-radius: 50%; border: 4px solid #333; border-top-color: var(--orange); animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.wall, .intro, .fin { padding-block: 24px 60px; display: grid; gap: 14px; max-width: 720px; }
.wall { justify-items: center; text-align: center; padding-top: 48px; }
.lock { font-size: 3rem; }
.wall h1, .intro h1, .fin h1 { font-size: clamp(2.4rem, 9vw, 3.6rem); line-height: 1.1; }
.wall-plan { display: flex; gap: 16px; align-items: baseline; padding: 14px 20px; border: 2px solid var(--orange); border-radius: 14px; box-shadow: 6px 6px 0 var(--orange); }
.wall-plan .num { font-size: 2.2rem; color: var(--amber); }
.back { font-weight: 800; }
.meta { display: flex; flex-wrap: wrap; gap: 8px; }
.meta span { background: #1a1a1a; border-radius: 99px; padding: 5px 14px; font-size: .88rem; font-weight: 700; }
.meta b { color: var(--amber); font-size: 1.15rem; }
.list { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
.it { display: grid; grid-template-columns: 64px auto 1fr; gap: 12px; align-items: center; padding: 8px; padding-inline-end: 14px; border-radius: 14px; background: #121212; border: 1px solid #222; }
.it:hover { border-color: var(--orange); }
.it img { width: 64px; height: 84px; object-fit: cover; border-radius: 10px; grid-row: span 2; }
.it-n { font-size: 1.8rem; color: var(--orange); grid-row: span 2; }
.it-t { display: grid; }
.it-t b { font-size: 1.5rem; line-height: 1.1; justify-self: start; }
.it-t small { color: var(--muted); font-size: .8rem; }
.it-l { font-size: .78rem; color: var(--orange); font-weight: 800; }
.done-badge { color: var(--ok); font-weight: 800; }
.go { min-height: 60px; font-size: 1.4rem; position: sticky; bottom: 16px; }

.fin { justify-items: center; text-align: center; padding-top: 48px; }
.trophy { width: 96px; height: 96px; border-radius: 50%; background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-size: 3rem; font-weight: 900; box-shadow: 0 0 0 14px rgba(255,106,0,.2); }
.save { display: grid; gap: 10px; justify-items: center; padding: 18px; border-radius: 16px; background: #121212; border: 1px solid #262626; }
.btns { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }

/* player */
.player { min-height: calc(100svh - var(--header)); display: flex; flex-direction: column; }
.player.rest { background: radial-gradient(circle at 50% 30%, rgba(61,220,132,.08), transparent 60%); }
.pl-top { display: flex; align-items: center; gap: 12px; padding-block: 10px; }
.x { width: 40px; height: 40px; border-radius: 50%; background: #1a1a1a; }
.pl-step { font-weight: 800; font-size: .9rem; color: var(--muted); }
.bar { height: 4px; background: #1a1a1a; }
.bar span { display: block; height: 100%; background: var(--orange); transition: width .3s; }
.pl-main { flex: 1; display: grid; gap: 14px; padding-block: 14px 24px; align-content: start; }
.media { position: relative; height: 24svh; border-radius: 18px; overflow: hidden; background: #000; }
.media img { width: 100%; height: 100%; object-fit: cover; object-position: center 40%; }
.ph-tag { position: absolute; top: 10px; inset-inline-start: 10px; background: var(--orange); color: #0a0a0a; font-family: var(--f-head); font-size: 1.1rem; padding: 2px 12px; border-radius: 8px; }
.rest .ph-tag { background: var(--ok); }
.ctrl { display: grid; justify-items: center; gap: 8px; text-align: center; }
.label { font-weight: 800; color: var(--muted); }
.ctrl h2 { font-size: clamp(2.4rem, 10vw, 3.6rem); line-height: 1; }
.ring { position: relative; width: min(50vw, 30svh, 240px); aspect-ratio: 1; }
.ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.trk { fill: none; stroke: #1d1d1d; stroke-width: 14; }
.prg { fill: none; stroke: var(--orange); stroke-width: 14; stroke-linecap: round; transition: stroke-dashoffset .2s linear; }
.rest .prg { stroke: var(--ok); }
.t { position: absolute; inset: 0; display: grid; place-items: center; font-size: clamp(4.5rem, 20vw, 6.5rem); }
.next { font-size: .95rem; }
.next .en { font-size: 1.2rem; color: #fff; }
.cb { width: 56px; height: 56px; border-radius: 50%; background: #1a1a1a; font-size: 1.2rem; }
.cb.main { width: 76px; height: 76px; background: var(--orange); color: #0a0a0a; font-size: 1.5rem; }
@media (min-width: 900px) {
  .pl-main { grid-template-columns: 1fr 1fr; align-items: center; gap: 40px; }
  .media { height: 70svh; aspect-ratio: 9/16; justify-self: end; }
}
</style>
