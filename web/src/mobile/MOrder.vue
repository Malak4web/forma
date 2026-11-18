<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import MAppBar from './MAppBar.vue'
import NotFound from '../pages/NotFound.vue'
import { planById, programById, methodById, egp } from '../data.js'
import { store, fetchOrder, loadMe, nextWorkout } from '../stores/app.js'

const route = useRoute()
const missing = ref(false)
const o = computed(() => store.orders.find((x) => x.id === route.params.order))
const plan = computed(() => o.value && planById(o.value.plan))
const startProgram = computed(() => plan.value && programById(plan.value.unlocks[0]))
const next = computed(() => nextWorkout())

let timer
async function refresh() {
  try {
    const before = o.value?.status
    const order = await fetchOrder(route.params.order)
    if (order.status === 'paid' && before !== 'paid') await loadMe()
    if (order.status !== 'pending') clearInterval(timer)
  } catch (e) {
    if (e.status === 404) missing.value = true
    clearInterval(timer)
  }
}
onMounted(() => { refresh(); timer = setInterval(refresh, 30000) })
onBeforeUnmount(() => clearInterval(timer))
const steps = computed(() => {
  const s = o.value?.status
  return [
    { t: 'اتبعت', d: `${methodById(o.value?.method)?.label || ''} · ${egp(o.value?.amount || 0)} ج.م`, st: 'ok' },
    { t: s === 'rejected' ? 'متقبلش' : 'بيتراجع', d: s === 'rejected' ? o.value?.note || 'راجع التحويل وابعت تاني' : 'بنشوف التحويل', st: s === 'pending' ? 'now' : s === 'rejected' ? 'bad' : 'ok' },
    { t: 'اتفعّل', d: s === 'paid' ? 'يلا نتمرن 👊' : 'هيوصلك واتساب', st: s === 'paid' ? 'ok' : 'wait' },
  ]
})
</script>

<template>
  <NotFound v-if="missing" />
  <div v-else class="m-screen with-cta">
    <MAppBar title="طلبك" close fallback="/account"><span v-if="o" class="en muted ref">{{ o.id }}</span></MAppBar>
    <div v-if="!o" class="m-spin"></div>
    <section v-else class="m-pad body">
      <div class="ic" :class="o.status">{{ o.status === 'paid' ? '✓' : o.status === 'rejected' ? '!' : '🧾' }}</div>
      <h1 v-if="o.status === 'paid'">عاش! <span class="hl">اشتراكك اتفعّل</span></h1>
      <h1 v-else-if="o.status === 'rejected'">الإيصال <span class="hl">متقبلش</span></h1>
      <h1 v-else>الإيصال <span class="hl">وصلنا</span></h1>
      <p class="muted">{{ o.status === 'pending' ? `بنراجعه ونفعّل ${o.plan_name} خلال ${methodById(o.method)?.review_hours || 24} ساعة بالكتير.` : o.plan_name }}</p>

      <ol class="tl m-card">
        <li v-for="s in steps" :key="s.t" :class="s.st">
          <span class="dot">{{ s.st === 'ok' ? '✓' : s.st === 'bad' ? '!' : '' }}</span>
          <span><b>{{ s.t }}</b><small>{{ s.d }}</small></span>
        </li>
      </ol>
      <small v-if="o.status === 'pending'" class="muted">الصفحة بتتحدّث لوحدها.</small>
    </section>

    <div v-if="o" class="m-cta">
      <RouterLink v-if="o.status === 'paid' && startProgram" :to="`/programs/${startProgram.id}`" class="btn btn-primary">▶ ابدأ {{ startProgram.title }}</RouterLink>
      <RouterLink v-else-if="o.status === 'rejected'" :to="`/checkout/${o.plan}`" class="btn btn-primary">ابعت إيصال تاني</RouterLink>
      <RouterLink v-else-if="next" :to="`/workout/${next.program.id}/${next.day}`" class="btn btn-ghost">جرّب الأيام المجانية لحد ما يتفعّل</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.ref { font-size: 1rem; }
.body { padding-top: 22px; display: grid; gap: 14px; justify-items: center; text-align: center; }
.ic { width: 96px; height: 96px; border-radius: 30px; display: grid; place-items: center; font-size: 2.6rem; background: #1a1a1a; border: 2px solid #333; }
.ic.paid { background: var(--orange); color: #0a0a0a; border: 0; transform: rotate(-8deg); box-shadow: 6px 6px 0 #fff; font-weight: 900; }
.ic.rejected { background: rgba(255,77,77,.12); border-color: var(--danger); color: var(--danger); font-weight: 900; }
.body h1 { font-size: 2.2rem; }
.tl { width: 100%; list-style: none; margin: 4px 0 0; padding: 16px; display: grid; text-align: start; }
.tl li { display: flex; gap: 12px; padding-bottom: 16px; position: relative; }
.tl li:last-child { padding-bottom: 0; }
.tl li:not(:last-child)::before { content: ''; position: absolute; inset-inline-start: 12px; top: 28px; bottom: 2px; width: 2px; background: #262626; }
.tl li > span:last-child { display: grid; }
.tl small { color: var(--muted); font-size: .8rem; }
.dot { width: 26px; height: 26px; flex: none; border-radius: 50%; display: grid; place-items: center; font-size: .8rem; font-weight: 900; background: #222; }
.ok .dot { background: var(--ok); color: #0a0a0a; }
.now .dot { background: transparent; border: 3px solid var(--amber); }
.now b { color: var(--amber); }
.bad .dot { background: var(--danger); color: #fff; }
.bad b { color: var(--danger); }
.wait b { color: var(--muted); }
</style>
