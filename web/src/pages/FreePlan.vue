<script setup>
import { computed } from 'vue'
import { store } from '../stores/app.js'
import { starterDay, goals } from '../nutrition.js'
import { dietBySlug } from '../diets/data.js'
import DietPlanView from '../components/DietPlanView.vue'

const c = computed(() => store.calc)
const meals = computed(() => (c.value?.kcal ? starterDay(c.value.kcal) : []))
const goal = computed(() => goals.find((g) => g.id === c.value?.input?.goal)?.label)
const diet = computed(() => dietBySlug(c.value?.input?.diet))
const name = computed(() => store.calc?.leadName || store.user?.name || '')
</script>

<template>
  <div>
    <header class="page-head">
      <div class="wrap">
        <span class="kicker">نظامك المبدئي{{ goal ? ' · ' + goal : '' }}{{ diet ? ' · ' + diet.name : '' }}</span>
        <h1>{{ name ? `يلا يا ${name}،` : 'يلا،' }}<br /><span class="hl">ده نظامك</span></h1>
        <p v-if="diet">نظام {{ diet.name }} متفصّل على سعراتك: أسبوع كامل بالوجبات وكمية كل وجبة.</p>
        <p v-else>ده يوم نموذجي على سعراتك التقريبية. بدّل الأكلات بحاجات شبهها، المهم المجموع.</p>
      </div>
    </header>

    <section v-if="!c?.kcal" class="wrap empty">
      <p class="muted">لسه محسبتش سعراتك.</p>
      <RouterLink to="/calculator" class="btn btn-primary">احسب دلوقتي</RouterLink>
    </section>

    <template v-else>
      <section v-if="diet" class="wrap tailored"><DietPlanView :calc="c" /></section>
      <template v-else>
      <section class="wrap sum">
        <div class="big"><span class="num">{{ c.kcal }}</span><small>سعرة/يوم تقريبًا</small></div>
        <div class="mac"><b class="num">{{ c.protein }}</b><span>بروتين جم</span></div>
        <div class="mac"><b class="num">{{ c.carbs }}</b><span>كارب جم</span></div>
        <div class="mac"><b class="num">{{ c.fat }}</b><span>دهون جم</span></div>
      </section>

      <section class="wrap meals">
        <article v-for="m in meals" :key="m.id" class="meal">
          <header><h2>{{ m.name }}</h2><span><b class="num">{{ m.kcal }}</b> سعرة</span></header>
          <ul><li v-for="i in m.items" :key="i">{{ i }}</li></ul>
        </article>
      </section>

      </template>
      <p class="wrap change"><RouterLink to="/calculator">غيّر النظام أو بياناتك ‹</RouterLink></p>
      <section class="wrap tips">
        <p class="note">اشرب مية كتير، قلّل السكر والعصاير والمقليات. الأرقام تقريبية ومش بديل عن دكتور تغذية، ولو عندك سكر أو ضغط أو أي مشكلة صحية اكشف الأول.</p>
      </section>

      <section class="wrap up">
        <div class="up-in">
          <img src="/img/coach-arms-640.webp" alt="" loading="lazy" />
          <div>
            <h2>الأكل نص الشغل.<br /><span class="hl">التمرين النص التاني.</span></h2>
            <p>ابدأ تحدّي الـ 30 يوم — أول 3 أيام ببلاش، من البيت ومن غير أدوات.</p>
            <div class="btns">
              <RouterLink to="/workout/30/1" class="btn btn-primary">ابدأ اليوم الأول</RouterLink>
              <RouterLink to="/pricing" class="btn btn-ghost">شوف الباقات</RouterLink>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.empty { padding-top: 32px; display: grid; gap: 12px; justify-items: start; }
.sum { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding-top: 24px; }
.big { grid-column: 1 / -1; display: flex; align-items: baseline; gap: 10px; padding: 18px; border: 2px solid var(--orange); border-radius: 16px; box-shadow: 6px 6px 0 var(--orange); background: #0f0f0f; }
.big .num { font-size: clamp(4rem, 16vw, 5.4rem); line-height: .85; }
.big small { font-weight: 800; color: var(--muted); }
.mac { display: grid; padding: 12px 14px; border-radius: 14px; background: #121212; border: 1px solid #222; }
.mac b { font-size: 2rem; line-height: 1; color: var(--orange); justify-self: start; }
.mac span { font-size: .78rem; color: var(--muted); font-weight: 700; }
@media (min-width: 900px) { .sum { grid-template-columns: 2fr 1fr 1fr 1fr; } .big { grid-column: auto; } }
.meals { display: grid; gap: 12px; padding-top: 22px; }
@media (min-width: 800px) { .meals { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1100px) { .meals { grid-template-columns: repeat(4, 1fr); } }
.meal { padding: 18px; border-radius: 16px; background: #121212; border: 1px solid #222; }
.meal header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
.meal h2 { font-size: 1.5rem; }
.meal header b { color: var(--amber); font-size: 1.4rem; }
.meal ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 6px; }
.meal li { display: flex; gap: 8px; color: rgba(255,255,255,.88); }
.meal li::before { content: '•'; color: var(--orange); font-weight: 900; }
.tips { padding-top: 18px; }
.tailored { padding-top: 24px; }
.change { padding-top: 12px; font-weight: 800; }
.change a { color: var(--orange); }
.up { padding-top: 40px; }
.up-in { display: grid; gap: 18px; padding: 22px; border-radius: 20px; background: #111; border: 1px solid #222; align-items: center; }
.up-in img { width: 140px; border-radius: 50%; border: 4px solid var(--orange); }
.up-in h2 { font-size: clamp(1.8rem, 6vw, 2.6rem); margin-bottom: 8px; }
.up-in p { color: var(--muted); margin-bottom: 14px; }
.btns { display: flex; flex-wrap: wrap; gap: 10px; }
@media (min-width: 800px) { .up-in { grid-template-columns: 200px 1fr; padding: 32px; } .up-in img { width: 200px; } }
</style>
