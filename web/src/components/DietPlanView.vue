<script setup>
// The free plan, tailored: the diet the visitor picked in the calculator, scaled to their calories.
import { ref, computed } from 'vue'
import { dietBySlug, dietDay, CYCLE_LABEL } from '../diets/data.js'

const props = defineProps({ calc: { type: Object, required: true }, mobile: Boolean })
const d = computed(() => dietBySlug(props.calc.input?.diet))
const day = ref(1)
const plan = computed(() => (d.value ? dietDay(d.value, day.value) : null))
const factor = computed(() => (d.value?.noKcal || !plan.value?.kcal ? 1 : props.calc.kcal / plan.value.kcal))
const kc = (k) => Math.round((k * factor.value) / 10) * 10
const pct = computed(() => Math.round((factor.value - 1) * 100))
const days = Array.from({ length: 7 }, (_, i) => i + 1)
// one brand palette for every diet
const vars = { '--bg': '#0e0e0e', '--sf': '#141414', '--ink': '#fff', '--a': '#ff6a00', '--b': '#ffb000', '--line': '#262626', '--soft': 'rgba(255,255,255,.64)' }
</script>

<template>
  <section v-if="d" class="dpv" :class="{ mob: mobile }" :style="vars">
    <!-- head -->
    <header class="head">
      <div class="ht">
        <small class="en">{{ d.en }}</small>
        <h2>نظام {{ d.name }} <span class="acc">على قدّك</span></h2>
        <p v-if="d.noKcal">النظام ده مبيعدّش سعرات — الكميات على قد جوعك. احتياجك التقريبي <b class="num">{{ calc.kcal }}</b> سعرة للمعلومة بس.</p>
        <p v-else>متفصّل على <b class="num">{{ calc.kcal }}</b> سعرة في اليوم بنسب النظام ({{ d.macros.c }}% كارب · {{ d.macros.p }}% بروتين · {{ d.macros.f }}% دهون).</p>
      </div>
    </header>

    <div v-if="!d.noKcal" class="macs">
      <div><b class="num">{{ calc.protein }}</b><span>بروتين جم</span></div>
      <div><b class="num">{{ calc.carbs }}</b><span>كارب جم</span></div>
      <div><b class="num">{{ calc.fat }}</b><span>دهون جم</span></div>
    </div>

    <!-- week 1 -->
    <div class="wk-h">
      <b>أسبوعك الأول</b>
      <RouterLink :to="`/diets/${d.slug}`" class="all">الـ 30 يوم كاملين ‹</RouterLink>
    </div>
    <div class="days" role="tablist">
      <button v-for="n in days" :key="n" role="tab" :aria-selected="day === n" class="dd" :class="{ sel: day === n }" @click="day = n">
        <small>يوم</small><span class="num">{{ n }}</span>
      </button>
    </div>

    <Transition name="fade" mode="out-in">
      <div :key="day" class="card">
        <div class="ch">
          <div>
            <b>اليوم <span class="num">{{ day }}</span></b>
            <small v-if="plan.type" class="ty">{{ (d.cycleLabels || CYCLE_LABEL)[plan.type] }}</small>
          </div>
          <span v-if="!d.noKcal" class="tot">≈ <span class="num">{{ kc(plan.kcal) }}</span> سعرة</span>
        </div>
        <ul class="meals">
          <li v-for="(m, i) in plan.meals" :key="i">
            <span class="x"><small>{{ m.label }}</small><b>{{ m.n }}</b></span>
            <span v-if="m.k && !d.noKcal" class="k num">{{ kc(m.k) }}</span>
          </li>
        </ul>
        <p v-if="d.noKcal" class="hint">«كُل لما تجوع وبطّل قبل الشبع» — زي ما النظام بيقول.</p>
        <p v-else-if="Math.abs(pct) >= 5" class="hint">الكميات المكتوبة محسوبة على حوالي <span class="num">{{ plan.kcal }}</span> سعرة — <b>{{ pct > 0 ? 'زوّد' : 'قلّل' }} كل وجبة حوالي <span class="num">{{ Math.abs(pct) }}%</span></b> عشان توصل لـ <span class="num">{{ calc.kcal }}</span>.</p>
        <p v-else class="hint">الكميات المكتوبة على قدّك تقريبًا زي ما هي.</p>
      </div>
    </Transition>

    <!-- rules -->
    <div class="rules">
      <div><b>✓ كُل</b><ul><li v-for="x in d.eat.slice(0, 4)" :key="x">{{ x }}</li></ul></div>
      <div><b class="no">✕ ابعد عن</b><ul><li v-for="x in d.avoid.slice(0, 4)" :key="x">{{ x }}</li></ul></div>
    </div>
    <p class="warn">⚠️ {{ d.warning }}</p>
    <RouterLink :to="`/diets/${d.slug}`" class="go">افتح صفحة النظام — خطة 30 يوم وقايمة مشتريات ‹</RouterLink>
  </section>
</template>

<style scoped>
.dpv { display: grid; gap: 14px; padding: 18px; border-radius: 24px; background-color: var(--bg); color: var(--ink); border: 1px solid var(--line); }
.acc { color: var(--a); }
.head { display: flex; gap: 12px; align-items: flex-start; }
.e { width: 56px; height: 56px; flex: none; border-radius: 16px; display: grid; place-items: center; font-size: 1.8rem; background: var(--sf); border: 1px solid var(--line); }
.ht { display: grid; gap: 2px; min-width: 0; }
.ht .en { font-family: var(--f-num); letter-spacing: .14em; color: var(--a); direction: ltr; justify-self: start; }
.ht h2 { font-size: clamp(1.5rem, 5vw, 2.2rem); line-height: 1.15; }
.ht p { font-size: .86rem; color: var(--soft); }
.ht p b { color: var(--ink); font-size: 1.1rem; }
.macs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.macs div { display: grid; padding: 10px 12px; border-radius: 14px; background: var(--sf); border: 1px solid var(--line); }
.macs b { font-size: 1.8rem; line-height: 1; color: var(--a); justify-self: start; }
.macs span { font-size: .72rem; font-weight: 800; color: var(--soft); }
.wk-h { display: flex; justify-content: space-between; align-items: baseline; margin-top: 4px; }
.wk-h b { font-family: var(--f-head); font-weight: 400; font-size: 1.3rem; }
.all { font-weight: 800; font-size: .82rem; color: var(--a); }
.days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; max-width: 520px; }
.dd { aspect-ratio: .9; border-radius: 12px; background: var(--sf); border: 1.5px solid var(--line); color: var(--ink); display: grid; place-items: center; align-content: center; line-height: 1.1; }
.dd small { font-size: .6rem; font-weight: 800; color: var(--soft); }
.dd .num { font-size: 1.25rem; }
.dd.sel { background: var(--a); border-color: var(--a); color: #0a0a0a; }
.dd.sel small { color: rgba(0,0,0,.6); }
.card { padding: 14px; border-radius: 18px; background: var(--sf); border: 1px solid var(--line); }
.ch { display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed var(--line); }
.ch > div { display: grid; }
.ch b { font-family: var(--f-head); font-weight: 400; font-size: 1.35rem; }
.ty { font-size: .74rem; font-weight: 800; color: var(--b); }
.tot { font-weight: 800; font-size: .82rem; color: var(--soft); }
.tot .num { font-size: 1.4rem; color: var(--a); }
.meals { list-style: none; margin: 0; padding: 0; display: grid; }
.meals li { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--line); }
.meals li:last-child { border-bottom: 0; }
.meals .ic { width: 38px; height: 38px; flex: none; border-radius: 12px; display: grid; place-items: center; font-size: 1.1rem; background: color-mix(in srgb, var(--a) 14%, transparent); }
.meals .x { flex: 1; display: grid; min-width: 0; }
.meals .x small { font-size: .7rem; font-weight: 800; color: var(--soft); }
.meals .x b { font-size: .94rem; line-height: 1.5; }
.meals .k { font-size: 1.2rem; color: var(--a); }
.hint { margin-top: 8px; font-size: .8rem; color: var(--soft); }
.hint b { color: var(--ink); }
.rules { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.rules > div { padding: 12px; border-radius: 14px; background: var(--sf); border: 1px solid var(--line); }
.rules b { color: var(--a); font-size: .9rem; }
.rules b.no { color: #ff5a5a; }
.rules ul { margin: 6px 0 0; padding: 0; list-style: none; display: grid; gap: 4px; font-size: .8rem; font-weight: 600; }
.warn { font-size: .78rem; padding: 10px 12px; border-radius: 12px; background: rgba(255,90,90,.1); border: 1px solid rgba(255,90,90,.3); }
.go { display: grid; place-items: center; min-height: 48px; border-radius: 14px; background: var(--a); color: #0a0a0a; font-weight: 900; font-size: .9rem; text-align: center; padding: 0 12px; }
.mob .rules { grid-template-columns: 1fr; }
.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
