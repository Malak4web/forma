<script setup>
import { reactive, computed, watch } from 'vue'
import { goals, activities, calcCalories } from '../nutrition.js'
import { store } from '../stores/app.js'

defineProps({ compact: Boolean })
const f = reactive({ sex: 'm', age: 28, weight: 85, height: 175, act: 1.375, goal: 'loss', ...(store.calc?.input || {}) })
const res = computed(() => calcCalories(f))
watch([() => ({ ...f }), res], ([input, r]) => { store.calc = { input, ...r, weight: input.weight } }, { immediate: true, deep: true })
defineExpose({ f, res })
</script>

<template>
  <div class="calc" :class="{ compact }">
    <form class="calc-form" @submit.prevent>
      <fieldset class="seg">
        <legend>هدفك إيه؟</legend>
        <label v-for="g in goals" :key="g.id" :class="{ on: f.goal === g.id }"><input type="radio" v-model="f.goal" :value="g.id" /><span>{{ g.label }}</span></label>
      </fieldset>
      <fieldset class="seg">
        <legend>النوع</legend>
        <label :class="{ on: f.sex === 'm' }"><input type="radio" v-model="f.sex" value="m" /><span>راجل</span></label>
        <label :class="{ on: f.sex === 'f' }"><input type="radio" v-model="f.sex" value="f" /><span>ست</span></label>
      </fieldset>
      <div class="nums">
        <label><span>السن</span><input class="input ltr" type="number" inputmode="numeric" v-model.number="f.age" min="14" max="80" /></label>
        <label><span>الوزن <small>كجم</small></span><input class="input ltr" type="number" inputmode="numeric" v-model.number="f.weight" min="35" max="250" /></label>
        <label><span>الطول <small>سم</small></span><input class="input ltr" type="number" inputmode="numeric" v-model.number="f.height" min="120" max="220" /></label>
      </div>
      <label class="field"><span>نشاطك في اليوم</span>
        <select class="input" v-model.number="f.act"><option v-for="a in activities" :key="a.v" :value="a.v">{{ a.label }}</option></select>
      </label>
    </form>

    <div class="result" aria-live="polite">
      <span class="r-label">احتياجك تقريبًا</span>
      <div class="r-num"><span class="num">{{ res?.kcal ?? '—' }}</span><small>سعرة / يوم</small></div>
      <div v-if="res" class="macros">
        <div><b class="num">{{ res.protein }}</b><span>بروتين (جم)</span></div>
        <div><b class="num">{{ res.carbs }}</b><span>كارب (جم)</span></div>
        <div><b class="num">{{ res.fat }}</b><span>دهون (جم)</span></div>
      </div>
      <p class="r-note">أرقام تقريبية مش حسابات دكتور. لو عندك مشكلة صحية اكشف الأول.</p>
      <slot :res="res" :input="f" />
    </div>
  </div>
</template>

<style scoped>
.calc { display: grid; gap: 18px; }
@media (min-width: 900px) { .calc { grid-template-columns: 1.15fr 1fr; gap: 28px; align-items: start; } }
.calc-form { display: grid; gap: 16px; }
.seg { border: 0; padding: 0; margin: 0; display: flex; gap: 6px; }
.seg legend { font-size: .85rem; color: rgba(255,255,255,.8); font-weight: 800; margin-bottom: 6px; padding: 0; }
.seg label { flex: 1; text-align: center; padding: 11px 8px; border-radius: 10px; background: var(--surface-2); border: 1.5px solid #2a2a2a; font-weight: 800; cursor: pointer; transform: skewX(-8deg); transition: border-color .15s, background .15s; }
.seg label span { display: inline-block; transform: skewX(8deg); }
.seg label.on { border-color: var(--orange); background: var(--orange); color: #0a0a0a; }
.seg label:focus-within { outline: 3px solid var(--amber); outline-offset: 2px; }
.seg input { position: absolute; opacity: 0; pointer-events: none; }
.nums { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.nums label { display: grid; gap: 6px; font-size: .85rem; font-weight: 800; color: rgba(255,255,255,.8); }
.nums small { font-weight: 400; color: var(--muted); }
.nums .input { text-align: center; font-size: 1.6rem; padding: 0 6px; }

.result { position: relative; overflow: hidden; display: grid; gap: 10px; padding: 22px; border-radius: 18px; background: #0f0f0f; border: 2px solid var(--orange); box-shadow: 6px 6px 0 var(--orange); align-content: start; }
.result::before { content: ''; position: absolute; inset: 0; background: var(--dots); opacity: .18; mask: linear-gradient(200deg, #000, transparent 45%); -webkit-mask: linear-gradient(200deg, #000, transparent 45%); pointer-events: none; }
.r-label { font-weight: 800; color: var(--amber); font-size: .9rem; }
.r-num { display: flex; align-items: baseline; gap: 10px; }
.r-num .num { font-size: clamp(4.4rem, 18vw, 6rem); line-height: .85; color: #fff; }
.r-num small { font-weight: 800; color: var(--muted); }
.macros { display: grid; grid-template-columns: repeat(3, 1fr); border-block: 1px solid #222; }
.macros div { display: grid; padding-block: 10px; }
.macros div + div { border-inline-start: 1px solid #222; padding-inline-start: 12px; }
.macros b { font-size: 1.9rem; line-height: 1; color: var(--orange); justify-self: start; }
.macros span { font-size: .75rem; color: var(--muted); font-weight: 700; }
.r-note { font-size: .78rem; color: var(--muted); }
</style>
