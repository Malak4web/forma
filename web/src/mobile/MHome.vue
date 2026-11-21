<script setup>
import { computed } from 'vue'
import MAppBar from './MAppBar.vue'
import MRail from './MRail.vue'
import TransformationsMarquee from '../components/TransformationsMarquee.vue'
import SocialLinks from '../components/SocialLinks.vue'
import MemberToday from '../components/MemberToday.vue'
import { videos, programs, programDay, plans } from '../data.js'
import { store, nextWorkout, doneDays, hasAccess, greeting } from '../stores/app.js'
import { DIETS, dietBySlug } from '../diets/data.js'
import { TRAININGS } from '../training/data.js'
import { motifBg } from '../diets/motifs.js'

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
const tay = dietBySlug('tayyibat')
const tayBg = motifBg('arabesque', tay.theme.a, 0.2)
const dietsSorted = [tay, ...DIETS.filter((x) => x.slug !== 'tayyibat')]
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
          <b>يومك النهارده</b>
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
    <div v-if="store.user" class="m-pad"><MemberToday mobile /></div>

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

    <!-- featured: Al-Tayyibat -->
    <div class="m-pad blk">
      <RouterLink to="/diets/tayyibat" class="tay" :style="{ backgroundImage: tayBg }">
        <span class="tay-star" aria-hidden="true">
          <svg viewBox="0 0 100 100"><g fill="none" stroke="#d4af37" stroke-width="3"><rect x="22" y="22" width="56" height="56" rx="4" /><rect x="22" y="22" width="56" height="56" rx="4" transform="rotate(45 50 50)" /></g><text x="50" y="57" text-anchor="middle">🌿</text></svg>
        </span>
        <span class="tay-b">
          <span class="tay-k">نظام مميّز · قايمة مسموح وممنوع</span>
          <b>نظام الطيبات</b>
          <small>كُل لما تجوع، وطيّبات واضحة — وخطة 30 يوم جاهزة.</small>
        </span>
        <span class="tay-go">‹</span>
      </RouterLink>
    </div>

    <MRail title="أنظمة الأكل" :total="dietsSorted.length" all="/diets" all-label="كل الأنظمة">
      <RouterLink v-for="dt in dietsSorted" :key="dt.slug" :to="`/diets/${dt.slug}`" class="dcard" :style="{ '--da': dt.theme.a, '--dbg': dt.theme.bg, '--dink': dt.theme.ink }">
        <span class="de">{{ dt.emoji }}</span>
        <b>{{ dt.name }}</b>
        <small class="en">{{ dt.en }}</small>
        <span class="d30"><span class="num">30</span> يوم</span>
      </RouterLink>
    </MRail>

    <MRail title="أنظمة التدريب" :total="TRAININGS.length" all="/training" all-label="كل الأنظمة">
      <RouterLink v-for="tr in TRAININGS" :key="tr.slug" :to="`/training/${tr.slug}`" class="dcard tcard" :style="{ '--da': tr.theme.a, '--dbg': tr.theme.mode === 'light' ? '#13231d' : tr.theme.bg, '--dink': tr.theme.mode === 'light' ? '#eafff6' : tr.theme.ink }">
        <span class="de">{{ tr.emoji }}</span>
        <b>{{ tr.name }}</b>
        <small class="en">{{ tr.en }}</small>
        <span class="d30"><span class="num">{{ tr.daysPerWeek }}</span> أيام</span>
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
    <section class="m-pad blk">
      <SocialLinks title="تابع الكوتش المقنّع — ريلز جديدة كل يوم" />
    </section>
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

/* diets rail */
.dcard { position: relative; width: 140px; height: 150px; padding: 12px; border-radius: 20px; background: var(--dbg); color: var(--dink); border: 1px solid color-mix(in srgb, var(--da) 40%, transparent); display: flex; flex-direction: column; justify-content: flex-end; gap: 2px; overflow: hidden; }
.dcard::before { content: ''; position: absolute; width: 120px; height: 120px; border-radius: 50%; top: -50px; inset-inline-end: -40px; background: radial-gradient(circle, color-mix(in srgb, var(--da) 45%, transparent), transparent 70%); }
.de { position: absolute; top: 10px; inset-inline-start: 12px; font-size: 2rem; }
.dcard b { font-family: var(--f-head); font-weight: 400; font-size: 1.25rem; line-height: 1.1; }
.dcard .en { color: var(--da); font-size: .78rem; letter-spacing: .12em; justify-self: start; align-self: flex-start; }
.d30 { position: absolute; top: 12px; inset-inline-end: 10px; font-size: .62rem; font-weight: 900; background: var(--da); color: #0a0a0a; padding: 2px 7px; border-radius: 99px; }

/* featured tayyibat */
.tay { display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 22px; background-color: #0e1712; color: #f3ead2; border: 1px solid rgba(212,175,55,.45); box-shadow: inset 0 0 0 1px rgba(212,175,55,.08), 0 14px 30px -20px #d4af37; }
.tay-star { flex: none; width: 70px; height: 70px; border-radius: 18px; background: #16241c; display: grid; place-items: center; }
.tay-star svg { width: 62px; height: 62px; animation: tayspin 24s linear infinite; }
.tay-star text { font-size: 22px; }
@keyframes tayspin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .tay-star svg { animation: none; } }
.tay-b { flex: 1; display: grid; gap: 2px; min-width: 0; }
.tay-k { font-size: .66rem; font-weight: 900; color: #7fbf95; }
.tay-b b { font-family: var(--f-head); font-weight: 400; font-size: 1.5rem; line-height: 1.05; color: #d4af37; }
.tay-b small { font-size: .76rem; opacity: .85; line-height: 1.5; }
.tay-go { font-family: var(--f-head); font-size: 1.6rem; color: #d4af37; }
.tcard .d30 { font-size: .6rem; }

/* programs rail */
.prog { width: 250px; overflow: hidden; }
.prog img { width: 100%; height: 120px; object-fit: cover; object-position: center 30%; }
.prog div { padding: 10px 12px; display: grid; }
.prog small { color: var(--muted); }

</style>
