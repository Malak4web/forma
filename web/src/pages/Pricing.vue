<script setup>
import { plans, egp, payMethods } from '../data.js'
import { store } from '../stores/app.js'
import Faq from '../components/Faq.vue'

const link = (p) => {
  if (p.price === 0) return store.user ? '/programs' : '/register'
  return store.user ? `/checkout/${p.id}` : `/register?next=/checkout/${p.id}`
}
const current = (p) => store.subscription?.plan === p.id
</script>

<template>
  <div>
    <header class="page-head">
      <div class="wrap">
        <nav class="crumbs"><RouterLink to="/">الرئيسية</RouterLink><span>/</span><span>الباقات</span></nav>
        <h1>اختار <span class="hl">باقتك</span></h1>
        <p>ابدأ ببلاش، وادفع بس لما تحب تكمّل. الدفع بفودافون كاش أو InstaPay.</p>
      </div>
    </header>

    <section class="wrap plans">
      <article v-for="p in plans" :key="p.id" class="plan" :class="{ hi: p.highlight }">
        <span v-if="p.tag || p.highlight" class="badge">{{ p.highlight ? 'الأوفر' : p.tag }}</span>
        <h2>{{ p.name }}</h2>
        <p class="price">
          <template v-if="p.price"><span class="num">{{ egp(p.price) }}</span> ج.م <small>{{ p.period }}</small></template>
          <template v-else><span class="num">0</span> ج.م <small>على طول</small></template>
        </p>
        <ul><li v-for="f in p.features" :key="f">{{ f }}</li></ul>
        <span v-if="current(p)" class="btn btn-ghost btn-block cur">باقتك الحالية ✓</span>
        <RouterLink v-else :to="link(p)" class="btn btn-block" :class="p.price ? 'btn-primary' : 'btn-ghost'">{{ p.cta }}</RouterLink>
      </article>
    </section>

    <section class="wrap pays">
      <h2>طرق الدفع</h2>
      <ul>
        <li v-for="m in payMethods" :key="m.id"><b>{{ m.label }}</b><small>{{ m.hint }}</small></li>
      </ul>
    </section>

    <section class="wrap sec">
      <div class="sec-head"><h2>أسئلة <span class="hl">بتتسأل</span></h2></div>
      <Faq />
    </section>
  </div>
</template>

<style scoped>
.plans { display: grid; gap: 16px; padding-top: 28px; }
@media (min-width: 900px) { .plans { grid-template-columns: repeat(3, 1fr); align-items: stretch; } }
.plan { position: relative; display: flex; flex-direction: column; gap: 14px; padding: 24px; border-radius: 18px; background: #121212; border: 1px solid #262626; }
.plan.hi { border: 2px solid var(--orange); box-shadow: 8px 8px 0 var(--orange); background: linear-gradient(170deg, rgba(255,106,0,.12), #121212 45%); }
.plan .badge { position: absolute; top: -11px; inset-inline-start: 20px; }
.plan h2 { font-size: 1.8rem; }
.price { font-family: var(--f-head); font-size: 1.2rem; }
.price .num { font-size: 3.4rem; line-height: .9; color: #fff; }
.price small { font-family: var(--f-body); font-size: .85rem; color: var(--muted); font-weight: 700; }
.plan ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; flex: 1; }
.plan li { display: flex; gap: 10px; }
.plan li::before { content: '✓'; color: var(--orange); font-weight: 900; }
.cur { cursor: default; color: var(--ok); border-color: var(--ok); }
.pays { padding-top: 40px; }
.pays h2 { font-size: 1.6rem; margin-bottom: 12px; }
.pays ul { margin: 0; padding: 0; list-style: none; display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
@media (min-width: 900px) { .pays ul { max-width: 640px; } }
.pays li { display: grid; padding: 14px; border-radius: 14px; background: #121212; border: 1px solid #222; }
.pays small { color: var(--muted); font-size: .8rem; }
</style>
