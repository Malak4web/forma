<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import MAppBar from './MAppBar.vue'
import MSeg from './MSeg.vue'
import { programs, planById, egp, methodById } from '../data.js'
import { store, hasAccess, doneDays, logWeight, logout, toast } from '../stores/app.js'

const router = useRouter()
const sub = computed(() => store.subscription && planById(store.subscription.plan))
const mine = computed(() => programs.filter((p) => hasAccess(p.id) || doneDays(p.id).length))
const totalDone = computed(() => Object.values(store.completed).reduce((n, d) => n + d.length, 0))
const lastKg = computed(() => store.weights.at(-1)?.kg)
const diff = computed(() => (store.weights.length > 1 ? +(store.weights.at(-1).kg - store.weights[0].kg).toFixed(1) : null))
const tab = ref('progs')
const tabs = [{ id: 'progs', label: 'برامجي' }, { id: 'weight', label: 'وزني' }, { id: 'orders', label: 'طلباتي' }]

const kg = ref(lastKg.value || store.calc?.weight || '')
const saving = ref(false)
async function saveWeight() {
  const v = Number(kg.value)
  if (!v || v < 30 || v > 300) return toast('اكتب وزن مظبوط', 'err')
  saving.value = true
  try { await logWeight(v); toast('اتسجّل ✓') } catch (e) { toast(e.message, 'err') } finally { saving.value = false }
}
const W = 300, H = 90
const chart = computed(() => {
  const ws = store.weights.slice(-12)
  if (ws.length < 2) return null
  const vals = ws.map((w) => w.kg), min = Math.min(...vals) - 1, max = Math.max(...vals) + 1
  const pts = ws.map((w, i) => [(i / (ws.length - 1)) * W, H - ((w.kg - min) / (max - min)) * H])
  return { d: pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' '), last: pts.at(-1) }
})
const statusLabel = { paid: 'مدفوع', pending: 'بيتراجع', rejected: 'متقبلش', cancelled: 'اتلغى' }
const orders = computed(() => store.orders.slice(0, 3))
async function out() { await logout().catch(() => {}); toast('اتسجّل خروجك'); router.push('/') }
</script>

<template>
  <div class="m-screen with-tabs">
    <MAppBar title="حسابي" :back="false">
      <RouterLink v-if="store.user.is_admin" to="/admin/orders" class="mib" aria-label="لوحة الأدمن">
        <svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z" /></svg>
      </RouterLink>
    </MAppBar>

    <div class="m-pad prof">
      <span class="ava">{{ store.user.name.charAt(0) }}</span>
      <span class="who"><h2>{{ store.user.name }}</h2><small class="en muted">{{ store.user.phone }}</small></span>
      <RouterLink :to="sub ? '/pricing' : '/pricing'" class="m-tag" :class="{ free: !sub }">{{ sub ? sub.name : 'مجاني' }}</RouterLink>
    </div>

    <div class="m-pad">
      <div class="m-card stats">
        <div><b class="num">{{ totalDone }}</b><span>يوم</span></div>
        <div><b class="num">{{ lastKg ?? '—' }}</b><span>وزنك</span></div>
        <div><b class="num" :class="{ amber: diff !== null && diff <= 0 }">{{ diff === null ? '—' : (diff > 0 ? '+' : '') + diff }}</b><span>التغيير</span></div>
      </div>
    </div>

    <div class="m-pad seg"><MSeg v-model="tab" :options="tabs" /></div>

    <div class="m-pad pane">
      <!-- programs -->
      <template v-if="tab === 'progs'">
        <div v-if="mine.length" class="m-stack">
          <RouterLink v-for="p in mine.slice(0, 3)" :key="p.id" :to="`/programs/${p.id}`" class="m-li">
            <img :src="p.img" alt="" />
            <span class="x">
              <b>{{ p.title }}</b>
              <span class="bar"><i :style="{ width: (doneDays(p.id).length / p.days) * 100 + '%' }"></i></span>
              <small><span class="num">{{ doneDays(p.id).length }}/{{ p.days }}</span> يوم {{ hasAccess(p.id) ? '' : '· تجربة' }}</small>
            </span>
            <span class="go">▶</span>
          </RouterLink>
        </div>
        <div v-else class="m-empty"><p>لسه مبدأتش أي برنامج.</p><RouterLink to="/programs" class="btn btn-primary btn-sm">اختار برنامج</RouterLink></div>
      </template>

      <!-- weight -->
      <template v-else-if="tab === 'weight'">
        <svg v-if="chart" :viewBox="`-6 -6 ${W + 12} ${H + 12}`" class="spark" role="img" :aria-label="`تغيّر الوزن ${diff} كجم`">
          <path :d="chart.d" /><circle :cx="chart.last[0]" :cy="chart.last[1]" r="5" />
        </svg>
        <p v-else class="muted hint">سجّل وزنك كل أسبوع وهتشوف الخط هنا.</p>
        <form class="kg" @submit.prevent="saveWeight">
          <input v-model="kg" class="m-input ltr" type="number" inputmode="decimal" step="0.1" aria-label="الوزن بالكيلو" />
          <span class="muted">كجم</span>
          <button class="btn btn-primary btn-sm" :disabled="saving">سجّل</button>
        </form>
      </template>

      <!-- orders -->
      <template v-else>
        <div v-if="orders.length" class="m-stack">
          <RouterLink v-for="o in orders" :key="o.id" :to="`/checkout/result/${o.id}`" class="m-li">
            <span class="x">
              <b class="en">{{ o.id }}</b>
              <small>{{ o.plan_name }} · <span class="num">{{ egp(o.amount) }}</span> ج.م · {{ methodById(o.method)?.label || o.method }}</small>
            </span>
            <span class="st" :class="o.status">{{ statusLabel[o.status] }}</span>
          </RouterLink>
        </div>
        <div v-else class="m-empty"><p>مفيش طلبات لسه.</p><RouterLink to="/pricing" class="btn btn-primary btn-sm">شوف الباقات</RouterLink></div>
      </template>
    </div>

    <div class="m-pad">
      <nav class="m-card menu">
        <RouterLink :to="store.calc?.kcal ? '/free-plan' : '/calculator'"><span>🔥</span><b>{{ store.calc?.kcal ? `نظامي (${store.calc.kcal} سعرة)` : 'احسب سعراتك' }}</b><i>‹</i></RouterLink>
        <RouterLink v-if="!sub" to="/pricing"><span>⭐</span><b>الباقات</b><i>‹</i></RouterLink>
        <button @click="out"><span>🚪</span><b class="bad">تسجيل خروج</b></button>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.prof { display: flex; align-items: center; gap: 12px; padding-top: 4px; }
.ava { width: 62px; height: 62px; flex: none; border-radius: 20px; transform: rotate(-6deg); background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-family: var(--f-head); font-size: 2rem; box-shadow: 4px 4px 0 #fff; }
.who { flex: 1; min-width: 0; display: grid; }
.who h2 { font-size: 1.7rem; line-height: 1.1; }
.who small { font-size: 1rem; justify-self: start; }
.prof .m-tag { background: var(--orange); }
.prof .m-tag.free { background: #262626; color: var(--muted); }
.stats { display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; margin-top: 14px; }
.stats div { display: grid; padding: 12px 4px; }
.stats div + div { border-inline-start: 1px solid #262626; }
.stats b { font-size: 1.9rem; line-height: 1; }
.stats span { font-size: .7rem; color: var(--muted); font-weight: 800; }
.seg { margin-top: 14px; }
.pane { padding-top: 12px; min-height: 196px; }
.bar { display: block; height: 6px; background: #262626; border-radius: 9px; margin: 6px 0; overflow: hidden; }
.bar i { display: block; height: 100%; background: var(--orange); }
.spark { width: 100%; height: 100px; overflow: visible; }
.spark path { fill: none; stroke: var(--orange); stroke-width: 3; stroke-linejoin: round; stroke-linecap: round; }
.spark circle { fill: var(--amber); }
.hint { padding: 20px 0; text-align: center; }
.kg { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
.kg .m-input { flex: 1; text-align: center; }
.st { font-weight: 800; font-size: .78rem; }
.st.paid { color: var(--ok); } .st.pending { color: var(--amber); } .st.rejected { color: var(--danger); }
.menu { overflow: hidden; margin-top: 4px; }
.menu a, .menu button { display: flex; align-items: center; gap: 12px; width: 100%; padding: 13px 14px; text-align: start; }
.menu > * + * { border-top: 1px solid #1f1f1f; }
.menu b { flex: 1; }
.menu i { font-style: normal; color: var(--muted); font-size: 1.3rem; }
.bad { color: var(--danger); }
</style>
