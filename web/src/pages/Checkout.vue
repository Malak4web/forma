<script setup>
import { ref, reactive, computed, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NotFound from './NotFound.vue'
import { planById, payMethods, methodById, egp } from '../data.js'
import { store, createOrder, toast } from '../stores/app.js'
import { fieldErrors } from '../api.js'

const route = useRoute()
const router = useRouter()
const plan = computed(() => planById(route.params.plan))
const method = ref(payMethods[0]?.id)
const m = computed(() => methodById(method.value))
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

const copy = (t) => navigator.clipboard?.writeText(t).then(() => toast('اتنسخ'))

async function pay() {
  Object.keys(err).forEach((k) => (err[k] = ''))
  if (m.value.payer_phone_required && !/^01[0125]\d{8}$/.test(payerPhone.value.replace(/\s/g, ''))) err.payer_phone = 'اكتب الرقم اللي حوّلت منه (11 رقم)'
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
  <div v-else class="wrap co">
    <ol class="steps" aria-label="خطوات الاشتراك">
      <li class="done"><span>✓</span>الحساب</li>
      <li class="on"><span class="num">2</span>الدفع</li>
      <li><span class="num">3</span>التفعيل</li>
    </ol>

    <div v-if="pending" class="pend">
      عندك طلب للباقة دي لسه بيتراجع. <RouterLink :to="`/checkout/result/${pending.id}`">شوف حالته ←</RouterLink>
    </div>

    <div class="grid">
      <section class="pay">
        <h1>حوّل وارفع الإيصال</h1>
        <div class="methods" role="radiogroup" aria-label="طريقة الدفع">
          <label v-for="pm in payMethods" :key="pm.id" class="m" :class="{ on: method === pm.id }">
            <input type="radio" v-model="method" :value="pm.id" name="method" />
            <span class="m-ic" :class="pm.id" aria-hidden="true"></span>
            <span class="m-t"><b>{{ pm.label }}</b><small>{{ pm.hint }}</small></span>
            <span class="m-r" aria-hidden="true"></span>
          </label>
        </div>

        <ol class="howto">
          <li>
            <span class="n num">1</span>
            <div>
              <b>حوّل <span class="num amber">{{ egp(plan.price) }}</span> ج.م {{ m.id === 'instapay' ? 'على عنوان InstaPay' : 'على رقم فودافون كاش' }}</b>
              <button v-if="m.account" type="button" class="acct" @click="copy(m.account)">
                <span class="en">{{ m.account }}</span><small>نسخ</small>
              </button>
              <small v-else class="muted">الرقم هيتحدد قريب</small>
            </div>
          </li>
          <li>
            <span class="n num">2</span>
            <div class="grow">
              <b>{{ m.payer_phone_required ? 'اكتب الرقم اللي حوّلت منه' : 'الرقم اللي حوّلت منه (اختياري)' }}</b>
              <input v-model="payerPhone" class="input ltr" :class="{ invalid: err.payer_phone }" type="tel" inputmode="tel" placeholder="01xxxxxxxxx" aria-label="رقم المحوِّل" />
              <small v-if="err.payer_phone" class="err">{{ err.payer_phone }}</small>
            </div>
          </li>
          <li>
            <span class="n num">3</span>
            <div class="grow">
              <b>ارفع سكرين شوت التحويل</b>
              <label class="upload" :class="{ has: preview, invalid: err.receipt }">
                <input type="file" accept="image/jpeg,image/png,image/webp" @change="onFile" />
                <img v-if="preview" :src="preview" alt="صورة الإيصال" />
                <span v-else>اختار صورة الإيصال</span>
              </label>
              <small v-if="err.receipt" class="err">{{ err.receipt }}</small>
            </div>
          </li>
        </ol>
        <p class="note">بنراجع التحويل ونفعّل اشتراكك خلال <span class="num">{{ m.review_hours }}</span> ساعة بالكتير، وغالبًا أسرع.</p>
      </section>

      <aside class="sum">
        <h2>طلبك</h2>
        <div class="row"><span>{{ plan.name }}</span><span><span class="num">{{ egp(plan.price) }}</span> ج.م</span></div>
        <small class="muted">{{ plan.period }}</small>
        <ul><li v-for="f in plan.features" :key="f">{{ f }}</li></ul>
        <div class="row total"><span>الإجمالي</span><span><span class="num">{{ egp(plan.price) }}</span> ج.م</span></div>
        <button class="btn btn-primary btn-block" :disabled="busy" @click="pay">{{ busy ? 'بنرفع الإيصال…' : 'ابعت الإيصال' }}</button>
        <small class="muted who">باسم: {{ store.user.name }} · <span class="en">{{ store.user.phone }}</span></small>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.co { padding-block: 20px 60px; }
.steps { list-style: none; margin: 0 0 22px; padding: 0; display: flex; gap: 8px; font-weight: 800; font-size: .88rem; color: var(--muted); }
.steps li { display: flex; align-items: center; gap: 6px; flex: 1; padding-bottom: 8px; border-bottom: 3px solid #222; }
.steps span { width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; background: #222; font-size: .95rem; }
.steps .done { color: var(--ok); border-color: var(--ok); }
.steps .done span { background: var(--ok); color: #0a0a0a; }
.steps .on { color: #fff; border-color: var(--orange); }
.steps .on span { background: var(--orange); color: #0a0a0a; }
.pend { margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: rgba(255,176,0,.08); border: 1px solid rgba(255,176,0,.35); font-weight: 700; }
.pend a { color: var(--amber); font-weight: 800; }

.grid { display: grid; gap: 20px; }
@media (min-width: 900px) { .grid { grid-template-columns: 1fr 360px; gap: 32px; align-items: start; } .sum { position: sticky; top: calc(var(--header) + 16px); } }
.pay h1 { font-size: clamp(2rem, 7vw, 2.8rem); margin-bottom: 14px; }
.methods { display: grid; gap: 8px; }
@media (min-width: 560px) { .methods { grid-template-columns: 1fr 1fr; } }
.m { display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 14px; background: #121212; border: 1.5px solid #262626; cursor: pointer; transition: border-color .15s; }
.m:focus-within { outline: 3px solid var(--amber); outline-offset: 2px; }
.m.on { border-color: var(--orange); background: rgba(255,106,0,.06); }
.m input { position: absolute; opacity: 0; pointer-events: none; }
.m-ic { width: 36px; height: 36px; border-radius: 10px; flex: none; }
.m-ic.vodafone_cash { background: #e60000; }
.m-ic.instapay { background: linear-gradient(135deg, #5b2a86, #8d3bd1); }
.m-t { flex: 1; display: grid; min-width: 0; }
.m-t small { color: var(--muted); font-size: .72rem; line-height: 1.4; }
.m-r { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #444; flex: none; }
.m.on .m-r { border: 6px solid var(--orange); }

.howto { list-style: none; margin: 18px 0 0; padding: 0; display: grid; gap: 10px; }
.howto li { display: flex; gap: 12px; padding: 14px; border-radius: 14px; background: #0f0f0f; border: 1px solid #222; }
.howto .n { width: 30px; height: 30px; flex: none; border-radius: 8px; background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-size: 1.2rem; }
.howto li > div { display: grid; gap: 8px; align-content: start; }
.grow { flex: 1; }
.acct { justify-self: start; display: flex; align-items: baseline; gap: 10px; padding: 8px 14px; border: 2px dashed var(--orange); border-radius: 10px; }
.acct .en { font-size: 1.7rem; letter-spacing: .06em; color: var(--amber); }
.acct small { color: var(--muted); font-weight: 800; }
.upload { position: relative; display: grid; place-items: center; min-height: 90px; padding: 12px; border: 1.5px dashed var(--orange); border-radius: 12px; text-align: center; font-weight: 800; cursor: pointer; color: var(--orange); overflow: hidden; }
.upload.invalid { border-color: var(--danger); }
.upload input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload img { max-height: 220px; border-radius: 8px; }
.err { color: var(--danger); font-weight: 800; font-size: .85rem; }
.note { margin-top: 14px; }

.sum { padding: 20px; border-radius: 18px; background: #121212; border: 1px solid #262626; display: grid; gap: 10px; }
.sum h2 { font-size: 1.5rem; }
.row { display: flex; justify-content: space-between; font-weight: 800; }
.row .num { font-size: 1.4rem; }
.sum ul { margin: 0; padding: 10px 0; list-style: none; display: grid; gap: 4px; font-size: .88rem; color: var(--muted); border-block: 1px solid #222; }
.sum li::before { content: '✓ '; color: var(--orange); }
.total .num { font-size: 2rem; color: var(--amber); }
.who { font-size: .78rem; text-align: center; }
</style>
