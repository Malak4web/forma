<script setup>
import { ref, computed } from 'vue'
import MAppBar from '../mobile/MAppBar.vue'
import { MOBILE } from '../mobile/device.js'
import { DIETS, GOALS } from '../diets/data.js'
import { motifBg } from '../diets/motifs.js'

const goal = ref('all')
const list = computed(() => (goal.value === 'all' ? DIETS : DIETS.filter((d) => d.goal.includes(goal.value))))
const cardStyle = (d) => ({
  '--bg': d.theme.bg, '--sf': d.theme.surface, '--ink': d.theme.ink, '--a': d.theme.a, '--b': d.theme.b,
  backgroundImage: motifBg(d.theme.motif, d.theme.a, d.theme.mode === 'light' ? 0.22 : 0.16),
})
</script>

<template>
  <div class="diets" :class="{ mob: MOBILE }">
    <MAppBar v-if="MOBILE" title="أنظمة الأكل" fallback="/" />

    <header class="hero">
      <div class="wrap hero-in">
        <span class="kicker">كل نظام = خطة 30 يوم كاملة</span>
        <h1>أنظمة الأكل <span class="hl">المعروفة</span><br />في مكان واحد</h1>
        <p>كيتو، صيام متقطع، لو كارب، كارنيفور، متوسطي، نباتي… كل نظام بصفحته، بيشرحلك بيشتغل إزاي، تاكل إيه وتبعد عن إيه، و<b>30 يوم أكل متنوع</b> جاهزين.</p>
        <div class="chips" role="tablist">
          <button v-for="g in GOALS" :key="g.id" role="tab" :aria-selected="goal === g.id" :class="{ on: goal === g.id }" @click="goal = g.id"><span>{{ g.label }}</span></button>
        </div>
      </div>
    </header>

    <section class="wrap grid">
      <RouterLink v-for="(d, i) in list" :key="d.slug" :to="`/diets/${d.slug}`" class="card" :class="[d.theme.mode, `v${i % 3}`]" :style="cardStyle(d)">
        <div class="top">
          <span class="emoji" aria-hidden="true">{{ d.emoji }}</span>
          <span class="days"><span class="num">30</span> يوم</span>
        </div>
        <span class="en">{{ d.en }}</span>
        <h2>{{ d.name }}</h2>
        <p>{{ d.tagline }}</p>
        <div v-if="d.macros" class="macros" :aria-label="`كارب ${d.macros.c}% بروتين ${d.macros.p}% دهون ${d.macros.f}%`">
          <i class="c" :style="{ flex: d.macros.c || 0.001 }"></i><i class="p" :style="{ flex: d.macros.p }"></i><i class="f" :style="{ flex: d.macros.f }"></i>
        </div>
        <div v-if="!d.macros" class="legend"><span>✓ <b class="num">{{ d.eat.length }}</b> مجموعات مسموحة</span><span>✕ <b class="num">{{ d.avoid.length }}</b> ممنوعة</span></div>
        <div v-else class="legend">
          <span><b class="num">{{ d.macros.c }}%</b> كارب</span><span><b class="num">{{ d.macros.p }}%</b> بروتين</span><span><b class="num">{{ d.macros.f }}%</b> دهون</span>
        </div>
        <div class="foot">
          <span class="lvl">الصعوبة <i v-for="n in 4" :key="n" :class="{ on: n <= d.levelN }"></i></span>
          <span class="go">افتح ‹</span>
        </div>
      </RouterLink>
    </section>

    <section class="wrap help">
      <div class="help-in">
        <span class="h-ic">🤔</span>
        <div><b>مش عارف تختار أنهي نظام؟</b><small>احسب سعراتك الأول وخد نظام مبدئي على قدّك، وبعدين جرّب اللي يناسبك.</small></div>
        <RouterLink to="/calculator" class="btn btn-primary btn-sm">احسب ببلاش</RouterLink>
      </div>
      <p class="note">كل الأرقام هنا تقريبية ومحسوبة على حوالي 1800 سعرة. لو عندك أي مرض مزمن أو حامل، اكشف عند دكتور قبل ما تبدأ أي نظام.</p>
    </section>
  </div>
</template>

<style scoped>
.diets { padding-bottom: 60px; }
.diets.mob { padding-bottom: 30px; }
.hero { position: relative; overflow: hidden; padding: 36px 0 18px; background: radial-gradient(900px 400px at 85% -10%, rgba(255,106,0,.18), transparent 60%); }
.mob .hero { padding-top: 6px; }
.hero-in { display: grid; gap: 12px; }
.hero h1 { font-size: clamp(2.3rem, 8vw, 4rem); line-height: 1.1; }
.hero p { color: var(--muted); max-width: 62ch; }
.hero p b { color: #fff; }
.chips { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; margin-inline: calc(var(--gutter) * -1); padding: 4px var(--gutter); }
.chips::-webkit-scrollbar { display: none; }
.chips button { flex: none; padding: 8px 16px; border-radius: 10px; border: 1.5px solid rgba(255,255,255,.18); font-weight: 800; font-size: .88rem; transform: skewX(-8deg); }
.chips button > span { display: inline-block; transform: skewX(8deg); }
.chips button.on { background: var(--orange); border-color: var(--orange); color: #0a0a0a; }

.grid { display: grid; gap: 14px; padding-top: 18px; }
@media (min-width: 700px) { .grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1080px) { .grid { grid-template-columns: repeat(3, 1fr); } }
.card { position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 6px; padding: 18px; border-radius: 24px; background-color: var(--bg); color: var(--ink); border: 1px solid color-mix(in srgb, var(--a) 30%, transparent); transition: transform .2s, box-shadow .2s; isolation: isolate; }
.card::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(160deg, transparent 30%, color-mix(in srgb, var(--a) 22%, transparent)); }
.card:hover { transform: translateY(-4px); box-shadow: 0 18px 40px -20px var(--a); }
.card:active { transform: scale(.985); }
.card.light { box-shadow: 0 10px 30px -18px rgba(0,0,0,.6); }
.top { display: flex; justify-content: space-between; align-items: flex-start; }
.emoji { font-size: 2.6rem; line-height: 1; filter: drop-shadow(0 6px 12px rgba(0,0,0,.35)); }
.days { font-weight: 900; font-size: .75rem; padding: 3px 10px; border-radius: 99px; background: var(--a); color: #0a0a0a; }
.days .num { font-size: 1rem; }
.card .en { font-size: 1rem; letter-spacing: .25em; color: var(--a); margin-top: 6px; justify-self: start; align-self: flex-start; }
.card h2 { font-size: 2rem; line-height: 1.05; }
.card p { font-size: .88rem; opacity: .85; line-height: 1.7; flex: 1; }
.macros { display: flex; height: 8px; border-radius: 9px; overflow: hidden; gap: 2px; margin-top: 6px; }
.macros i { display: block; }
.macros .c { background: color-mix(in srgb, var(--ink) 35%, transparent); }
.macros .p { background: var(--b); }
.macros .f { background: var(--a); }
.legend { display: flex; gap: 12px; font-size: .74rem; font-weight: 700; opacity: .9; }
.legend b { font-size: 1rem; }
.foot { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 10px; border-top: 1px solid color-mix(in srgb, var(--ink) 14%, transparent); font-size: .78rem; font-weight: 800; }
.lvl { display: inline-flex; align-items: center; gap: 4px; }
.lvl i { width: 14px; height: 6px; border-radius: 3px; background: color-mix(in srgb, var(--ink) 20%, transparent); }
.lvl i.on { background: var(--a); }
.go { font-family: var(--f-head); font-size: 1.1rem; color: var(--a); }
.card.light .go, .card.light .card .en { filter: saturate(1.2) brightness(.85); }

.help { padding-top: 24px; display: grid; gap: 12px; }
.help-in { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 16px; border-radius: 20px; background: #141414; border: 1px solid #262626; }
.h-ic { font-size: 1.8rem; }
.help-in div { flex: 1; min-width: 200px; display: grid; }
.help-in small { color: var(--muted); }
</style>
