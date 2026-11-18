<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { programs, planById, egp, payMethods } from '../data.js'
import { store, hasAccess, doneDays, logWeight, logout, toast } from '../stores/app.js'

const router = useRouter()
const sub = computed(() => store.subscription && planById(store.subscription.plan))
const pending = computed(() => store.orders.filter((o) => o.status === 'pending'))
const mine = computed(() => programs.filter((p) => hasAccess(p.id) || doneDays(p.id).length))
const kg = ref(store.weights.at(-1)?.kg || store.calc?.weight || '')

function saveWeight() {
  const v = Number(kg.value)
  if (!v || v < 30 || v > 300) return toast('اكتب وزن مظبوط', 'err')
  logWeight(v).then(() => toast('اتسجّل ✓')).catch((e) => toast(e.message, 'err'))
}
const W = 300, H = 90
const chart = computed(() => {
  const ws = store.weights.slice(-12)
  if (ws.length < 2) return null
  const vals = ws.map((w) => w.kg), min = Math.min(...vals) - 1, max = Math.max(...vals) + 1
  const pts = ws.map((w, i) => [(i / (ws.length - 1)) * W, H - ((w.kg - min) / (max - min)) * H])
  return { d: pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' '), last: pts.at(-1) }
})
const diff = computed(() => {
  if (store.weights.length < 2) return null
  return +(store.weights.at(-1).kg - store.weights[0].kg).toFixed(1)
})
const methodLabel = (id) => payMethods.find((m) => m.id === id)?.label || id
const statusLabel = { paid: 'مدفوع', pending: 'بيتراجع', rejected: 'متقبلش', cancelled: 'اتلغى' }
async function out() { await logout().catch(() => {}); toast('اتسجّل خروجك'); router.push('/') }
</script>

<template>
  <div class="wrap acc">
    <header class="hello">
      <span class="avatar">{{ store.user.name.charAt(0) }}</span>
      <div>
        <h1>أهلًا يا {{ store.user.name }}</h1>
        <p class="muted en">{{ store.user.phone }}</p>
      </div>
    </header>

    <div class="grid">
      <!-- subscription -->
      <section class="card subc">
        <h2>اشتراكك</h2>
        <template v-if="sub">
          <p class="plan-n">{{ sub.name }} <span class="ok">نشط ✓</span></p>
          <p class="muted">من <span class="num">{{ store.subscription.since.slice(0, 10) }}</span>
            <template v-if="store.subscription.ends_at"> · لحد <span class="num">{{ store.subscription.ends_at.slice(0, 10) }}</span></template>
            <template v-else> · على طول</template>
          </p>
        </template>
        <template v-else>
          <p class="plan-n">مجاني</p>
          <p class="muted">أول 3 أيام من أي برنامج + المكتبة كلها.</p>
          <RouterLink to="/pricing" class="btn btn-primary btn-sm">افتح البرامج كاملة</RouterLink>
        </template>
        <div v-for="o in pending" :key="o.id" class="pend">
          <span>طلب {{ o.plan_name }} · {{ methodLabel(o.method) }} · بيتراجع</span>
          <RouterLink :to="`/checkout/result/${o.id}`">التفاصيل ←</RouterLink>
        </div>
      </section>

      <!-- weight -->
      <section class="card wt">
        <h2>وزنك</h2>
        <form class="wt-form" @submit.prevent="saveWeight">
          <input v-model="kg" class="input ltr" type="number" inputmode="decimal" step="0.1" aria-label="الوزن بالكيلو" />
          <span class="muted">كجم</span>
          <button class="btn btn-primary btn-sm">سجّل</button>
        </form>
        <svg v-if="chart" :viewBox="`-6 -6 ${W + 12} ${H + 12}`" class="spark" role="img" :aria-label="`تغيّر الوزن ${diff} كجم`">
          <path :d="chart.d" />
          <circle :cx="chart.last[0]" :cy="chart.last[1]" r="5" />
        </svg>
        <p v-if="diff !== null" class="muted">من أول تسجيل: <b class="num" :class="diff <= 0 ? 'amber' : ''">{{ diff > 0 ? '+' : '' }}{{ diff }}</b> كجم</p>
        <p v-else class="muted">سجّل وزنك كل أسبوع وهتشوف الخط هنا.</p>
      </section>

      <!-- calc -->
      <section v-if="store.calc?.kcal" class="card">
        <h2>سعراتك</h2>
        <p><b class="num big">{{ store.calc.kcal }}</b> سعرة/يوم تقريبًا</p>
        <div class="links"><RouterLink to="/free-plan">نظامك ←</RouterLink><RouterLink to="/calculator">احسب تاني ←</RouterLink></div>
      </section>
    </div>

    <!-- programs -->
    <section class="sec0">
      <h2>برامجك</h2>
      <div v-if="mine.length" class="progs">
        <RouterLink v-for="p in mine" :key="p.id" :to="`/programs/${p.id}`" class="prog">
          <img :src="p.img" alt="" loading="lazy" />
          <div>
            <b>{{ p.title }}</b>
            <div class="bar"><span :style="{ width: (doneDays(p.id).length / p.days) * 100 + '%' }"></span></div>
            <small class="muted"><span class="num">{{ doneDays(p.id).length }}/{{ p.days }}</span> يوم {{ hasAccess(p.id) ? '' : '· تجربة' }}</small>
          </div>
        </RouterLink>
      </div>
      <div v-else class="card empty">
        <p>لسه مبدأتش أي برنامج.</p>
        <RouterLink to="/workout/30/1" class="btn btn-primary btn-sm">جرّب اليوم الأول من تحدّي 30 يوم</RouterLink>
      </div>
    </section>

    <!-- orders -->
    <section v-if="store.orders.length" class="sec0">
      <h2>طلباتك</h2>
      <div class="orders">
        <RouterLink v-for="o in store.orders" :key="o.id" :to="`/checkout/result/${o.id}`" class="order">
          <span class="en">{{ o.id }}</span>
          <span>{{ o.plan_name }}</span>
          <span><span class="num">{{ egp(o.amount) }}</span> ج.م</span>
          <span class="st" :class="o.status">{{ statusLabel[o.status] }}</span>
        </RouterLink>
      </div>
    </section>

    <RouterLink v-if="store.user.is_admin" to="/admin/orders" class="admin-link">لوحة الأدمن (الطلبات والأسعار) ←</RouterLink>
    <button class="logout" @click="out">تسجيل خروج</button>
  </div>
</template>

<style scoped>
.acc { padding-block: 24px 40px; display: grid; gap: 26px; }
.hello { display: flex; align-items: center; gap: 14px; }
.avatar { width: 60px; height: 60px; border-radius: 50%; background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-family: var(--f-head); font-size: 2rem; }
.hello h1 { font-size: clamp(1.8rem, 6vw, 2.6rem); }
.grid { display: grid; gap: 12px; }
@media (min-width: 900px) { .grid { grid-template-columns: repeat(3, 1fr); } }
.card { padding: 18px; border-radius: 16px; background: #121212; border: 1px solid #232323; display: grid; gap: 8px; align-content: start; }
.card h2, .sec0 h2 { font-size: 1.4rem; }
.subc { border-color: rgba(255,106,0,.45); }
.plan-n { font-family: var(--f-head); font-size: 1.6rem; display: flex; gap: 10px; align-items: center; }
.ok { font-family: var(--f-body); font-size: .8rem; font-weight: 800; color: var(--ok); }
.subc .btn { justify-self: start; }
.pend { display: flex; justify-content: space-between; gap: 8px; font-size: .85rem; padding: 8px 10px; border-radius: 10px; background: rgba(255,176,0,.08); border: 1px solid rgba(255,176,0,.3); }
.pend a { color: var(--amber); font-weight: 800; white-space: nowrap; }
.wt-form { display: flex; align-items: center; gap: 8px; }
.wt-form .input { max-width: 130px; text-align: center; }
.spark { width: 100%; height: 90px; overflow: visible; }
.spark path { fill: none; stroke: var(--orange); stroke-width: 3; stroke-linejoin: round; stroke-linecap: round; }
.spark circle { fill: var(--amber); }
.big { font-size: 2.6rem; color: var(--amber); }
.links { display: flex; gap: 16px; font-weight: 800; color: var(--orange); }
.sec0 { display: grid; gap: 12px; }
.progs { display: grid; gap: 10px; }
@media (min-width: 800px) { .progs { grid-template-columns: repeat(2, 1fr); } }
.prog { display: grid; grid-template-columns: 70px 1fr; gap: 12px; align-items: center; padding: 10px; border-radius: 14px; background: #121212; border: 1px solid #232323; }
.prog:hover { border-color: var(--orange); }
.prog img { width: 70px; height: 70px; object-fit: cover; border-radius: 10px; }
.prog > div { display: grid; gap: 6px; }
.bar { height: 6px; background: #222; border-radius: 99px; overflow: hidden; }
.bar span { display: block; height: 100%; background: var(--orange); }
.empty { justify-items: start; }
.orders { display: grid; gap: 6px; }
.order { display: grid; grid-template-columns: 1fr auto; gap: 2px 12px; padding: 12px 14px; border-radius: 12px; background: #121212; border: 1px solid #232323; font-size: .9rem; }
@media (min-width: 700px) { .order { grid-template-columns: 140px 1fr auto auto; align-items: center; } }
.st { font-weight: 800; font-size: .8rem; }
.st.paid { color: var(--ok); }
.st.pending { color: var(--amber); }
.st.rejected { color: var(--danger); }
.admin-link { justify-self: start; color: var(--amber); font-weight: 800; }
.logout { justify-self: start; color: var(--muted); font-weight: 800; text-decoration: underline; }
</style>
