<script setup>
import { computed } from 'vue'
import { programDay } from '../data.js'
import { store, nextWorkout, doneDays, hasAccess, greeting } from '../stores/app.js'

const next = computed(() => nextWorkout())
const p = computed(() => next.value?.program)
const day = computed(() => (p.value ? programDay(p.value, next.value.day) : null))
const done = computed(() => (p.value ? doneDays(p.value.id).length : 0))
const pct = computed(() => (p.value ? done.value / p.value.days : 0))
const R = 26, C = 2 * Math.PI * R
const lastWeight = computed(() => store.weights.at(-1)?.kg)
const totalDone = computed(() => Object.values(store.user ? store.completed : store.guestCompleted).reduce((s, d) => s + d.length, 0))
const firstName = computed(() => store.user?.name?.split(' ')[0])
</script>

<template>
  <section v-if="p && day" class="today">
    <p class="hi">{{ greeting() }}{{ firstName ? ` يا ${firstName}` : '' }} 👊</p>
    <h1>{{ next.started ? 'نكمّل؟' : 'يلا نبدأ؟' }}</h1>

    <RouterLink :to="`/workout/${p.id}/${next.day}`" class="card">
      <img :src="day.items[0]?.img || p.img" alt="" class="bg" />
      <div class="top">
        <span class="chip">{{ p.title }}</span>
        <svg class="ring" viewBox="0 0 64 64" :aria-label="`خلّصت ${done} من ${p.days}`">
          <circle cx="32" cy="32" :r="R" class="trk" />
          <circle cx="32" cy="32" :r="R" class="prg" :stroke-dasharray="C" :stroke-dashoffset="C * (1 - pct)" />
          <text x="32" y="37" text-anchor="middle" class="num">{{ done }}/{{ p.days }}</text>
        </svg>
      </div>
      <div class="bottom">
        <span class="kicker">الأسبوع <span class="num">{{ day.week }}</span> · {{ day.title }}</span>
        <h2>اليوم <span class="num">{{ next.day }}</span></h2>
        <p class="en moves">{{ day.items.map((i) => i.title).join(' · ') }}</p>
        <span class="go">▶ ابدأ تمرين النهارده</span>
      </div>
    </RouterLink>

    <div class="stats">
      <RouterLink to="/account" class="st"><b class="num">{{ totalDone }}</b><span>يوم خلّصته</span></RouterLink>
      <RouterLink to="/account" class="st"><b class="num">{{ lastWeight ?? '—' }}</b><span>وزنك (كجم)</span></RouterLink>
      <RouterLink :to="store.calc?.kcal ? '/free-plan' : '/calculator'" class="st"><b class="num">{{ store.calc?.kcal ?? '—' }}</b><span>سعراتك</span></RouterLink>
    </div>

    <RouterLink v-if="!hasAccess(p.id)" to="/pricing" class="upsell">
      <span>🔓</span><div><b>افتح البرنامج كله</b><small>أول 3 أيام ببلاش — كمّل من غير ما تقف</small></div><i>←</i>
    </RouterLink>
  </section>
</template>

<style scoped>
.today { display: grid; gap: 12px; padding-top: calc(var(--header) + env(safe-area-inset-top) + 10px); }
.hi { color: var(--muted); font-weight: 800; }
h1 { font-size: clamp(2.2rem, 9vw, 3rem); line-height: 1.05; margin-top: -6px; }
.card { position: relative; display: flex; flex-direction: column; justify-content: space-between; min-height: 300px; padding: 16px; border-radius: 22px; overflow: hidden; isolation: isolate; border: 1px solid #262626; box-shadow: 0 20px 40px -24px rgba(255,106,0,.55); transition: transform .15s; }
.card:active { transform: scale(.985); }
.bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 35%; z-index: -2; }
.card::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(to top, rgba(0,0,0,.95) 25%, rgba(0,0,0,.25) 65%, rgba(0,0,0,.45)); }
.top { display: flex; justify-content: space-between; align-items: flex-start; }
.chip { background: rgba(0,0,0,.6); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,.15); padding: 4px 12px; border-radius: 99px; font-weight: 800; font-size: .82rem; }
.ring { width: 64px; height: 64px; }
.ring circle { fill: none; stroke-width: 6; }
.trk { stroke: rgba(255,255,255,.18); }
.prg { stroke: var(--orange); stroke-linecap: round; transform: rotate(-90deg); transform-origin: 50% 50%; }
.ring text { fill: #fff; font-family: var(--f-num); font-size: 15px; direction: ltr; }
.bottom { display: grid; gap: 2px; }
.bottom h2 { font-size: 2.6rem; line-height: 1; }
.bottom h2 .num { color: var(--orange); font-size: 1.15em; }
.moves { color: rgba(255,255,255,.8); font-size: 1.05rem; justify-self: start; }
.go { margin-top: 10px; justify-self: stretch; text-align: center; padding: 14px; border-radius: 14px; background: var(--orange); color: #0a0a0a; font-family: var(--f-head); font-size: 1.2rem; }
.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.st { display: grid; padding: 12px; border-radius: 16px; background: #141414; border: 1px solid #222; }
.st:active { background: #1b1b1b; }
.st b { font-size: 1.9rem; line-height: 1; color: #fff; justify-self: start; }
.st span { font-size: .72rem; color: var(--muted); font-weight: 800; }
.upsell { display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 16px; background: linear-gradient(120deg, rgba(255,106,0,.18), rgba(255,106,0,.04)); border: 1px solid rgba(255,106,0,.45); }
.upsell span { font-size: 1.4rem; }
.upsell div { flex: 1; display: grid; }
.upsell small { color: var(--muted); font-size: .8rem; }
.upsell i { font-style: normal; color: var(--orange); font-weight: 900; }
@media (min-width: 900px) { .card { min-height: 380px; padding: 24px; } .today { max-width: 720px; } }
</style>
