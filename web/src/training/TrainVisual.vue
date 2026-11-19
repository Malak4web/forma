<script setup>
// Signature visual per training system.
import { computed } from 'vue'
import { WD_SHORT, shortKey } from './data.js'

const props = defineProps({ t: Object, size: { type: Number, default: 260 } })
const t = computed(() => props.t)
const th = computed(() => t.value.theme)
const dim = computed(() => (th.value.mode === 'light' ? 'rgba(0,0,0,.1)' : 'rgba(255,255,255,.1)'))

/* split: one bar per weekday, coloured per day template */
const keys = computed(() => [...new Set(t.value.split.filter((k) => k !== 'rest'))])
const colOf = (k) => {
  const i = keys.value.indexOf(k)
  return i < 0 ? dim.value : [th.value.a, th.value.b, th.value.ink, th.value.a, th.value.b][i] || th.value.a
}
const opOf = (k) => (keys.value.indexOf(k) >= 3 ? 0.6 : 1)
const label = (k) => {
  const d = t.value.days[k]
  if (!d) return ''
  return shortKey(k)
}

/* grid: sets × reps dots */
const n = computed(() => t.value.gridN || 5)

/* interval ring: work vs rest */
const R = 78, C = 2 * Math.PI * R
const workFrac = computed(() => t.value.work / (t.value.work + t.value.rest))
const segs = computed(() => {
  const k = 8
  const per = C / k
  return Array.from({ length: k }, (_, i) => ({ off: -i * per, w: per * workFrac.value - 3, r: per * (1 - workFrac.value) - 3, i }))
})

/* ladder steps */
const steps = [1, 2, 3, 4, 5]
</script>

<template>
  <div class="tv" :style="{ width: size + 'px' }">
    <!-- SPLIT -->
    <svg v-if="t.visual === 'split'" viewBox="0 0 220 170" class="svg">
      <g v-for="(k, i) in t.split" :key="i">
        <rect :x="10 + i * 30" :y="k === 'rest' ? 112 : 26" width="22" :height="k === 'rest' ? 22 : 108" rx="8"
          :fill="k === 'rest' ? dim : colOf(k)" :opacity="k === 'rest' ? 1 : opOf(k)" />
        <text v-if="k !== 'rest'" :x="21 + i * 30" y="20" text-anchor="middle" class="en" :fill="th.ink">{{ label(k) }}</text>
        <text :x="21 + i * 30" y="152" text-anchor="middle" class="sm" :fill="th.ink">{{ WD_SHORT[i] }}</text>
      </g>
      <text x="110" y="168" text-anchor="middle" class="sm" :fill="th.a">{{ t.daysPerWeek }} أيام تمرين · {{ 7 - t.daysPerWeek }} راحة</text>
    </svg>

    <!-- GRID (5×5 / 10×10) -->
    <svg v-else-if="t.visual === 'grid'" viewBox="0 0 200 200" class="svg">
      <g v-for="r in n" :key="r">
        <circle v-for="c in n" :key="c" :cx="20 + (c - 0.5) * (160 / n)" :cy="20 + (r - 0.5) * (160 / n)" :r="n > 6 ? 5.2 : 11"
          :fill="r <= Math.ceil(n * 0.6) ? th.a : dim" class="dot" :style="{ animationDelay: (r * n + c) * 18 + 'ms' }" />
      </g>
      <rect x="54" y="78" width="92" height="44" rx="12" :fill="th.bg" opacity=".88" />
      <text x="100" y="112" text-anchor="middle" class="big" :fill="th.ink">{{ n }}×{{ n }}</text>
    </svg>

    <!-- INTERVAL ring -->
    <svg v-else-if="t.visual === 'interval'" viewBox="0 0 200 200" class="svg">
      <circle cx="100" cy="100" :r="R" fill="none" :stroke="dim" stroke-width="18" />
      <g transform="rotate(-90 100 100)">
        <template v-for="s in segs" :key="s.i">
          <circle cx="100" cy="100" :r="R" fill="none" :stroke="th.a" stroke-width="18" :stroke-dasharray="`${s.w} ${C}`" :stroke-dashoffset="s.off" />
          <circle cx="100" cy="100" :r="R" fill="none" :stroke="th.b" stroke-width="18" opacity=".55"
            :stroke-dasharray="`${s.r} ${C}`" :stroke-dashoffset="s.off - s.w - 3" />
        </template>
      </g>
      <text x="100" y="100" text-anchor="middle" class="big" :fill="th.ink">{{ t.work }}/{{ t.rest }}</text>
      <text x="100" y="124" text-anchor="middle" class="sm" :fill="th.a">ثانية شغل / راحة</text>
    </svg>

    <!-- LADDER (progressions) -->
    <svg v-else-if="t.visual === 'ladder'" viewBox="0 0 220 170" class="svg">
      <g v-for="s in steps" :key="s">
        <rect :x="10 + (s - 1) * 41" :y="150 - s * 26" width="36" :height="s * 26" rx="8" :fill="s === 5 ? th.b : th.a" :opacity="0.35 + s * 0.13" />
        <text :x="28 + (s - 1) * 41" :y="142 - s * 26" text-anchor="middle" class="en" :fill="th.ink">L{{ s }}</text>
      </g>
      <text x="110" y="166" text-anchor="middle" class="sm" :fill="th.ink">اطلع درجة لما تعدّي العدد</text>
    </svg>

    <!-- CLOCK (AMRAP / EMOM) -->
    <svg v-else-if="t.visual === 'clock'" viewBox="0 0 200 200" class="svg">
      <circle cx="100" cy="104" r="78" fill="none" :stroke="dim" stroke-width="10" />
      <path d="M100 26 A78 78 0 1 1 32 143" fill="none" :stroke="th.a" stroke-width="10" stroke-linecap="round" />
      <rect x="90" y="8" width="20" height="12" rx="3" :fill="th.ink" />
      <line v-for="m in 12" :key="m" x1="100" y1="34" x2="100" :y2="m % 3 === 0 ? 46 : 40" :transform="`rotate(${m * 30} 100 104)`" :stroke="th.ink" stroke-width="2" opacity=".5" />
      <line x1="100" y1="104" x2="100" y2="50" :stroke="th.b" stroke-width="4" stroke-linecap="round" class="hand" />
      <circle cx="100" cy="104" r="6" :fill="th.b" />
      <text x="100" y="146" text-anchor="middle" class="en" :fill="th.ink">AMRAP · EMOM</text>
    </svg>
  </div>
</template>

<style scoped>
.tv { max-width: 100%; }
.svg { width: 100%; height: auto; display: block; overflow: visible; }
.big { font-family: var(--f-num); font-size: 40px; direction: ltr; }
.en { font-family: var(--f-num); font-size: 12px; letter-spacing: .04em; direction: ltr; }
.sm { font-family: var(--f-body); font-size: 10.5px; font-weight: 800; }
.dot { animation: pop .5s both; transform-box: fill-box; transform-origin: center; }
@keyframes pop { from { transform: scale(0); } }
.hand { transform-origin: 100px 104px; animation: tick 6s steps(12) infinite; }
@keyframes tick { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .dot, .hand { animation: none; } }
</style>
