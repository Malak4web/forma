<script setup>
import { ref, reactive, computed, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MAppBar from './MAppBar.vue'
import MSeg from './MSeg.vue'
import NotFound from '../pages/NotFound.vue'
import { planById, payMethods, methodById, egp } from '../data.js'
import { store, createOrder, toast } from '../stores/app.js'
import { fieldErrors } from '../api.js'

const route = useRoute()
const router = useRouter()
const plan = computed(() => planById(route.params.plan))
const method = ref(payMethods[0]?.id)
const m = computed(() => methodById(method.value))
const methodOptions = computed(() => payMethods.map((x) => ({ id: x.id, label: (x.id === 'vodafone_cash' ? '🔴 ' : '🟣 ') + x.label })))
const payerPhone = ref(store.user?.phone || '')
const file = ref(null)
const preview = ref('')
const busy = ref(false)
const err = reactive({})
const pending = computed(() => store.orders.find((o) => o.plan === plan.value?.id && o.status === 'pending'))

function onFile(e) {
  const f = e.target.files?.[0]
  err.receipt = ''
  if (!f) return
  if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { err.receipt = 'الإيصال لازم يكون صورة (JPG أو PNG)'; e.target.value = ''; return }
  if (f.size > 5 * 1024 * 1024) { err.receipt = 'الصورة أكبر من 5 ميجا'; e.target.value = ''; return }
  file.value = f
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = URL.createObjectURL(f)
}
onBeforeUnmount(() => preview.value && URL.revokeObjectURL(preview.value))
const copy = (t) => navigator.clipboard?.writeText(t).then(() => toast('اتنسخ — حوّل عليه'))

async function pay() {
  Object.keys(err).forEach((k) => (err[k] = ''))
  if (m.value?.payer_phone_required && !/^01[0125]\d{8}$/.test(payerPhone.value.replace(/\s/g, ''))) err.payer_phone = 'اكتب الرقم اللي حوّلت منه (11 رقم)'
  if (!file.value) err.receipt = 'ارفع صورة إيصال التحويل'
  if (err.payer_phone || err.receipt) return
  busy.value = true
  try {
    const order = await createOrder({ plan: plan.value.id, method: method.value, payerPhone: payerPhone.value.replace(/\s/g, ''), receipt: file.value })
    router.replace(`/checkout/result/${order.id}`)
  } catch (e) {
    const fe = fieldErrors(e)
    Object.assign(err, fe)
    if (!Object.keys(fe).length) toast(e.message, 'err')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <NotFound v-if="!plan || !plan.price" />
  <div v-else class="m-screen with-cta">
    <MAppBar title="الدفع" fallback="/pricing">
      <span class="amt"><span class="num">{{ egp(plan.price) }}</span> ج.م</span>
    </MAppBar>

    <div class="m-pad m-stack body">
      <RouterLink v-if="pending" :to="`/checkout/result/${pending.id}`" class="pend">عندك طلب للباقة دي لسه بيتراجع — شوف حالته ‹</RouterLink>
      <small class="muted who">{{ plan.name }} · {{ plan.period }}</small>
      <MSeg v-model="method" :options="methodOptions" />

      <div class="m-card st">
        <header><span class="n num">1</span><b>حوّل <span class="num amber">{{ egp(plan.price) }}</span> ج.م {{ method === 'instapay' ? 'على عنوان InstaPay' : 'على الرقم ده' }}</b></header>
        <button v-if="m?.account" type="button" class="acct" @click="copy(m.account)">
          <span class="en">{{ m.account }}</span><small>نسخ</small>
        </button>
        <small v-else class="muted">الرقم هيتحدد قريب</small>
      </div>

      <div class="m-card st">
        <header><span class="n num">2</span><b>{{ m?.payer_phone_required ? 'الرقم اللي حوّلت منه' : 'الرقم اللي حوّلت منه (اختياري)' }}</b></header>
        <input v-model="payerPhone" class="m-input ltr" :class="{ invalid: err.payer_phone }" type="tel" inputmode="tel" placeholder="01xxxxxxxxx" aria-label="رقم المحوِّل" />
        <small v-if="err.payer_phone" class="m-err">{{ err.payer_phone }}</small>
      </div>

      <div class="m-card st">
        <header><span class="n num">3</span><b>صورة الإيصال</b></header>
        <label class="up" :class="{ has: preview, invalid: err.receipt }">
          <input type="file" accept="image/jpeg,image/png,image/webp" @change="onFile" />
          <img v-if="preview" :src="preview" alt="صورة الإيصال" />
          <span v-else>📷 صوّر أو اختار صورة</span>
        </label>
        <small v-if="err.receipt" class="m-err">{{ err.receipt }}</small>
      </div>
      <small class="muted center">بنراجع ونفعّل خلال <span class="num">{{ m?.review_hours || 24 }}</span> ساعة بالكتير.</small>
    </div>

    <div class="m-cta"><button class="btn btn-primary" :disabled="busy" @click="pay">{{ busy ? 'بنرفع الإيصال…' : 'ابعت الإيصال' }}</button></div>
  </div>
</template>

<style scoped>
.amt { font-family: var(--f-head); color: var(--amber); white-space: nowrap; }
.amt .num { font-size: 1.4rem; }
.body { padding-top: 4px; }
.who { font-weight: 800; }
.pend { padding: 10px 12px; border-radius: 12px; background: rgba(255,176,0,.08); border: 1px solid rgba(255,176,0,.35); font-weight: 800; font-size: .85rem; color: var(--amber); }
.st { padding: 14px; display: grid; gap: 10px; }
.st header { display: flex; gap: 10px; align-items: center; }
.n { width: 28px; height: 28px; flex: none; border-radius: 9px; background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-size: 1.15rem; }
.acct { display: flex; justify-content: space-between; align-items: center; border: 2px dashed var(--orange); border-radius: 14px; padding: 10px 14px; }
.acct .en { font-size: 1.9rem; letter-spacing: .06em; color: var(--amber); }
.acct small { background: #fff; color: #0a0a0a; font-weight: 900; font-size: .72rem; padding: 3px 10px; border-radius: 7px; }
.up { position: relative; display: grid; place-items: center; min-height: 84px; border: 1.5px dashed var(--orange); border-radius: 14px; color: var(--orange); font-weight: 800; overflow: hidden; }
.up.invalid { border-color: var(--danger); }
.up input { position: absolute; inset: 0; opacity: 0; }
.up img { max-height: 160px; border-radius: 8px; }
.center { text-align: center; }
</style>
