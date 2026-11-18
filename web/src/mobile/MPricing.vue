<script setup>
import { ref, computed } from 'vue'
import MAppBar from './MAppBar.vue'
import MSeg from './MSeg.vue'
import { plans, payMethods, egp } from '../data.js'
import { store } from '../stores/app.js'

const sel = ref((plans.find((p) => p.highlight) || plans.at(-1))?.id)
const plan = computed(() => plans.find((p) => p.id === sel.value))
const options = computed(() => plans.map((p) => ({ id: p.id, label: p.name.replace('فورمة ', '') })))
const current = computed(() => store.subscription?.plan === sel.value)
const link = computed(() => {
  const p = plan.value
  if (!p) return '/'
  if (!p.price) return store.user ? '/programs' : '/register'
  return store.user ? `/checkout/${p.id}` : `/register?next=/checkout/${p.id}`
})
// swipe between plans
let sx = null
const ts = (e) => (sx = e.touches[0].clientX)
const te = (e) => {
  if (sx === null) return
  const dx = e.changedTouches[0].clientX - sx
  sx = null
  if (Math.abs(dx) < 50) return
  const i = plans.findIndex((p) => p.id === sel.value)
  const j = dx > 0 ? i + 1 : i - 1 // RTL: swipe right = next
  if (plans[j]) sel.value = plans[j].id
}
</script>

<template>
  <div class="m-screen with-cta">
    <MAppBar title="الباقات" fallback="/" />
    <div class="m-pad"><MSeg v-model="sel" :options="options" /></div>

    <Transition name="fade" mode="out-in">
      <div v-if="plan" :key="plan.id" class="m-pad wrapc" @touchstart.passive="ts" @touchend="te">
        <article class="pl" :class="{ hi: plan.highlight }">
          <span v-if="plan.tag || plan.highlight" class="m-tag">{{ plan.highlight ? 'الأوفر' : plan.tag }}</span>
          <h2>{{ plan.name }}</h2>
          <p class="price"><span class="num">{{ egp(plan.price) }}</span> ج.م <small class="muted">{{ plan.price ? plan.period : 'على طول' }}</small></p>
          <ul><li v-for="f in plan.features" :key="f">{{ f }}</li></ul>
        </article>
      </div>
    </Transition>

    <div class="m-pad pays">
      <small class="muted">الدفع بـ</small>
      <span v-for="m in payMethods" :key="m.id" class="pm" :class="m.id">{{ m.label }}</span>
    </div>

    <div class="m-cta">
      <span v-if="current" class="btn btn-ghost cur">باقتك الحالية ✓</span>
      <RouterLink v-else :to="link" class="btn btn-primary">{{ plan?.cta }}</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.wrapc { padding-top: 16px; }
.pl { position: relative; display: grid; gap: 10px; padding: 22px; border-radius: 26px; background: var(--surface); border: 1px solid #262626; min-height: 360px; align-content: start; }
.pl.hi { border: 2px solid var(--orange); box-shadow: 8px 8px 0 var(--orange); background: linear-gradient(170deg, rgba(255,106,0,.16), #121212 55%); }
.pl .m-tag { justify-self: start; }
.pl h2 { font-size: 2rem; }
.price { font-family: var(--f-head); font-size: 1.15rem; }
.price .num { font-size: 4rem; line-height: .9; }
.price small { font-family: var(--f-body); font-weight: 800; font-size: .85rem; }
.pl ul { margin: 6px 0 0; padding: 0; list-style: none; display: grid; gap: 10px; }
.pl li { display: flex; gap: 10px; font-weight: 600; }
.pl li::before { content: '✓'; color: var(--orange); font-weight: 900; }
.pays { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px; }
.pm { font-size: .75rem; font-weight: 900; padding: 5px 12px; border-radius: 8px; color: #fff; }
.pm.vodafone_cash { background: #e60000; }
.pm.instapay { background: #6b2fa0; }
.cur { color: var(--ok); border-color: var(--ok); }
.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
