<script setup>
import { ref, computed } from 'vue'
import ReelCard from '../components/ReelCard.vue'
import Faq from '../components/Faq.vue'
import MemberToday from '../components/MemberToday.vue'
import TransformationsMarquee from '../components/TransformationsMarquee.vue'
import { videos, categories, programs, steps } from '../data.js'
import { store, doneDays } from '../stores/app.js'

const cat = ref('all')
const list = computed(() => (cat.value === 'all' ? videos : videos.filter((v) => v.cat === cat.value)).slice(0, 8))
const ticker = ['من غير أدوات', 'من البيت', '40 ثانية شغل + 20 راحة', 'فيديوهات أقل من دقيقة', 'نظام أكل ببلاش', 'الكوتش المقنّع']
const done30 = computed(() => doneDays('30'))
</script>

<template>
  <div class="home">
    <!-- signed-in: app-style "today" dashboard instead of the marketing hero -->
    <div v-if="store.user" class="wrap"><MemberToday /></div>

    <!-- HERO -->
    <section v-else class="hero">
      <picture class="hero-bg">
        <source media="(min-width: 900px)" srcset="/img/pyramids-1672.webp" />
        <img src="/img/pyramids-coach-720.webp" alt="الكوتش المقنّع قدام الأهرامات" fetchpriority="high" />
      </picture>
      <div class="wrap hero-in">
        <div class="hero-copy">
          <span class="kicker">تدريب أونلاين مع الكوتش المقنّع</span>
          <h1>اتمرن صح،<br /><span class="hl">وخلّي جسمك</span><br />فورمة.</h1>
          <p class="lead">نفس تمارين الريلز اللي بتحبها، بس مترتّبة في برنامج يوم بيوم — من البيت ومن غير ولا أداة.</p>
          <div class="ctas">
            <RouterLink to="/calculator" class="btn btn-primary">احسب سعراتك وخد نظامك ببلاش</RouterLink>
            <RouterLink to="/programs/30" class="btn btn-ghost">ابدأ تحدّي 30 يوم</RouterLink>
          </div>
          <ul class="chips"><li class="on">ريلز يومية</li><li>تمارين في البيت</li><li>أنظمة أكل ببلاش</li></ul>
        </div>
      </div>
    </section>

    <div v-if="!store.user" class="ticker" aria-hidden="true">
      <div class="ticker-track">
        <span v-for="n in 2" :key="n" class="ticker-set"><span v-for="t in ticker" :key="t">{{ t }} <i>▼</i></span></span>
      </div>
    </div>

    <!-- LIBRARY -->
    <section class="sec wrap">
      <div class="sec-head row">
        <div>
          <h2>مكتبة <span class="hl">الكوتش</span></h2>
          <p>اختار العضلة، دوس على التمرين، واتمرن معاه.</p>
        </div>
        <RouterLink to="/library" class="more">كل التمارين ←</RouterLink>
      </div>
      <div class="filters no-scrollbar" role="tablist">
        <button v-for="c in categories" :key="c.id" role="tab" :aria-selected="cat === c.id" :class="{ on: cat === c.id }" @click="cat = c.id"><span>{{ c.label }}</span></button>
      </div>
      <div class="lib">
        <ReelCard v-for="(v, i) in list" :key="v.id" :v="v" :n="i + 1" />
      </div>
    </section>

    <!-- 30 DAY -->
    <section v-if="!store.user" class="ch30">
      <div class="wrap ch30-in">
        <div class="big30 num" aria-hidden="true">30</div>
        <div class="ch30-copy">
          <span class="kicker">البرنامج الأساسي</span>
          <h2>تحدّي الـ <span class="num amber">30</span> يوم</h2>
          <p>كل يوم <b class="num amber">15–20</b> دقيقة. 3 تمارين، كرّرهم 3 مرات. المؤقّت شغّال قدامك، بتعلّم على اليوم لما تخلّصه، والحِمل بيزيد كل أسبوع.</p>
          <div class="days" aria-label="أيام التحدي">
            <span v-for="d in 30" :key="d" class="day" :class="{ done: done30.includes(d), free: d <= 3 && !done30.includes(d) }"><span class="num">{{ d }}</span></span>
          </div>
          <small class="muted">أول <span class="num amber">3</span> أيام ببلاش — جرّب قبل ما تدفع.</small>
          <div class="ctas">
            <RouterLink to="/workout/30/1" class="btn btn-primary">جرّب اليوم الأول</RouterLink>
            <RouterLink to="/programs/30" class="btn btn-ghost">تفاصيل البرنامج</RouterLink>
          </div>
        </div>
      </div>
    </section>

    <TransformationsMarquee />

    <!-- PROGRAMS -->
    <section class="sec wrap">
      <div class="sec-head row"><h2>برامج <span class="hl">جاهزة</span></h2><RouterLink to="/programs" class="more">كل البرامج ←</RouterLink></div>
      <div class="progs">
        <RouterLink v-for="p in programs" :key="p.id" :to="'/programs/' + p.id" class="prog">
          <img :src="p.img" alt="" loading="lazy" />
          <div class="prog-body">
            <span v-if="p.tag" class="badge">{{ p.tag }}</span>
            <h3>{{ p.title }}</h3>
            <p>{{ p.sub }}</p>
            <div class="prog-meta"><span><b class="num">{{ p.days }}</b> يوم</span><span><b class="num">{{ p.mins }}</b> د/يوم</span></div>
          </div>
        </RouterLink>
      </div>
    </section>

    <!-- HOW -->
    <section v-if="!store.user" class="sec wrap">
      <div class="sec-head"><h2>بيشتغل <span class="hl">إزاي؟</span></h2></div>
      <ol class="steps">
        <li v-for="s in steps" :key="s.n" class="step">
          <span class="step-n num">{{ s.n }}</span>
          <h3>{{ s.title }}</h3>
          <p>{{ s.body }}</p>
        </li>
      </ol>
    </section>

    <!-- PLAN CTA -->
    <section v-if="!store.user" class="plan">
      <div class="wrap plan-in">
        <img src="/img/coach-arms-640.webp" alt="الكوتش المقنّع" class="plan-coach" loading="lazy" />
        <div class="plan-copy">
          <h2>اكتب «نظام»…<br />وخد نظامك ببلاش</h2>
          <p>زي ما بنقول في كل ريل — بس هنا من غير ما تستنى الرد. جاوب على 6 أسئلة وخد سعراتك ونظام أكل مبدئي فورًا.</p>
          <RouterLink to="/calculator" class="btn btn-dark">احسب دلوقتي</RouterLink>
          <small>السعرات تقريبية. لو عندك مشكلة صحية اكشف عند دكتور الأول.</small>
        </div>
      </div>
    </section>

    <section v-if="!store.user" class="sec wrap">
      <div class="sec-head"><h2>أسئلة <span class="hl">بتتسأل</span></h2></div>
      <Faq />
    </section>
  </div>
</template>

<style scoped>
/* HERO */
.hero { position: relative; min-height: 100svh; display: flex; align-items: flex-end; overflow: hidden; isolation: isolate; }
.hero-bg { position: absolute; inset: 0; z-index: -1; }
.hero-bg img { width: 100%; height: 100%; object-fit: cover; object-position: 60% top; }
.hero::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(to top, #0a0a0a 22%, rgba(10,10,10,.7) 50%, transparent 78%); }
.hero-in { padding-block: 120px 44px; }
.hero-copy { max-width: 560px; display: grid; gap: 16px; }
.hero h1 { font-size: clamp(3rem, 13vw, 6rem); line-height: 1.08; text-shadow: 0 4px 0 #000; }
.lead { color: rgba(255,255,255,.88); font-size: 1.05rem; max-width: 46ch; }
.ctas { display: flex; flex-wrap: wrap; gap: 10px; }
.ctas .btn { flex: 1 1 220px; white-space: normal; text-align: center; line-height: 1.3; }
.chips { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 8px; }
.chips li { background: #fff; color: #111; border-radius: 999px; padding: 4px 14px; font-weight: 800; font-size: .85rem; }
.chips li.on { background: var(--orange); color: #0a0a0a; }
@media (min-width: 900px) {
  .hero { align-items: center; }
  .hero::after { background: linear-gradient(to left, transparent 35%, rgba(10,10,10,.88) 62%), linear-gradient(to top, #0a0a0a 2%, transparent 30%); }
  .hero-bg img { object-position: center; }
  .hero-in { display: flex; justify-content: flex-end; }
}

/* TICKER */
.ticker { background: var(--orange); color: #0a0a0a; transform: rotate(-2deg); margin: 10px -20px 0; overflow: hidden; border-block: 3px solid #0a0a0a; }
.ticker-track { display: flex; width: max-content; animation: tick 28s linear infinite; }
.ticker-set { display: flex; }
.ticker-set > span { font-family: var(--f-head); font-size: 1.35rem; padding: 10px 18px; white-space: nowrap; }
.ticker i { font-style: normal; font-size: .7em; vertical-align: middle; margin-inline-start: 14px; }
@keyframes tick { to { transform: translateX(50%); } }

.sec-head.row { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; }
.more { color: var(--orange); font-weight: 800; white-space: nowrap; }
.lib { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
@media (max-width: 699px) {
  .lib, .progs { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; margin-inline: calc(var(--gutter) * -1); padding-inline: var(--gutter); scroll-padding-inline: var(--gutter); gap: 10px; }
  .lib::-webkit-scrollbar, .progs::-webkit-scrollbar { display: none; }
  .lib > * { flex: 0 0 44%; scroll-snap-align: start; }
  .progs > * { flex: 0 0 82%; scroll-snap-align: start; }
  .sec { padding-block: 40px 8px; }
}
@media (min-width: 700px) { .lib { grid-template-columns: repeat(3, 1fr); gap: 14px; } }
@media (min-width: 1000px) { .lib { grid-template-columns: repeat(4, 1fr); } }

/* 30 */
.ch30 { position: relative; margin-top: 64px; overflow: hidden; background: #111; border-block: 1px solid #222; }
.ch30::before { content: ''; position: absolute; inset: 0; background: var(--dots); opacity: .25; mask: linear-gradient(120deg, #000 0%, transparent 55%); -webkit-mask: linear-gradient(120deg, #000 0%, transparent 55%); }
.ch30-in { position: relative; padding-block: 56px; display: grid; gap: 10px; }
.big30 { font-size: clamp(10rem, 46vw, 26rem); line-height: .8; color: transparent; -webkit-text-stroke: 3px var(--orange); justify-self: start; }
.ch30-copy { display: grid; gap: 16px; max-width: 580px; }
.ch30-copy h2 { font-size: clamp(2.4rem, 9vw, 3.8rem); }
.ch30-copy h2 .num { font-size: 1.15em; }
.ch30-copy p { color: rgba(255,255,255,.85); }
.days { display: grid; grid-template-columns: repeat(10, 1fr); gap: 5px; }
.day { aspect-ratio: 1; border-radius: 6px; border: 1.5px solid #333; display: grid; place-items: center; font-size: .95rem; color: #777; }
.day.done { background: var(--orange); border-color: var(--orange); color: #0a0a0a; }
.day.free { border-color: var(--amber); color: var(--amber); }
@media (min-width: 900px) { .ch30-in { grid-template-columns: 1fr 1.2fr; align-items: center; } }

/* PROGRAMS */
.progs { display: grid; gap: 12px; }
@media (min-width: 700px) { .progs { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1000px) { .progs { grid-template-columns: repeat(4, 1fr); } }
.prog { display: grid; grid-template-columns: 96px 1fr; gap: 12px; background: #141414; border: 1px solid #222; border-radius: 14px; overflow: hidden; transition: border-color .2s; }
.prog:hover { border-color: var(--orange); }
.prog img { width: 100%; height: 100%; object-fit: cover; aspect-ratio: 9/14; }
.prog-body { padding: 12px 0; padding-inline-end: 10px; display: grid; gap: 4px; align-content: center; }
.prog-body h3 { font-size: 1.35rem; }
.prog-body p { color: var(--muted); font-size: .85rem; }
.prog-meta { display: flex; gap: 12px; font-size: .85rem; }
.prog-meta b { color: var(--amber); font-size: 1.15rem; }
.prog-body .badge { justify-self: start; }
@media (min-width: 1000px) { .prog { grid-template-columns: 1fr; } .prog img { aspect-ratio: 4/5; } .prog-body { padding: 14px; } }

/* STEPS */
.steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
@media (min-width: 800px) { .steps { grid-template-columns: repeat(3, 1fr); } }
.step { padding: 22px; border: 2px solid #fff; border-radius: 4px; background: #0f0f0f; box-shadow: 6px 6px 0 var(--orange); display: grid; gap: 6px; }
.step-n { font-size: 3.4rem; line-height: .9; color: var(--orange); }
.step h3 { font-size: 1.5rem; }
.step p { color: var(--muted); }

/* PLAN */
.plan { position: relative; margin-top: 72px; background: var(--orange); color: #0a0a0a; overflow: hidden; }
.plan::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle, rgba(0,0,0,.22) 1.4px, transparent 1.6px) 0 0 / 11px 11px; mask: linear-gradient(to left, #000, transparent 60%); -webkit-mask: linear-gradient(to left, #000, transparent 60%); }
.plan-in { position: relative; display: grid; gap: 20px; padding-block: 48px; }
.plan-coach { width: 220px; justify-self: center; border-radius: 50%; border: 5px solid #0a0a0a; box-shadow: 8px 8px 0 #0a0a0a; }
.plan-copy { display: grid; gap: 14px; justify-items: start; }
.plan-copy h2 { font-size: clamp(2.2rem, 8vw, 3.4rem); }
.plan-copy p { font-weight: 600; max-width: 52ch; }
.plan small { font-weight: 700; opacity: .8; }
@media (min-width: 900px) { .plan-in { grid-template-columns: auto 1fr; align-items: center; gap: 48px; } .plan-coach { width: 300px; } }
</style>
