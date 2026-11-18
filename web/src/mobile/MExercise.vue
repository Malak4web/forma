<script setup>
import { ref, computed, watchEffect, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import MAppBar from './MAppBar.vue'
import MSeg from './MSeg.vue'
import NotFound from '../pages/NotFound.vue'
import { videoById, programs, categories } from '../data.js'

const route = useRoute()
const v = computed(() => videoById(route.params.id))
watchEffect(() => { if (v.value) document.title = `${v.value.title} — ${v.value.ar} | فورمة` })
const catLabel = computed(() => categories.find((c) => c.id === v.value?.cat)?.label)
const inPrograms = computed(() => programs.filter((p) => p.weeks.some((w) => w.pool.includes(v.value?.id))))
const tab = ref('how')
const tabs = computed(() => [
  { id: 'how', label: 'الطريقة' },
  ...(v.value?.tips ? [{ id: 'tips', label: 'نصايح' }] : []),
  ...(inPrograms.value.length ? [{ id: 'progs', label: 'في البرامج' }] : []),
])

/* quick single-exercise timer: 3 rounds × 40s work / 20s rest */
const ROUNDS = 3, WORK = 40, REST = 20
const timer = ref(null) // { round, phase: 'work'|'rest'|'done', left }
let t, deadline, pausedLeft = 0
const paused = ref(false)
function startTimer() { timer.value = { round: 1, phase: 'work', left: WORK }; deadline = Date.now() + WORK * 1000; paused.value = false; clearInterval(t); t = setInterval(tick, 200) }
function tick() {
  if (paused.value || !timer.value) return
  timer.value.left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000))
  if (timer.value.left > 0) return
  const s = timer.value
  if (s.phase === 'work' && s.round === ROUNDS) { s.phase = 'done'; clearInterval(t); navigator.vibrate?.([120, 60, 120]); return }
  if (s.phase === 'work') { s.phase = 'rest'; s.left = REST } else { s.phase = 'work'; s.round++; s.left = WORK }
  deadline = Date.now() + s.left * 1000
  navigator.vibrate?.(80)
}
function togglePause() {
  if (paused.value) { deadline = Date.now() + pausedLeft * 1000; paused.value = false } else { pausedLeft = timer.value.left; paused.value = true }
}
function stopTimer() { clearInterval(t); timer.value = null }
onBeforeUnmount(() => clearInterval(t))
const RR = 88, CC = 2 * Math.PI * RR
const total = computed(() => (timer.value?.phase === 'rest' ? REST : WORK))
</script>

<template>
  <NotFound v-if="!v" />
  <div v-else class="ex">
    <div class="media">
      <img :src="v.img" :alt="v.title" />
      <MAppBar transparent fallback="/library">
        <RouterLink :to="{ path: '/library', query: { cat: v.cat } }" class="mib glass" aria-label="تمارين شبهه">
          <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7.5" height="10" rx="2" /><rect x="13.5" y="3" width="7.5" height="10" rx="2" /><rect x="3" y="15.5" width="7.5" height="5.5" rx="2" /><rect x="13.5" y="15.5" width="7.5" height="5.5" rx="2" /></svg>
        </RouterLink>
      </MAppBar>
      <button class="play" aria-label="شغّل الفيديو">▶</button>
    </div>

    <div class="m-pad head">
      <span class="m-tag">{{ catLabel }}</span>
      <h1 class="en">{{ v.title }}</h1>
      <p class="hook">{{ v.ar }}</p>
    </div>

    <MSeg v-if="tabs.length > 1" v-model="tab" :options="tabs" class="seg" />
    <div class="m-pad pane">
      <div v-if="tab === 'how'" class="set">
        <div><b class="num">3</b><span>جولات</span></div>
        <div><b class="num">40</b><span>ثانية شغل</span></div>
        <div><b class="num">20</b><span>ثانية راحة</span></div>
      </div>
      <ul v-else-if="tab === 'tips'" class="tips">
        <li v-for="tip in v.tips" :key="tip">{{ tip }}</li>
      </ul>
      <div v-else class="progs">
        <RouterLink v-for="p in inPrograms" :key="p.id" :to="`/programs/${p.id}`" class="pchip">{{ p.title }} ‹</RouterLink>
      </div>
      <p class="note">لو حسّيت وجع مش مجرد تعب، وقّف. ولو الوجع شديد أو مستمر، اكشف عند دكتور.</p>
    </div>

    <div class="m-cta"><button class="btn btn-primary" @click="startTimer">▶ جرّبه دلوقتي — مؤقّت</button></div>

    <!-- full-screen quick timer -->
    <Teleport to="body">
      <div v-if="timer" class="qt" :class="timer.phase" role="dialog" aria-modal="true">
        <header class="qt-h">
          <button class="mib" aria-label="قفل" @click="stopTimer"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
          <span class="muted">جولة <span class="num">{{ timer.round }}/{{ ROUNDS }}</span></span>
        </header>
        <template v-if="timer.phase !== 'done'">
          <img :src="v.img" alt="" class="qt-img" />
          <span class="qt-tag">{{ timer.phase === 'work' ? 'شغل' : 'راحة' }}</span>
          <h2 class="en">{{ v.title }}</h2>
          <div class="qt-ring">
            <svg viewBox="0 0 200 200"><circle cx="100" cy="100" :r="RR" class="tk" /><circle cx="100" cy="100" :r="RR" class="pg" :stroke-dasharray="CC" :stroke-dashoffset="CC * (1 - timer.left / total)" /></svg>
            <span class="num">{{ timer.left }}</span>
          </div>
          <div class="qt-btns" dir="ltr">
            <button class="qt-main" :aria-label="paused ? 'كمّل' : 'وقف'" @click="togglePause">{{ paused ? '▶' : '❚❚' }}</button>
          </div>
        </template>
        <div v-else class="qt-done">
          <div class="badge">✓</div>
          <h2>عاش! <span class="hl">خلّصت الـ 3 جولات</span></h2>
          <button class="btn btn-primary" @click="stopTimer">تمام</button>
          <button class="btn btn-ghost" @click="startTimer">عيده تاني</button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.ex { min-height: 100dvh; display: flex; flex-direction: column; padding-bottom: var(--m-cta); }
.media { position: relative; height: 46dvh; min-height: 300px; flex: none; }
.media > img { width: 100%; height: 100%; object-fit: cover; object-position: center 35%; }
.media::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, #0a0a0a 2%, transparent 40%); pointer-events: none; }
.play { position: absolute; z-index: 2; inset: 0; margin: auto; width: 72px; height: 72px; border-radius: 50%; background: var(--orange); color: #0a0a0a; font-size: 1.5rem; padding-inline-start: 4px; box-shadow: 0 0 0 10px rgba(255,106,0,.25); }
.head { display: grid; gap: 2px; margin-top: -6px; position: relative; z-index: 1; }
.head .m-tag { justify-self: start; }
.head h1 { font-size: 3rem; line-height: .95; justify-self: start; }
.hook { font-family: var(--f-head); font-size: 1.25rem; }
.seg { margin: 14px 16px 0; }
.pane { padding-top: 14px; display: grid; gap: 12px; align-content: start; }
.set { display: grid; grid-template-columns: repeat(3, 1fr); border: 2px solid #fff; border-radius: 6px; box-shadow: 5px 5px 0 var(--orange); text-align: center; background: #0f0f0f; }
.set div { display: grid; padding: 10px 4px; }
.set div + div { border-inline-start: 1px solid #333; }
.set b { font-size: 2.1rem; line-height: 1; color: var(--orange); }
.set span { font-size: .72rem; color: var(--muted); font-weight: 800; }
.tips { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
.tips li { display: flex; gap: 10px; font-weight: 600; }
.tips li::before { content: '▸'; color: var(--orange); }
.progs { display: flex; flex-wrap: wrap; gap: 8px; }
.pchip { border: 1.5px solid var(--orange); color: var(--orange); border-radius: 10px; padding: 7px 12px; font-weight: 800; font-size: .88rem; }
.note { font-size: .78rem; }

/* quick timer */
.qt { position: fixed; inset: 0; z-index: 300; background: #0a0a0a; display: flex; flex-direction: column; align-items: center; gap: 8px; padding: env(safe-area-inset-top) 16px max(20px, env(safe-area-inset-bottom)); }
.qt.rest { background: radial-gradient(circle at 50% 35%, rgba(61,220,132,.1), #0a0a0a 60%); }
.qt-h { width: 100%; display: flex; align-items: center; justify-content: space-between; height: 56px; font-weight: 800; }
.qt-img { width: 100%; height: 28dvh; object-fit: cover; object-position: center 40%; border-radius: 22px; }
.qt-tag { margin-top: -30px; position: relative; background: var(--orange); color: #0a0a0a; font-family: var(--f-head); padding: 2px 14px; border-radius: 9px; }
.rest .qt-tag { background: var(--ok); }
.qt h2 { font-size: 2.4rem; line-height: 1; }
.qt-ring { position: relative; width: min(52vw, 30dvh); aspect-ratio: 1; }
.qt-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.qt-ring circle { fill: none; stroke-width: 12; }
.qt-ring .tk { stroke: #1d1d1d; }
.qt-ring .pg { stroke: var(--orange); stroke-linecap: round; transition: stroke-dashoffset .2s linear; }
.rest .qt-ring .pg { stroke: var(--ok); }
.qt-ring .num { position: absolute; inset: 0; display: grid; place-items: center; font-size: clamp(4rem, 18vw, 5.5rem); }
.qt-btns { margin-top: auto; }
.qt-main { width: 78px; height: 78px; border-radius: 50%; background: var(--orange); color: #0a0a0a; font-size: 1.6rem; font-weight: 900; }
.qt-done { margin: auto 0; display: grid; gap: 12px; justify-items: center; text-align: center; width: 100%; }
.qt-done .badge { width: 100px; height: 100px; border-radius: 30px; transform: rotate(-8deg); background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-size: 3rem; font-weight: 900; box-shadow: 6px 6px 0 #fff; }
.qt-done h2 { font-size: 2.1rem; }
.qt-done .btn { width: 100%; }
</style>
