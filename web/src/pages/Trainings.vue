<script setup>
import { ref, computed } from 'vue'
import MAppBar from '../mobile/MAppBar.vue'
import { MOBILE } from '../mobile/device.js'
import { TRAININGS, T_GOALS, PLACE } from '../training/data.js'
import { motifBg } from '../diets/motifs.js'

const goal = ref('all')
const place = ref('all')
const list = computed(() => TRAININGS.filter((t) => (goal.value === 'all' || t.goal.includes(goal.value)) && (place.value === 'all' || t.place === place.value)))
const cardStyle = (t) => ({
  '--bg': t.theme.bg, '--sf': t.theme.surface, '--ink': t.theme.ink, '--a': t.theme.a, '--b': t.theme.b,
  backgroundImage: motifBg(t.theme.motif, t.theme.a, t.theme.mode === 'light' ? 0.22 : 0.16),
})
const week = (t) => t.split.map((k) => k !== 'rest')
</script>

<template>
  <div class="tr" :class="{ mob: MOBILE }">
    <MAppBar v-if="MOBILE" title="أنظمة التدريب" fallback="/" />

    <header class="hero">
      <div class="wrap hero-in">
        <span class="kicker">كل نظام = برنامج 4 أسابيع بالتفصيل</span>
        <h1>أنظمة التدريب <span class="hl">المشهورة</span><br />اختار اللي على مقاسك</h1>
        <p>فول بادي، بوش بول ليجز، 5×5، HIIT، كاليسثنكس… كل نظام بصفحته: بيشتغل إزاي، مناسب لمين، و<b>كل يوم بتمارينه ومجموعاته وعدّاته والراحة</b>.</p>
        <div class="chips" role="tablist" aria-label="الهدف">
          <button v-for="g in T_GOALS" :key="g.id" role="tab" :aria-selected="goal === g.id" :class="{ on: goal === g.id }" @click="goal = g.id"><span>{{ g.label }}</span></button>
        </div>
        <div class="place" role="tablist" aria-label="المكان">
          <button :class="{ on: place === 'all' }" @click="place = 'all'">أي مكان</button>
          <button :class="{ on: place === 'gym' }" @click="place = 'gym'">🏋️ جيم</button>
          <button :class="{ on: place === 'home' }" @click="place = 'home'">🏠 من البيت</button>
        </div>
      </div>
    </header>

    <section class="wrap grid">
      <RouterLink v-for="t in list" :key="t.slug" :to="`/training/${t.slug}`" class="card" :class="t.theme.mode" :style="cardStyle(t)">
        <div class="top">
          <span class="emoji" aria-hidden="true">{{ t.emoji }}</span>
          <span class="days"><span class="num">{{ t.daysPerWeek }}</span> أيام/أسبوع</span>
        </div>
        <span class="en">{{ t.en }}</span>
        <h2>{{ t.name }}</h2>
        <p>{{ t.tagline }}</p>
        <div class="wk" :aria-label="`${t.daysPerWeek} أيام تمرين في الأسبوع`">
          <i v-for="(on, i) in week(t)" :key="i" :class="{ on }"></i>
        </div>
        <div class="legend">
          <span>⏱ <b class="num">{{ t.minutes }}</b> د</span>
          <span>📍 {{ PLACE[t.place] }}</span>
        </div>
        <div class="foot">
          <span class="lvl">{{ t.level }} <i v-for="n in 4" :key="n" :class="{ on: n <= t.levelN }"></i></span>
          <span class="go">افتح ‹</span>
        </div>
      </RouterLink>
      <p v-if="!list.length" class="empty">مفيش نظام بالفلتر ده — جرّب «أي مكان».</p>
    </section>

    <section class="wrap help">
      <div class="help-in">
        <span class="h-ic">🧭</span>
        <div><b>لسه بادئ ومش عارف تختار؟</b><small>ابدأ بـ «فول بادي» لو في جيم، أو «تحدّي 30 يوم» لو من البيت.</small></div>
        <RouterLink to="/training/full-body" class="btn btn-primary btn-sm">فول بادي</RouterLink>
      </div>
      <p class="note">البرامج دي إرشادية. سخّن قبل أي تمرين، ولو عندك إصابة أو مرض مزمن اكشف عند دكتور قبل ما تبدأ.</p>
    </section>
  </div>
</template>

<style scoped>
.tr { padding-bottom: 60px; }
.tr.mob { padding-bottom: 30px; }
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
.place { display: flex; gap: 2px; padding: 4px; border-radius: 12px; background: #141414; border: 1px solid #262626; max-width: 380px; }
.place button { flex: 1; min-height: 36px; border-radius: 9px; font-weight: 800; font-size: .82rem; color: var(--muted); }
.place button.on { background: #262626; color: #fff; }

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
.days { font-weight: 900; font-size: .72rem; padding: 3px 10px; border-radius: 99px; background: var(--a); color: #0a0a0a; }
.days .num { font-size: 1rem; }
.card .en { font-family: var(--f-num); font-size: 1rem; letter-spacing: .2em; color: var(--a); margin-top: 6px; align-self: flex-start; direction: ltr; }
.card h2 { font-size: 2rem; line-height: 1.05; }
.card p { font-size: .88rem; opacity: .85; line-height: 1.7; flex: 1; }
.wk { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-top: 4px; }
.wk i { height: 8px; border-radius: 4px; background: color-mix(in srgb, var(--ink) 16%, transparent); }
.wk i.on { background: var(--a); }
.legend { display: flex; gap: 14px; font-size: .76rem; font-weight: 700; opacity: .9; }
.legend b { font-size: 1rem; }
.foot { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 10px; border-top: 1px solid color-mix(in srgb, var(--ink) 14%, transparent); font-size: .78rem; font-weight: 800; }
.lvl { display: inline-flex; align-items: center; gap: 4px; }
.lvl i { width: 14px; height: 6px; border-radius: 3px; background: color-mix(in srgb, var(--ink) 20%, transparent); }
.lvl i.on { background: var(--a); }
.go { font-family: var(--f-head); font-size: 1.1rem; color: var(--a); }
.empty { color: var(--muted); }

.help { padding-top: 24px; display: grid; gap: 12px; }
.help-in { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 16px; border-radius: 20px; background: #141414; border: 1px solid #262626; }
.h-ic { font-size: 1.8rem; }
.help-in div { flex: 1; min-width: 200px; display: grid; }
.help-in small { color: var(--muted); }
.note { color: var(--muted); font-size: .82rem; }
</style>
