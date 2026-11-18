<script setup>
import { ref, computed, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import MAppBar from './MAppBar.vue'
import MSeg from './MSeg.vue'
import NotFound from '../pages/NotFound.vue'
import { programById, programDay, planById, meta, egp } from '../data.js'
import { store, hasAccess, doneDays } from '../stores/app.js'

const route = useRoute()
const p = computed(() => programById(route.params.id))
watchEffect(() => { if (p.value) document.title = `${p.value.title} | فورمة` })
const plan = computed(() => p.value && planById(p.value.plan))
const owned = computed(() => p.value && hasAccess(p.value.id))
const done = computed(() => (p.value ? doneDays(p.value.id) : []))
const nextDay = computed(() => {
  if (!p.value) return 1
  for (let d = 1; d <= p.value.days; d++) if (!done.value.includes(d)) return d
  return 1
})
const locked = (n) => !owned.value && n > meta.freeDays

// week tabs + one selected day — the whole program fits one screen
const weekCount = computed(() => (p.value ? Math.ceil(p.value.days / 7) : 0))
const week = ref(1)
const sel = ref(1)
watchEffect(() => { if (p.value) { sel.value = nextDay.value; week.value = Math.ceil(nextDay.value / 7) } })
const weekOptions = computed(() => Array.from({ length: weekCount.value }, (_, i) => ({ id: i + 1, label: `أسبوع ${i + 1}` })))
const weekDays = computed(() => {
  if (!p.value) return []
  const from = (week.value - 1) * 7 + 1
  return Array.from({ length: Math.min(7, p.value.days - from + 1) }, (_, i) => from + i)
})
function pickWeek(w) { week.value = w; sel.value = (w - 1) * 7 + 1 }
const day = computed(() => (p.value ? programDay(p.value, sel.value) : null))
const weekTitle = computed(() => p.value?.weeks[Math.min(week.value, p.value.weeks.length) - 1]?.t)
const R = 26, C = 2 * Math.PI * R
const buyLink = computed(() => (store.user ? `/checkout/${plan.value?.id}` : `/register?next=/checkout/${plan.value?.id}`))
</script>

<template>
  <NotFound v-if="!p" />
  <div v-else class="pg">
    <header class="hero">
      <img :src="p.img" alt="" />
      <div class="m-halftone"></div>
      <MAppBar transparent fallback="/programs">
        <svg class="m-ring" viewBox="0 0 64 64" width="54" height="54" :aria-label="`خلّصت ${done.length} من ${p.days}`">
          <circle class="tk" cx="32" cy="32" :r="R" />
          <circle class="pg" cx="32" cy="32" :r="R" :stroke-dasharray="C" :stroke-dashoffset="C * (1 - done.length / p.days)" />
          <text x="32" y="37" text-anchor="middle">{{ done.length }}/{{ p.days }}</text>
        </svg>
      </MAppBar>
      <div class="hero-b">
        <span v-if="p.tag" class="m-tag">{{ p.tag }}</span>
        <h1>{{ p.title }}</h1>
        <small class="amber"><span class="num">{{ p.mins }}</span> د/يوم · {{ p.sub }}</small>
      </div>
    </header>

    <div class="weeks"><MSeg :model-value="week" :options="weekOptions" @update:model-value="pickWeek" /></div>

    <div class="m-pad">
      <small class="muted wt">{{ weekTitle }} · <span class="num">{{ programDay(p, weekDays[0] || 1).items[0]?.work }}</span>ث شغل / <span class="num">{{ programDay(p, weekDays[0] || 1).items[0]?.rest }}</span>ث راحة</small>
      <div class="days">
        <button
          v-for="n in weekDays" :key="n" class="d"
          :class="{ done: done.includes(n), sel: sel === n, lock: locked(n), rest: n % 7 === 0 }"
          :aria-label="`اليوم ${n}`" @click="sel = n"
        >
          <span v-if="locked(n) && !done.includes(n)" class="lk">🔒</span>
          <span v-else-if="n % 7 === 0 && !done.includes(n)">😌</span>
          <span v-else class="num">{{ n }}</span>
        </button>
      </div>
    </div>

    <section v-if="day" class="m-pad dayb">
      <header class="dh">
        <h2>{{ day.rest ? 'راحة نشيطة' : `اليوم ${sel}` }}</h2>
        <span class="num muted">{{ day.items.length }} × {{ day.rounds }}</span>
      </header>
      <div class="m-stack">
        <RouterLink v-for="(it, i) in day.items" :key="it.id" :to="`/library/${it.id}`" class="m-li">
          <img :src="it.img" alt="" />
          <span class="x"><b class="en">{{ it.title }}</b><small>{{ it.muscles }}</small></span>
          <span class="num hl idx">{{ i + 1 }}</span>
        </RouterLink>
      </div>
    </section>

    <div class="m-cta">
      <RouterLink v-if="!locked(sel)" :to="`/workout/${p.id}/${sel}`" class="btn btn-primary">▶ {{ done.includes(sel) ? `عيد اليوم ${sel}` : `ابدأ اليوم ${sel}` }}</RouterLink>
      <RouterLink v-else :to="buyLink" class="btn btn-primary">🔓 افتح البرنامج · <span class="num">{{ egp(plan?.price || 0) }}</span> ج.م</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.pg { min-height: 100dvh; padding-bottom: var(--m-cta); }
.hero { position: relative; height: 200px; display: flex; flex-direction: column; justify-content: flex-end; padding: 0 16px 14px; isolation: isolate; overflow: hidden; }
.hero > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 30%; filter: brightness(.45); z-index: -2; }
.hero .m-halftone { z-index: -1; -webkit-mask: linear-gradient(120deg, #000, transparent 50%); mask: linear-gradient(120deg, #000, transparent 50%); }
.hero::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(to top, #0a0a0a, transparent 60%); }
.hero-b { display: grid; gap: 2px; }
.hero-b .m-tag { justify-self: start; }
.hero-b h1 { font-size: 2.2rem; line-height: 1.1; }
.hero-b small { font-weight: 800; }
.weeks { margin: 12px 16px 0; overflow-x: auto; scrollbar-width: none; }
.weeks::-webkit-scrollbar { display: none; }
.weeks :deep(.mseg) { min-width: max-content; }
.weeks :deep(.mseg button) { padding-inline: 12px; }
.wt { display: block; font-weight: 800; font-size: .8rem; margin-top: 12px; }
.days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-top: 10px; }
.d { aspect-ratio: 1; border-radius: 12px; background: var(--surface-2); color: #8a8a8a; display: grid; place-items: center; font-size: 1.2rem; transition: transform .1s; }
.d:active { transform: scale(.92); }
.d.done { background: var(--orange); color: #0a0a0a; }
.d.rest:not(.done) { background: #1d2a1d; font-size: .9rem; }
.d.lock:not(.done) { opacity: .55; font-size: .8rem; }
.d.sel { outline: 2px solid #fff; outline-offset: 0; box-shadow: 3px 3px 0 var(--orange); color: #fff; }
.d.sel.done { color: #0a0a0a; }
.dayb { padding-top: 18px; }
.dh { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 10px; }
.dh h2 { font-size: 1.4rem; }
.idx { font-size: 1.5rem; padding-inline: 6px; }
</style>
