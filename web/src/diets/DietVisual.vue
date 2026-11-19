<script setup>
// Signature visual per diet — the one graphic that tells you which diet you're looking at.
import { computed } from 'vue'

const props = defineProps({ diet: Object, size: { type: Number, default: 260 } })
const d = computed(() => props.diet)
const t = computed(() => d.value.theme)

/* macro donut */
const R = 80, C = 2 * Math.PI * R
const segs = computed(() => {
  if (!d.value.macros) return []
  const { c, p, f } = d.value.macros
  const list = [
    { k: 'f', v: f, label: 'دهون', col: t.value.a },
    { k: 'p', v: p, label: 'بروتين', col: t.value.b },
    { k: 'c', v: c, label: 'كارب', col: t.value.mode === 'light' ? '#c9d3dc' : 'rgba(255,255,255,.35)' },
  ].filter((x) => x.v > 0)
  let acc = 0
  return list.map((x) => { const s = { ...x, dash: (x.v / 100) * C, off: -acc }; acc += (x.v / 100) * C; return s })
})
const lead = computed(() => [...segs.value].sort((a, b) => b.v - a.v)[0] || { v: 0, label: '' })

/* fasting dial: 24h, eating window highlighted */
const win = computed(() => d.value.window || { from: 12, to: 20 })
const arc = (fromH, toH, r) => {
  const a0 = (fromH / 24) * 2 * Math.PI - Math.PI / 2, a1 = (toH / 24) * 2 * Math.PI - Math.PI / 2
  const large = (toH - fromH) % 24 > 12 ? 1 : 0
  return `M ${100 + r * Math.cos(a0)} ${100 + r * Math.sin(a0)} A ${r} ${r} 0 ${large} 1 ${100 + r * Math.cos(a1)} ${100 + r * Math.sin(a1)}`
}
const ticks = Array.from({ length: 24 }, (_, h) => h)

/* carb gauge */
const gaugeMax = 300
const gaugePct = computed(() => Math.min(1, (d.value.carbs || 100) / gaugeMax))

/* cycle bars */
const H = { high: 90, mid: 58, low: 30 }
const days = ['س', 'ح', 'ن', 'ث', 'ر', 'خ', 'ج']
</script>

<template>
  <div class="dv" :style="{ width: size + 'px', '--a': t.a, '--b': t.b, '--ink': t.ink }">
    <!-- MACRO DONUT -->
    <svg v-if="d.visual === 'macro'" viewBox="0 0 200 200" class="svg">
      <circle cx="100" cy="100" :r="R" fill="none" :stroke="t.mode === 'light' ? '#e6ecf1' : 'rgba(255,255,255,.08)'" stroke-width="26" />
      <circle v-for="s in segs" :key="s.k" cx="100" cy="100" :r="R" fill="none" :stroke="s.col" stroke-width="26"
        :stroke-dasharray="`${s.dash} ${C}`" :stroke-dashoffset="s.off" transform="rotate(-90 100 100)" />
      <text x="100" y="98" text-anchor="middle" class="big" :fill="t.ink">{{ lead.v }}%</text>
      <text x="100" y="124" text-anchor="middle" class="lbl" :fill="t.ink">{{ lead.label }}</text>
    </svg>

    <!-- FASTING DIAL -->
    <svg v-else-if="d.visual === 'dial'" viewBox="0 0 200 200" class="svg">
      <circle cx="100" cy="100" r="84" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="18" />
      <path :d="arc(win.to, win.from + 24, 84)" fill="none" stroke="rgba(122,162,255,.35)" stroke-width="18" />
      <path :d="arc(win.from, win.to, 84)" fill="none" :stroke="t.b" stroke-width="18" stroke-linecap="round" />
      <line v-for="h in ticks" :key="h" x1="100" y1="22" x2="100" :y2="h % 6 === 0 ? 30 : 26" :transform="`rotate(${h * 15} 100 100)`" stroke="rgba(255,255,255,.4)" stroke-width="1.5" />
      <text x="100" y="92" text-anchor="middle" class="big" :fill="t.ink">16:8</text>
      <text x="100" y="116" text-anchor="middle" class="lbl" :fill="t.b">شباك الأكل</text>
      <text x="100" y="134" text-anchor="middle" class="sm" :fill="t.ink">{{ win.from }}:00 → {{ win.to }}:00</text>
    </svg>

    <!-- CARB GAUGE -->
    <svg v-else-if="d.visual === 'gauge'" viewBox="0 0 200 140" class="svg">
      <path d="M20 120 A80 80 0 0 1 180 120" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="20" stroke-linecap="round" />
      <path d="M20 120 A80 80 0 0 1 180 120" fill="none" :stroke="t.a" stroke-width="20" stroke-linecap="round"
        :stroke-dasharray="`${gaugePct * 251} 300`" />
      <line x1="100" y1="120" x2="100" y2="54" :stroke="t.ink" stroke-width="4" stroke-linecap="round" :transform="`rotate(${gaugePct * 180 - 90} 100 120)`" />
      <circle cx="100" cy="120" r="7" :fill="t.ink" />
      <text x="100" y="98" text-anchor="middle" class="big" :fill="t.ink">{{ d.carbs }}g</text>
      <text x="20" y="138" text-anchor="middle" class="sm" :fill="t.ink">0</text>
      <text x="180" y="138" text-anchor="middle" class="sm" :fill="t.ink">300g</text>
    </svg>

    <!-- PLATE -->
    <svg v-else-if="d.visual === 'plate'" viewBox="0 0 200 200" class="svg">
      <circle cx="100" cy="100" r="88" :fill="t.mode === 'light' ? '#fff' : 'rgba(255,255,255,.06)'" stroke="rgba(255,255,255,.25)" stroke-width="3" />
      <circle cx="100" cy="100" r="70" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="2" />
      <path d="M100 100 L100 30 A70 70 0 1 1 39 135 Z" :fill="t.a" opacity=".9" />
      <path d="M100 100 L39 135 A70 70 0 0 1 100 30 Z" :fill="t.b" opacity=".85" />
      <text x="128" y="108" text-anchor="middle" class="lbl" fill="#fff">{{ d.macros.p + d.macros.f }}%</text>
      <text x="70" y="88" text-anchor="middle" class="sm" fill="#1a1a1a">{{ d.slug === 'carnivore' ? 'لحوم' : 'خضار' }}</text>
    </svg>

    <!-- PROTEIN -->
    <div v-else-if="d.visual === 'protein'" class="protein">
      <svg viewBox="0 0 200 70" class="bar">
        <rect x="10" y="30" width="180" height="10" rx="5" fill="rgba(255,255,255,.35)" />
        <rect x="18" y="8" width="16" height="54" rx="4" :fill="t.a" /><rect x="38" y="14" width="12" height="42" rx="4" :fill="t.a" opacity=".8" />
        <rect x="166" y="8" width="16" height="54" rx="4" :fill="t.a" /><rect x="150" y="14" width="12" height="42" rx="4" :fill="t.a" opacity=".8" />
      </svg>
      <b class="num">{{ d.protein }}<small>g</small></b>
      <span>بروتين في اليوم لشخص 80 كيلو</span>
    </div>

    <!-- PULSE -->
    <svg v-else-if="d.visual === 'pulse'" viewBox="0 0 200 160" class="svg">
      <path d="M100 140 C40 100 10 70 30 40 C48 14 84 20 100 46 C116 20 152 14 170 40 C190 70 160 100 100 140Z" fill="none" :stroke="t.a" stroke-width="5" />
      <path d="M20 84 H70 L80 62 L92 108 L104 70 L112 84 H180" fill="none" :stroke="t.b" stroke-width="4" stroke-linejoin="round" stroke-linecap="round" class="beat" />
      <text x="100" y="156" text-anchor="middle" class="sm" :fill="t.ink">صوديوم أقل من {{ d.sodium }} مجم</text>
    </svg>

    <!-- STAR (tayyibat): 8-point star, allowed vs forbidden -->
    <svg v-else-if="d.visual === 'star'" viewBox="0 0 200 200" class="svg">
      <g :stroke="t.a" fill="none" stroke-width="3">
        <rect x="45" y="45" width="110" height="110" rx="6" />
        <rect x="45" y="45" width="110" height="110" rx="6" transform="rotate(45 100 100)" />
      </g>
      <circle cx="100" cy="100" r="44" :fill="t.surface" :stroke="t.b" stroke-width="2" />
      <text x="100" y="94" text-anchor="middle" class="lbl" :fill="t.a">طيّب</text>
      <text x="100" y="118" text-anchor="middle" class="sm" :fill="t.ink">مسموح وممنوع</text>
    </svg>

    <!-- CYCLE -->
    <svg v-else-if="d.visual === 'cycle'" viewBox="0 0 200 140" class="svg">
      <g v-for="(c, i) in d.cycle" :key="i">
        <rect :x="14 + i * 26" :y="110 - H[c]" width="18" :height="H[c]" rx="9" :fill="c === 'high' ? t.b : c === 'mid' ? t.a : 'rgba(255,255,255,.25)'" />
        <text :x="23 + i * 26" y="128" text-anchor="middle" class="sm" :fill="t.ink">{{ days[i] }}</text>
      </g>
    </svg>
  </div>
</template>

<style scoped>
.dv { aspect-ratio: auto; max-width: 100%; }
.svg { width: 100%; height: auto; display: block; overflow: visible; }
.big { font-family: var(--f-num); font-size: 44px; direction: ltr; }
.lbl { font-family: var(--f-head); font-size: 18px; }
.sm { font-family: var(--f-body); font-size: 11px; font-weight: 800; }
.beat { stroke-dasharray: 260; stroke-dashoffset: 260; animation: beat 2.4s ease-in-out infinite; }
@keyframes beat { 40%, 100% { stroke-dashoffset: 0; } }
.protein { display: grid; justify-items: center; gap: 2px; color: var(--ink); }
.protein .bar { width: 100%; }
.protein b { font-size: 5.4rem; line-height: .9; color: var(--a); }
.protein small { font-size: .45em; color: var(--ink); }
.protein span { font-size: .8rem; font-weight: 800; opacity: .8; }
</style>
