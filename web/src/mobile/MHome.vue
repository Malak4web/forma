<script setup>
import { computed } from 'vue'
import MAppBar from './MAppBar.vue'
import MRail from './MRail.vue'
import TransformationsMarquee from '../components/TransformationsMarquee.vue'
import { videos, programs, programDay, plans } from '../data.js'
import { store, nextWorkout, doneDays, hasAccess, greeting } from '../stores/app.js'

const next = computed(() => nextWorkout())
const nextDay = computed(() => (next.value ? programDay(next.value.program, next.value.day) : null))
const p = computed(() => next.value?.program)
const done = computed(() => (p.value ? doneDays(p.value.id) : []))
const R = 26, C = 2 * Math.PI * R
const pct = computed(() => (p.value ? done.value.length / p.value.days : 0))

// the current program week as a 7-day strip
const week = computed(() => {
  if (!p.value || !next.value) return []
  const w = Math.ceil(next.value.day / 7)
  const from = (w - 1) * 7 + 1
  return Array.from({ length: Math.min(7, p.value.days - from + 1) }, (_, i) => {
    const n = from + i
    return { n, done: done.value.includes(n), today: n === next.value.day, rest: n % 7 === 0 }
  })
})
const firstName = computed(() => store.user?.name?.split(' ')[0])
const lastKg = computed(() => store.weights.at(-1)?.kg)
const diff = computed(() => (store.weights.length > 1 ? +(store.weights.at(-1).kg - store.weights[0].kg).toFixed(1) : null))
const totalDone = computed(() => Object.values(store.user ? store.completed : store.guestCompleted).reduce((n, d) => n + d.length, 0))

const library = computed(() => videos.filter((v) => v.home).slice(0, 8))
const pro = computed(() => plans.find((x) => x.highlight) || plans.at(-1))
const goals = [
  { cat: 'belly', label: 'الكرش', img: 'climbers' },
  { cat: 'legs', label: 'الرجل', img: 'squat' },
  { cat: 'upper', label: 'الصدر', img: 'pushups' },
  { cat: 'pain', label: 'الضهر', img: 'toetouch' },
  { cat: 'cardio', label: 'كارديو', img: 'jacks' },
  { cat: 'food', label: 'الأكل', img: 'koshari' },
]
</script>

<template>
  <div class="m-screen with-tabs home">
    <!-- app bar -->
    <MAppBar :back="false">
      <template #start>
        <div v-if="store.user" class="hi">
          <small>{{ greeting() }}{{ firstName ? ` يا ${firstName}` : '' }} 👊</small>
          <b>{{ next?.started ? 'نكمّل؟' : 'يلا نبدأ؟' }}</b>
        </div>
        <RouterLink v-else to="/" class="logo">
          <img src="/img/logo-icon.webp" alt="" />
          <span><b>فورمة</b><i class="en">FORMA</i></span>
        </RouterLink>
      </template>
      <RouterLink to="/library" class="mib" aria-label="دوّر">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
      </RouterLink>
      <RouterLink v-if="store.user" to="/account" class="m-avatar" aria-label="حسابي">{{ store.user.name.charAt(0) }}</RouterLink>
      <RouterLink v-else to="/login" class="mib" aria-label="دخول">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" /><path d="M4 21c1.2-4 4.2-6 8-6s6.8 2 8 6" /></svg>
      </RouterLink>
    </MAppBar>

    <!-- ========== member: today ========== -->
    <template v-if="store.user && p && nextDay">
      <div class="m-pad">
        <RouterLink :to="`/workout/${p.id}/${next.day}`" class="today">
          <img :src="nextDay.items[0]?.img || p.img" alt="" />
          <div class="today-top">
            <span class="pill">{{ p.title }}</span>
            <svg class="m-ring" viewBox="0 0 64 64" width="60" height="60" :aria-label="`خلّصت ${done.length} من ${p.days}`">
              <circle class="tk" cx="32" cy="32" :r="R" />
              <circle class="pg" cx="32" cy="32" :r="R" :stroke-dasharray="C" :stroke-dashoffset="C * (1 - pct)" />
              <text x="32" y="37" text-anchor="middle">{{ done.length }}/{{ p.days }}</text>
            </svg>
          </div>
          <div class="today-b">
            <small class="amber">الأسبوع <span class="num">{{ nextDay.week }}</span> · {{ nextDay.title }}</small>
            <h2>اليوم <span class="num hl">{{ next.day }}</span></h2>
            <small class="en moves">{{ nextDay.items.map((i) => i.title).join(' · ') }}</small>
          </div>
        </RouterLink>
        <RouterLink :to="`/workout/${p.id}/${next.day}`" class="btn btn-primary go-btn">▶ ابدأ تمرين النهارده</RouterLink>
      </div>

      <section class="m-pad blk">
        <header class="bh"><h2>أسبوعك</h2><span class="num cnt">WEEK {{ nextDay.week }}</span></header>
        <div class="week">
          <RouterLink v-for="d in week" :key="d.n" :to="`/programs/${p.id}`" class="wd" :class="{ done: d.done, today: d.today, rest: d.rest }">
            <span v-if="d.rest && !d.done">😌</span><span v-else class="num">{{ d.n }}</span>
          </RouterLink>
        </div>
      </section>

      <section class="m-pad blk">
        <header class="bh"><h2>أرقامك</h2></header>
        <div class="stats">
          <RouterLink to="/account" class="m-card m-stat"><b class="num">{{ totalDone }}</b><span>يوم خلّصته</span></RouterLink>
          <RouterLink to="/account" class="m-card m-stat"><b class="num">{{ lastKg ?? '—' }}</b><span>وزنك</span></RouterLink>
          <RouterLink to="/account" class="m-card m-stat"><b class="num" :class="{ amber: diff !== null && diff <= 0 }">{{ diff === null ? '—' : (diff > 0 ? '+' : '') + diff }}</b><span>من البداية</span></RouterLink>
        </div>
      </section>

      <RouterLink v-if="!hasAccess(p.id)" to="/pricing" class="upsell m-pad">
        <span>🔓</span><div><b>افتح البرنامج كله</b><small>أول 3 أيام ببلاش — كمّل من غير ما تقف</small></div><i>‹</i>
      </RouterLink>
    </template>

    <!-- ========== guest: banners + goals + today ========== -->
    <template v-else>
      <MRail :dots="true" :gap="10" class="banners">
        <RouterLink to="/programs/30" class="ban">
          <img src="/img/banners/challenge-640.webp" srcset="/img/banners/challenge-640.webp 640w, /img/banners/challenge-960.webp 960w" sizes="340px" alt="" class="bg" fetchpriority="high" />
          <span class="m-tag">الأكتر طلبًا</span>
          <h3>تحدّي الـ <span class="num hl">30</span> يوم</h3>
          <small>من البيت · أول 3 أيام ببلاش</small>
          <span class="mini">ابدأ اليوم 1</span>
        </RouterLink>
        <RouterLink to="/calculator" class="ban org">
          <img src="/img/banners/calculator-640.webp" srcset="/img/banners/calculator-640.webp 640w, /img/banners/calculator-960.webp 960w" sizes="340px" alt="" class="bg" loading="lazy" />
          <h3>اكتب «نظام»… واحسب سعراتك</h3>
          <small>نظام أكل مبدئي في دقيقة</small>
          <span class="mini">احسب ببلاش</span>
        </RouterLink>
        <RouterLink to="/pricing" class="ban">
          <img src="/img/banners/pro-640.webp" srcset="/img/banners/pro-640.webp 640w, /img/banners/pro-960.webp 960w" sizes="340px" alt="" class="bg" loading="lazy" />
          <h3>{{ pro?.name || 'فورمة PRO' }}</h3>
          <small v-if="pro">كل البرامج كاملة · <span class="num">{{ pro.price }}</span> ج.م {{ pro.period }}</small>
          <span class="mini">شوف الباقات</span>
        </RouterLink>
      </MRail>

      <MRail title="اتمرن حسب هدفك" :counter="false" :gap="12">
        <RouterLink v-for="g in goals" :key="g.cat" :to="{ path: '/library', query: { cat: g.cat } }" class="goal">
          <span><img :src="`/img/thumbs/${g.img}.webp`" alt="" /></span>{{ g.label }}
        </RouterLink>
      </MRail>

      <section v-if="p && nextDay" class="m-pad blk">
        <header class="bh"><h2>تمرين النهارده</h2><span class="num cnt">DAY {{ next.day }}</span></header>
        <RouterLink :to="`/workout/${p.id}/${next.day}`" class="m-li">
          <img :src="nextDay.items[0]?.img" alt="" />
          <span class="x">
            <b>{{ p.title }} · اليوم {{ next.day }}</b>
            <small class="en">{{ nextDay.items.map((i) => i.title).join(' · ') }}</small>
            <small>حوالي 9 دقايق · ببلاش</small>
          </span>
          <span class="go">▶</span>
        </RouterLink>
      </section>
    </template>

    <!-- ========== shared ========== -->
    <MRail title="المكتبة" :total="library.length" arrows all="/library">
      <RouterLink v-for="v in library" :key="v.id" :to="`/library/${v.id}`" class="m-reel">
        <img :src="v.img" :alt="v.title" loading="lazy" />
        <span class="d">0:{{ String(v.sec).padStart(2, '0') }}</span>
        <span class="t"><b>{{ v.title }}</b><small>{{ v.ar }}</small></span>
      </RouterLink>
    </MRail>

    <!-- transformations: unchanged component -->
    <TransformationsMarquee />

    <MRail title="البرامج" :total="programs.length" all="/programs">
      <RouterLink v-for="pr in programs" :key="pr.id" :to="`/programs/${pr.id}`" class="prog m-card">
        <img :src="pr.img" alt="" loading="lazy" />
        <div>
          <b>{{ pr.title }}</b>
          <small><span class="num amber">{{ pr.days }}</span> يوم · <span class="num amber">{{ pr.mins }}</span> د</small>
        </div>
      </RouterLink>
    </MRail>
    <div style="height: 18px"></div>
  </div>
</template>

<style scoped>
.logo { display: inline-flex; align-items: center; gap: 8px; flex: 1; }
.logo img { width: 34px; height: 34px; border-radius: 10px; }
.logo b { font-family: var(--f-head); font-weight: 400; font-size: 1.45rem; line-height: 1; display: block; }
.logo i { font-style: normal; color: var(--orange); font-size: .62rem; letter-spacing: .35em; display: block; }
.hi { flex: 1; display: grid; line-height: 1.25; }
.hi small { color: var(--muted); font-weight: 800; font-size: .76rem; }
.hi b { font-family: var(--f-head); font-weight: 400; font-size: 1.5rem; }

/* today */
.today { position: relative; display: flex; flex-direction: column; justify-content: space-between; height: 250px; margin-top: 6px; padding: 16px; border-radius: 24px; overflow: hidden; isolation: isolate; border: 1px solid #2a2a2a; }
.today > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 40%; z-index: -2; }
.today::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(to top, rgba(0,0,0,.95) 30%, rgba(0,0,0,.2) 70%); }
.today-top { display: flex; justify-content: space-between; align-items: flex-start; }
.pill { background: rgba(0,0,0,.6); border: 1px solid rgba(255,255,255,.15); border-radius: 99px; padding: 3px 12px; font-weight: 800; font-size: .78rem; }
.today-b { display: grid; }
.today-b small.amber { font-weight: 800; font-size: .76rem; }
.today-b h2 { font-size: 2.4rem; line-height: 1; }
.today-b h2 .num { font-size: 1.15em; }
.moves { color: rgba(255,255,255,.75); font-size: 1rem; justify-self: start; }
.go-btn { width: 100%; margin-top: 10px; }

.blk { padding-top: 18px; }
.bh { display: flex; align-items: center; margin-bottom: 10px; }
.bh h2 { font-size: 1.3rem; flex: 1; }
.cnt { color: var(--muted); font-size: 1rem; }
.week { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
.wd { height: 40px; line-height: 1; border-radius: 12px; background: var(--surface-2); color: #777; display: grid; place-items: center; font-size: 1.1rem; }
.wd.done { background: var(--orange); color: #0a0a0a; }
.wd.today { border: 2px solid var(--amber); color: var(--amber); background: transparent; }
.wd.rest:not(.done) { background: #1d2a1d; font-size: .9rem; }
.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.upsell { display: flex; align-items: center; gap: 12px; margin: 14px 16px 0; padding: 14px; border-radius: 18px; background: linear-gradient(120deg, rgba(255,106,0,.18), rgba(255,106,0,.04)); border: 1px solid rgba(255,106,0,.45); }
.upsell span { font-size: 1.4rem; }
.upsell div { flex: 1; display: grid; }
.upsell small { color: var(--muted); font-size: .78rem; }
.upsell i { font-style: normal; color: var(--orange); font-weight: 900; font-size: 1.2rem; }

/* banners */
.banners { padding-top: 6px; }
.ban { position: relative; width: min(340px, calc(100vw - 44px)); aspect-ratio: 16 / 9; border-radius: 22px; overflow: hidden; padding: 14px 16px; display: flex; flex-direction: column; justify-content: center; align-items: flex-start; gap: 4px; isolation: isolate; border: 1px solid #262626; }
/* generated art: coach on the left, calm space on the right for the Arabic copy */
.ban .bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: left center; z-index: -2; }
.ban::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(to left, rgba(10,10,10,.75) 0%, rgba(10,10,10,.35) 48%, transparent 62%); }
.ban > :not(.bg) { max-width: 54%; }
.ban h3 { font-size: 1.45rem; line-height: 1.1; }
.ban small { font-size: .74rem; color: rgba(255,255,255,.85); }
.ban .m-tag { align-self: flex-start; }
.ban .mini { align-self: flex-start; margin-top: 6px; background: var(--orange); color: #0a0a0a; font-weight: 900; font-size: .75rem; padding: 6px 12px; border-radius: 10px; }
.ban.org { background: var(--orange); color: #0a0a0a; border-color: transparent; }
.ban.org::after { display: none; }
.ban.org small { color: rgba(0,0,0,.78); font-weight: 700; }
.ban.org .mini { background: #0a0a0a; color: #fff; }

/* goals */
.goal { width: 70px; display: grid; justify-items: center; gap: 6px; font-size: .74rem; font-weight: 800; }
.goal > span { width: 64px; height: 64px; border-radius: 50%; padding: 3px; background: conic-gradient(var(--orange), var(--amber), var(--orange)); }
.goal img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; object-position: center 35%; border: 3px solid var(--black); }

/* programs rail */
.prog { width: 250px; overflow: hidden; }
.prog img { width: 100%; height: 120px; object-fit: cover; object-position: center 30%; }
.prog div { padding: 10px 12px; display: grid; }
.prog small { color: var(--muted); }

</style>
