<script setup>
import { ref, reactive, onMounted } from 'vue'
import AdminNav from '../components/AdminNav.vue'
import { api, fieldErrors } from '../api.js'
import { loadContent } from '../data.js'
import { toast } from '../stores/app.js'

const plans = ref([])
const methods = ref([])
const loading = ref(true)
const coachOpen = ref(false)
const busy = reactive({})
const errs = reactive({}) // { [id]: { field: msg } }

// features edited as one-per-line text
const toForm = (p) => ({ ...p, featuresText: p.features.join('\n'), tag: p.tag || '' })

async function load() {
  loading.value = true
  try {
    const s = await api('/admin/settings')
    plans.value = s.plans.map(toForm)
    methods.value = s.payment_methods.map((m) => ({ ...m, account: m.account || '' }))
    coachOpen.value = !!s.coach?.open_to_free
  } catch (e) { toast(e.message, 'err') }
  finally { loading.value = false }
}
onMounted(load)

async function save(kind, item, body) {
  busy[item.id] = true
  errs[item.id] = {}
  try {
    const res = await api(kind === 'plan' ? `/admin/plans/${item.id}` : `/admin/payment-methods/${item.id}`, { method: 'PUT', body })
    Object.assign(item, kind === 'plan' ? toForm(res) : { ...res, account: res.account || '' })
    await loadContent() // so pricing/checkout pages show the new values right away
    toast('اتحفظ ✓')
  } catch (e) {
    errs[item.id] = fieldErrors(e)
    if (!Object.keys(errs[item.id]).length) toast(e.message, 'err')
  } finally {
    busy[item.id] = false
  }
}

const savePlan = (p) => save('plan', p, {
  name: p.name, price: Number(p.price), cta: p.cta, tag: p.tag || null, highlight: p.highlight, is_active: p.is_active,
  features: p.featuresText.split('\n').map((s) => s.trim()).filter(Boolean),
})
const saveMethod = (m) => save('method', m, { enabled: m.enabled, account: m.account.trim() || null, hint: m.hint })
async function saveCoach() {
  try { await api('/admin/coach-settings', { method: 'PUT', body: { open_to_free: coachOpen.value } }); toast('اتحفظ ✓') } catch (e) { coachOpen.value = !coachOpen.value; toast(e.message, 'err') }
}
const err = (id, f) => errs[id]?.[f] || errs[id]?.[`${f}.0`]
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <h1>الأسعار والدفع</h1>
    <p class="muted">أي تعديل بيظهر على الموقع فورًا. الطلبات القديمة بتفضل بسعرها وقت الطلب.</p>

    <div v-if="loading" class="loading"><span class="spin" aria-label="بنحمّل"></span></div>
    <template v-else>
      <!-- PAYMENT METHODS -->
      <section class="block">
        <h2>طرق الدفع</h2>
        <form v-for="m in methods" :key="m.id" class="card" @submit.prevent="saveMethod(m)">
          <header class="card-h">
            <span class="m-ic" :class="m.id" aria-hidden="true"></span>
            <b>{{ m.label }}</b>
            <label class="switch">
              <input v-model="m.enabled" type="checkbox" />
              <span>{{ m.enabled ? 'شغّالة' : 'مقفولة' }}</span>
            </label>
          </header>
          <label class="field">
            <span>{{ m.id === 'vodafone_cash' ? 'رقم فودافون كاش اللي العملاء هيحوّلوا عليه' : 'عنوان InstaPay (أو الرقم)' }}</span>
            <input v-model="m.account" class="input ltr" :class="{ invalid: err(m.id, 'account') }" :placeholder="m.id === 'vodafone_cash' ? '01xxxxxxxxx' : 'name@instapay'" />
            <small v-if="err(m.id, 'account')" class="err">{{ err(m.id, 'account') }}</small>
          </label>
          <label class="field">
            <span>الجملة اللي تحت الاسم</span>
            <input v-model="m.hint" class="input" />
          </label>
          <small v-if="err(m.id, 'enabled')" class="err">{{ err(m.id, 'enabled') }}</small>
          <button class="btn btn-primary btn-sm" :disabled="busy[m.id]">{{ busy[m.id] ? 'بنحفظ…' : 'احفظ' }}</button>
        </form>
      </section>

      <!-- COACH ACCESS -->
      <section class="block">
        <h2>الكوتش</h2>
        <div class="card">
          <label class="check"><input v-model="coachOpen" type="checkbox" @change="saveCoach" /> افتح الكلام مع الكوتش ومتابعة الأسبوع للمستخدمين المجانيين كمان</label>
          <p class="muted small">مقفول = المحادثة والمتابعة للمشتركين في باقة مدفوعة بس. فتحه مفيد في الأول عشان تبني ثقة، بس هيزوّد الرسايل عليك. الرسايل بتوصل في «رسايل المشتركين».</p>
        </div>
      </section>

      <!-- PLANS -->
      <section class="block">
        <h2>الباقات</h2>
        <form v-for="p in plans" :key="p.id" class="card" @submit.prevent="savePlan(p)">
          <header class="card-h">
            <b>{{ p.name }}</b>
            <small class="muted">{{ p.period_label }}{{ p.duration_days ? ` · ${p.duration_days} يوم` : '' }}</small>
            <label v-if="p.id !== 'free'" class="switch">
              <input v-model="p.is_active" type="checkbox" />
              <span>{{ p.is_active ? 'ظاهرة' : 'مخفية' }}</span>
            </label>
          </header>
          <p v-if="p.unlocks.length" class="muted small">بتفتح: {{ p.unlocks.join('، ') }}</p>
          <div class="row2">
            <label class="field">
              <span>الاسم</span>
              <input v-model="p.name" class="input" :class="{ invalid: err(p.id, 'name') }" />
              <small v-if="err(p.id, 'name')" class="err">{{ err(p.id, 'name') }}</small>
            </label>
            <label class="field">
              <span>السعر (ج.م)</span>
              <input v-model="p.price" class="input ltr" :class="{ invalid: err(p.id, 'price') }" type="number" inputmode="numeric" min="0" :disabled="p.id === 'free'" />
              <small v-if="err(p.id, 'price')" class="err">{{ err(p.id, 'price') }}</small>
            </label>
          </div>
          <label class="field">
            <span>المميزات (كل ميزة في سطر)</span>
            <textarea v-model="p.featuresText" class="input ta" rows="4" :class="{ invalid: err(p.id, 'features') }"></textarea>
            <small v-if="err(p.id, 'features')" class="err">{{ err(p.id, 'features') }}</small>
          </label>
          <div class="row2">
            <label class="field"><span>نص الزرار</span><input v-model="p.cta" class="input" /></label>
            <label class="field"><span>شارة (اختياري)</span><input v-model="p.tag" class="input" placeholder="الأكتر طلبًا" /></label>
          </div>
          <label class="check"><input v-model="p.highlight" type="checkbox" /> مميّزة (إطار برتقاني + «الأوفر»)</label>
          <button class="btn btn-primary btn-sm" :disabled="busy[p.id]">{{ busy[p.id] ? 'بنحفظ…' : 'احفظ' }}</button>
        </form>
      </section>
    </template>
  </div>
</template>

<style scoped>
.adm { padding-block: 24px 60px; display: grid; gap: 14px; max-width: 900px; }
h1 { font-size: 2.4rem; }
.loading { padding: 40px; display: grid; place-items: center; }
.spin { width: 44px; height: 44px; border-radius: 50%; border: 4px solid #333; border-top-color: var(--orange); animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.block { display: grid; gap: 10px; margin-top: 12px; }
.block h2 { font-size: 1.6rem; }
.card { display: grid; gap: 12px; padding: 16px; border-radius: 16px; background: #121212; border: 1px solid #242424; }
.card .btn { justify-self: start; }
.card-h { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.card-h b { font-family: var(--f-head); font-weight: 400; font-size: 1.35rem; }
.m-ic { width: 28px; height: 28px; border-radius: 8px; }
.m-ic.vodafone_cash { background: #e60000; }
.m-ic.instapay { background: linear-gradient(135deg, #5b2a86, #8d3bd1); }
.switch { margin-inline-start: auto; display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: .85rem; cursor: pointer; }
.switch input { width: 20px; height: 20px; accent-color: var(--orange); }
.row2 { display: grid; gap: 12px; }
@media (min-width: 640px) { .row2 { grid-template-columns: 1fr 1fr; } }
.ta { min-height: 110px; padding-block: 10px; line-height: 1.7; resize: vertical; }
.check { display: flex; gap: 8px; align-items: center; font-weight: 700; font-size: .9rem; }
.check input { width: 18px; height: 18px; accent-color: var(--orange); }
.err { color: var(--danger); font-weight: 700; font-size: .85rem; }
.small { font-size: .82rem; }
</style>
