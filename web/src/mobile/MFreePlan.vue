<script setup>
import { computed } from 'vue'
import MAppBar from './MAppBar.vue'
import MRail from './MRail.vue'
import { store, nextWorkout } from '../stores/app.js'
import { starterDay, goals } from '../nutrition.js'
import { dietBySlug } from '../diets/data.js'
import DietPlanView from '../components/DietPlanView.vue'

const c = computed(() => store.calc)
const meals = computed(() => (c.value?.kcal ? starterDay(c.value.kcal) : []))
const goal = computed(() => goals.find((g) => g.id === c.value?.input?.goal)?.label)
const diet = computed(() => dietBySlug(c.value?.input?.diet))
const icons = { b: '☀️', l: '🍗', s: '🍎', d: '🌙' }
const next = computed(() => nextWorkout())
const share = () => navigator.share?.({ title: 'نظامي من فورمة', text: `احتياجي تقريبًا ${c.value?.kcal} سعرة في اليوم — فورمة`, url: location.origin + '/calculator' }).catch(() => {})
</script>

<template>
  <div class="m-screen with-cta">
    <MAppBar title="نظامك" fallback="/calculator">
      <button v-if="c?.kcal" class="mib" aria-label="شارك" @click="share"><svg viewBox="0 0 24 24"><path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7M16 6l-4-4-4 4M12 2v13" /></svg></button>
    </MAppBar>

    <div v-if="!c?.kcal" class="m-empty">
      <p>لسه محسبتش سعراتك.</p>
      <RouterLink to="/calculator" class="btn btn-primary">احسب دلوقتي</RouterLink>
    </div>

    <template v-else>
      <div v-if="diet" class="m-pad"><DietPlanView :calc="c" mobile /></div>
      <template v-else>
      <div class="m-pad">
        <div class="sum">
          <div class="m-halftone"></div>
          <small class="amber">احتياجك تقريبًا{{ goal ? ' · ' + goal : '' }}</small>
          <div class="k"><span class="num">{{ c.kcal }}</span><small class="muted">سعرة/يوم</small></div>
          <div class="mac">
            <span><b class="num">{{ c.protein }}</b><small>بروتين جم</small></span>
            <span><b class="num">{{ c.carbs }}</b><small>كارب جم</small></span>
            <span><b class="num">{{ c.fat }}</b><small>دهون جم</small></span>
          </div>
        </div>
      </div>

      <MRail title="يومك" :total="meals.length" dots>
        <article v-for="m in meals" :key="m.id" class="meal m-card">
          <header><h3>{{ icons[m.id] }} {{ m.name }}</h3><b class="num amber">{{ m.kcal }}</b></header>
          <ul><li v-for="i in m.items" :key="i">{{ i }}</li></ul>
        </article>
      </MRail>
      </template>
      <RouterLink to="/calculator" class="m-pad change">غيّر النظام أو بياناتك ‹</RouterLink>

      <p class="m-pad muted note">قلّل السكر والعصاير والمقليات واشرب مية كتير. ولو عندك سكر أو ضغط أو أي مشكلة صحية، اكشف الأول.</p>
    </template>

    <div class="m-cta">
      <RouterLink v-if="next" :to="`/workout/${next.program.id}/${next.day}`" class="btn btn-primary">▶ ابدأ التمرين ببلاش</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.sum { position: relative; overflow: hidden; border-radius: 24px; padding: 18px; border: 2px solid var(--orange); box-shadow: 6px 6px 0 var(--orange); background: #0f0f0f; display: grid; gap: 2px; margin-top: 4px; }
.sum .m-halftone { -webkit-mask: linear-gradient(200deg, #000, transparent 45%); mask: linear-gradient(200deg, #000, transparent 45%); }
.sum small.amber { font-weight: 800; font-size: .8rem; position: relative; }
.k { display: flex; align-items: baseline; gap: 8px; position: relative; }
.k .num { font-size: 4.4rem; line-height: .9; }
.mac { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 10px; padding-top: 10px; border-top: 1px solid #262626; position: relative; }
.mac span { display: grid; }
.mac b { font-size: 1.7rem; line-height: 1; color: var(--orange); justify-self: start; }
.mac small { font-size: .7rem; color: var(--muted); font-weight: 800; }
.meal { width: calc(100vw - 80px); max-width: 300px; padding: 16px; display: grid; gap: 8px; align-content: start; }
.meal header { display: flex; justify-content: space-between; align-items: baseline; }
.meal h3 { font-size: 1.45rem; }
.meal header b { font-size: 1.5rem; }
.meal ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 6px; }
.meal li::before { content: '• '; color: var(--orange); font-weight: 900; }
.change { display: block; margin-top: 12px; font-weight: 800; color: var(--orange); font-size: .88rem; }
.note { font-size: .78rem; margin-top: 14px; }
</style>
