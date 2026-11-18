<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import NotFound from './NotFound.vue'
import { planById, programById, methodById, egp } from '../data.js'
import { store, fetchOrder, loadMe } from '../stores/app.js'

const route = useRoute()
const missing = ref(false)
const o = computed(() => store.orders.find((x) => x.id === route.params.order))
const plan = computed(() => o.value && planById(o.value.plan))
const startProgram = computed(() => plan.value && programById(plan.value.unlocks[0]))

let timer
async function refresh() {
  try {
    const before = o.value?.status
    const order = await fetchOrder(route.params.order)
    if (order.status === 'paid' && before !== 'paid') await loadMe() // pull the new subscription/unlocks
    if (order.status !== 'pending') clearInterval(timer)
  } catch (e) {
    if (e.status === 404) missing.value = true
    clearInterval(timer)
  }
}
onMounted(() => { refresh(); timer = setInterval(refresh, 30000) })
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <NotFound v-if="missing" />
  <section v-else-if="o" class="wrap res">
    <template v-if="o.status === 'paid'">
      <div class="icon ok" aria-hidden="true">✓</div>
      <h1>عاش! <span class="hl">اشتراكك اتفعّل</span></h1>
      <p class="muted">{{ o.plan_name }} · <span class="num">{{ egp(o.amount) }}</span> ج.م · رقم الطلب <span class="en">{{ o.id }}</span></p>
      <div class="btns">
        <RouterLink v-if="startProgram" :to="`/programs/${startProgram.id}`" class="btn btn-primary">ابدأ {{ startProgram.title }}</RouterLink>
        <RouterLink to="/account" class="btn btn-ghost">حسابي</RouterLink>
      </div>
    </template>

    <template v-else-if="o.status === 'rejected'">
      <div class="icon bad" aria-hidden="true">!</div>
      <h1>الإيصال <span class="hl">متقبلش</span></h1>
      <p v-if="o.note" class="reason">{{ o.note }}</p>
      <p class="muted">لو حوّلت فعلًا، ابعت الإيصال تاني أو كلّمنا على الواتساب.</p>
      <div class="btns">
        <RouterLink :to="`/checkout/${o.plan}`" class="btn btn-primary">ابعت إيصال تاني</RouterLink>
        <RouterLink to="/account" class="btn btn-ghost">حسابي</RouterLink>
      </div>
    </template>

    <template v-else>
      <div class="icon wait" aria-hidden="true">🧾</div>
      <h1>الإيصال <span class="hl">وصلنا</span></h1>
      <p class="muted">بنراجع تحويل {{ methodById(o.method)?.label || o.method }} ونفعّل اشتراكك خلال <span class="num">{{ methodById(o.method)?.review_hours || 24 }}</span> ساعة بالكتير.</p>
      <div class="facts">
        <div><small>رقم الطلب</small><b class="en">{{ o.id }}</b></div>
        <div><small>الباقة</small><b>{{ o.plan_name }}</b></div>
        <div><small>المبلغ</small><b><span class="num">{{ egp(o.amount) }}</span> ج.م</b></div>
      </div>
      <p class="muted small">الصفحة دي بتتحدّث لوحدها. تقدر تقفلها وترجعلها من «حسابي».</p>
      <div class="btns"><RouterLink to="/account" class="btn btn-ghost">حسابي</RouterLink></div>
    </template>
  </section>
  <section v-else class="wrap res"><span class="spin" aria-label="بنحمّل"></span></section>
</template>

<style scoped>
.res { padding-block: 40px 60px; display: grid; gap: 14px; justify-items: center; text-align: center; max-width: 620px; }
.icon { width: 88px; height: 88px; border-radius: 50%; display: grid; place-items: center; font-size: 2.6rem; }
.icon.ok { background: var(--orange); color: #0a0a0a; box-shadow: 0 0 0 12px rgba(255,106,0,.18); font-weight: 900; }
.icon.wait { background: #1a1a1a; border: 2px solid #333; }
.icon.bad { background: rgba(255,77,77,.15); border: 2px solid var(--danger); color: var(--danger); font-weight: 900; }
h1 { font-size: clamp(2.2rem, 8vw, 3.2rem); }
.reason { padding: 10px 16px; border-radius: 12px; background: rgba(255,77,77,.08); border: 1px solid rgba(255,77,77,.35); font-weight: 700; }
.facts { display: grid; grid-template-columns: repeat(3, 1fr); width: 100%; border: 1px solid #222; border-radius: 14px; background: #121212; }
.facts div { display: grid; padding: 12px 6px; }
.facts div + div { border-inline-start: 1px solid #222; }
.facts small { color: var(--muted); font-size: .75rem; font-weight: 700; }
.facts b { font-size: 1rem; }
.facts .en, .facts .num { font-size: 1.3rem; }
.small { font-size: .8rem; }
.btns { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 8px; }
.spin { width: 44px; height: 44px; border-radius: 50%; border: 4px solid #333; border-top-color: var(--orange); animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
