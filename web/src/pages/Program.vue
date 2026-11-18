<script setup>
import { computed, ref, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import NotFound from './NotFound.vue'
import { programById, programDay, planById, meta, egp } from '../data.js'
import { store, hasAccess, doneDays, ui } from '../stores/app.js'

const route = useRoute()
const p = computed(() => programById(route.params.id))
watchEffect(() => { if (p.value) { document.title = `${p.value.title} | فورمة`; ui.title = p.value.title } })
const plan = computed(() => p.value && planById(p.value.plan))
const owned = computed(() => p.value && hasAccess(p.value.id))
const done = computed(() => (p.value ? doneDays(p.value.id) : []))
const days = computed(() => (p.value ? Array.from({ length: p.value.days }, (_, i) => programDay(p.value, i + 1)) : []))
const weeks = computed(() => {
  const out = []
  days.value.forEach((d) => (out[d.week - 1] ||= { n: d.week, t: p.value.weeks[d.week - 1].t, days: [] }).days.push(d))
  return out
})
const openWeek = ref(0)
const nextDay = computed(() => days.value.find((d) => !done.value.includes(d.n))?.n || 1)
const locked = (n) => !owned.value && n > meta.freeDays
const buyLink = computed(() => (store.user ? `/checkout/${plan.value.id}` : `/register?next=/checkout/${plan.value.id}`))
</script>

<template>
  <NotFound v-if="!p" />
  <div v-else>
    <header class="ph">
      <img :src="p.img" alt="" class="ph-bg" />
      <div class="wrap ph-in">
        <nav class="crumbs"><RouterLink to="/programs">البرامج</RouterLink><span>/</span><span>{{ p.title }}</span></nav>
        <span v-if="p.tag" class="badge">{{ p.tag }}</span>
        <h1>{{ p.title }}</h1>
        <p class="sub">{{ p.sub }}</p>
        <p class="pitch">{{ p.pitch }}</p>
        <div class="stats">
          <div><b class="num">{{ p.days }}</b><span>يوم</span></div>
          <div><b class="num">{{ p.mins }}</b><span>دقيقة/يوم</span></div>
          <div><b class="num">0</b><span>أدوات</span></div>
        </div>
        <div v-if="owned" class="progress">
          <div class="bar"><span :style="{ width: (done.length / p.days) * 100 + '%' }"></span></div>
          <small>خلّصت <span class="num">{{ done.length }}</span> من <span class="num">{{ p.days }}</span> يوم</small>
        </div>
        <div class="ctas">
          <RouterLink :to="`/workout/${p.id}/${owned ? nextDay : 1}`" class="btn btn-primary">{{ owned ? `كمّل: اليوم ${nextDay}` : 'جرّب اليوم الأول ببلاش' }}</RouterLink>
          <RouterLink v-if="!owned" :to="buyLink" class="btn btn-ghost">افتح البرنامج كله · <span class="num">{{ egp(plan.price) }}</span> ج.م</RouterLink>
        </div>
      </div>
    </header>

    <section class="wrap sec">
      <div class="sec-head"><h2>البرنامج <span class="hl">أسبوع أسبوع</span></h2></div>
      <div class="weeks">
        <div v-for="(w, i) in weeks" :key="w.n" class="week" :class="{ open: openWeek === i }">
          <button class="wk-h" :aria-expanded="openWeek === i" @click="openWeek = openWeek === i ? -1 : i">
            <span class="num wk-n">0{{ w.n }}</span>
            <span class="wk-t"><b>{{ w.t }}</b><small><span class="num">{{ w.days.length }}</span> أيام</small></span>
            <span class="wk-c num">{{ w.days.filter((d) => done.includes(d.n)).length }}/{{ w.days.length }}</span>
          </button>
          <ol v-if="openWeek === i" class="wk-days">
            <li v-for="d in w.days" :key="d.n">
              <RouterLink :to="`/workout/${p.id}/${d.n}`" class="day" :class="{ done: done.includes(d.n), locked: locked(d.n), rest: d.rest }">
                <span class="d-n num">{{ d.n }}</span>
                <span class="d-t">
                  <b>{{ d.rest ? 'راحة نشيطة' : 'اليوم ' + d.n }}</b>
                  <small class="en">{{ d.items.map((x) => x.title).join(' · ') }}</small>
                </span>
                <span class="d-s" aria-hidden="true">{{ done.includes(d.n) ? '✓' : locked(d.n) ? '🔒' : '▶' }}</span>
                <span class="sr-only">{{ done.includes(d.n) ? 'خلصان' : locked(d.n) ? 'مقفول' : 'متاح' }}</span>
              </RouterLink>
            </li>
          </ol>
        </div>
      </div>
      <p class="note" style="margin-top: 18px">لو عندك إصابة أو وجع شديد أو مستمر، اكشف عند دكتور قبل ما تبدأ.</p>
    </section>

    <!-- phones: primary action pinned above the tab bar -->
    <div class="m-cta">
      <RouterLink :to="`/workout/${p.id}/${owned ? nextDay : 1}`" class="btn btn-primary">{{ owned ? `▶ كمّل: اليوم ${nextDay}` : '▶ جرّب اليوم الأول' }}</RouterLink>
      <RouterLink v-if="!owned" :to="buyLink" class="btn btn-ghost buy"><span class="num">{{ egp(plan.price) }}</span> ج.م</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.ph { position: relative; overflow: hidden; isolation: isolate; padding-block: 28px 36px; border-bottom: 1px solid #1c1c1c; }
.ph-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; object-position: center 25%; filter: blur(2px) brightness(.35); transform: scale(1.05); }
.ph::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(to top, #0a0a0a 5%, transparent 70%); }
.ph::before { content: ''; position: absolute; inset: 0; z-index: -1; background: var(--dots); opacity: .3; mask: linear-gradient(120deg, #000, transparent 45%); -webkit-mask: linear-gradient(120deg, #000, transparent 45%); }
.ph-in { display: grid; gap: 12px; justify-items: start; }
.ph-in > * { max-width: 720px; }
.ph h1 { font-size: clamp(2.6rem, 10vw, 4.6rem); line-height: 1.05; }
.sub { color: var(--amber); font-weight: 800; }
.pitch { color: rgba(255,255,255,.85); max-width: 56ch; }
.stats { display: flex; gap: 26px; }
.stats div { display: grid; }
.stats b { font-size: 2.6rem; line-height: 1; color: var(--orange); justify-self: start; }
.stats span { font-size: .8rem; color: var(--muted); font-weight: 700; }
.progress { width: 100%; max-width: 420px; display: grid; gap: 6px; }
.bar { height: 8px; background: #222; border-radius: 99px; overflow: hidden; }
.bar span { display: block; height: 100%; background: var(--orange); }
.ctas { display: flex; flex-wrap: wrap; gap: 10px; width: 100%; }
.ctas .btn { flex: 1 1 240px; max-width: 360px; }

.weeks { display: grid; gap: 10px; max-width: 820px; }
.week { border: 1px solid #222; border-radius: 16px; background: #111; overflow: hidden; }
.week.open { border-color: #333; }
.wk-h { width: 100%; display: flex; align-items: center; gap: 14px; padding: 14px 16px; text-align: start; }
.wk-n { font-size: 2.2rem; line-height: 1; color: #555; }
.week.open .wk-n { color: var(--orange); }
.wk-t { display: grid; flex: 1; }
.wk-t b { font-family: var(--f-head); font-weight: 400; font-size: 1.3rem; }
.wk-t small { color: var(--muted); font-size: .8rem; }
.wk-c { font-size: 1.2rem; color: var(--muted); }
.wk-days { list-style: none; margin: 0; padding: 0 10px 10px; display: grid; gap: 6px; }
.day { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; background: #161616; border: 1px solid #222; transition: border-color .15s; }
.day:hover { border-color: var(--orange); }
.d-n { width: 40px; height: 40px; flex: none; border-radius: 10px; display: grid; place-items: center; font-size: 1.35rem; background: #222; }
.d-t { flex: 1; display: grid; min-width: 0; }
.d-t b { font-size: .95rem; }
.d-t small { color: var(--muted); font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; justify-self: start; max-width: 100%; }
.d-s { width: 34px; height: 34px; flex: none; display: grid; place-items: center; border-radius: 50%; background: var(--orange); color: #0a0a0a; font-size: .8rem; }
.day.done .d-n { background: var(--orange); color: #0a0a0a; }
.day.done .d-s { background: var(--ok); }
.day.locked { opacity: .6; }
.day.locked .d-s { background: #222; }
.day.rest .d-n { background: #1d2a1d; color: var(--ok); }
.m-cta { display: none; }
@media (max-width: 959px) {
  .ctas { display: none; }
  .m-cta { position: fixed; z-index: 40; inset-inline: 0; bottom: calc(76px + env(safe-area-inset-bottom)); display: flex; gap: 8px; padding: 10px var(--gutter); background: linear-gradient(to top, #0a0a0a 60%, transparent); }
  .m-cta .btn:first-child { flex: 1; }
  .m-cta .buy { flex: none; background: #141414; }
  .weeks { padding-bottom: 70px; }
}
</style>
