<script setup>
import { ref, reactive, onMounted } from 'vue'
import AdminNav from '../components/AdminNav.vue'
import { api, fieldErrors } from '../api.js'
import { egp } from '../data.js'
import { toast } from '../stores/app.js'

const subs = ref([])
const counts = ref({})
const plans = ref([])
const memberOpts = ref([])
const loading = ref(true)
const saving = ref(false)
const busy = reactive({})

const status = ref('active')
const q = ref('')

const creating = ref(false)
const createForm = reactive({ user_id: '', plan_id: '', starts_at: new Date().toISOString().slice(0, 10), ends_at: '', lifetime: false, notes: '' })
const editing = ref(null)

const tabs = [
  { id: 'active', label: 'النشطة' },
  { id: 'expiring', label: 'قربت تخلص (7 أيام)' },
  { id: 'lifetime', label: 'مدى الحياة' },
  { id: 'expired', label: 'منتهية' },
  { id: 'all', label: 'الكل' },
]
const wa = (phone) => `https://wa.me/2${phone}`
const dDate = (iso) => (iso ? iso.slice(0, 10) : '—')

async function load() {
  loading.value = true
  try {
    const params = new URLSearchParams({ status: status.value })
    if (q.value.trim()) params.set('q', q.value.trim())
    const [res, mRes] = await Promise.all([
      api(`/admin/subscriptions?${params.toString()}`),
      memberOpts.value.length ? Promise.resolve(null) : api('/admin/members'),
    ])
    subs.value = res.data
    counts.value = res.counts || {}
    plans.value = res.plans || []
    if (mRes) memberOpts.value = mRes.data || []
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    loading.value = false
  }
}
onMounted(load)

function openCreate() {
  createForm.user_id = memberOpts.value[0]?.id || ''
  createForm.plan_id = plans.value[0]?.id || ''
  createForm.starts_at = new Date().toISOString().slice(0, 10)
  createForm.ends_at = ''
  createForm.lifetime = false
  createForm.notes = ''
  creating.value = true
}

async function createSub() {
  saving.value = true
  try {
    await api('/admin/subscriptions', {
      method: 'POST',
      body: {
        user_id: Number(createForm.user_id),
        plan_id: Number(createForm.plan_id),
        starts_at: createForm.starts_at || null,
        ends_at: createForm.lifetime ? null : (createForm.ends_at || null),
        lifetime: createForm.lifetime,
        notes: createForm.notes.trim() || null,
      },
    })
    creating.value = false
    toast('اتفعّل الاشتراك ✓')
    await load()
  } catch (e) {
    const fe = fieldErrors(e)
    toast(Object.values(fe)[0] || e.message, 'err')
  } finally {
    saving.value = false
  }
}

function openEdit(s) {
  editing.value = {
    id: s.id,
    user_name: s.user?.name,
    plan_id: s.plan_id,
    starts_at: dDate(s.starts_at),
    ends_at: s.ends_at ? dDate(s.ends_at) : '',
    lifetime: s.is_lifetime,
    notes: s.notes || '',
  }
}

async function saveEdit() {
  const ed = editing.value
  if (!ed) return
  saving.value = true
  try {
    await api(`/admin/subscriptions/${ed.id}`, {
      method: 'PUT',
      body: {
        plan_id: Number(ed.plan_id),
        starts_at: ed.starts_at,
        ends_at: ed.lifetime ? null : (ed.ends_at || null),
        lifetime: ed.lifetime,
        notes: ed.notes.trim() || null,
      },
    })
    editing.value = null
    toast('اتحفظ التعديل ✓')
    await load()
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    saving.value = false
  }
}

async function quickExtend(s, days) {
  busy[s.id] = true
  try {
    await api(`/admin/subscriptions/${s.id}`, {
      method: 'PUT',
      body: days === null ? { lifetime: true } : { extend_days: days },
    })
    toast(days === null ? 'اتحول لمدى الحياة ✓' : `اتمدّ +${days} يوم ✓`)
    await load()
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[s.id] = false
  }
}

async function cancelSub(s) {
  if (!confirm(`متأكد إنك عايز تلغي اشتراك "${s.user?.name}"؟ (حذف ناعم)`)) return
  busy[s.id] = true
  try {
    await api(`/admin/subscriptions/${s.id}`, { method: 'DELETE' })
    toast('اتلغى الاشتراك ✓')
    await load()
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[s.id] = false
  }
}
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <div>
        <h1>الاشتراكات</h1>
        <p class="muted">متابعة الاشتراكات النشطة واللي قربت تخلص، منح اشتراك يدوي، تمديد، أو إلغاء.</p>
      </div>
      <button class="btn btn-primary btn-sm" @click="openCreate">+ منح اشتراك جديد</button>
    </header>

    <div class="toolbar">
      <div class="filters no-scrollbar">
        <button v-for="t in tabs" :key="t.id" :class="{ on: status === t.id }" @click="status = t.id; load()">
          <span>{{ t.label }} <b class="num">({{ counts[t.id] ?? 0 }})</b></span>
        </button>
      </div>
      <div class="sbox">
        <input v-model="q" class="input" placeholder="دوّر باسم العضو أو الموبايل…" @keyup.enter="load" />
        <button class="btn btn-ghost btn-sm" :disabled="loading" @click="load">بحث</button>
      </div>
    </div>

    <div v-if="loading" class="loading"><span class="spin" aria-label="بنحمّل"></span></div>
    <p v-else-if="!subs.length" class="muted empty">مفيش اشتراكات في القسم ده.</p>

    <div v-else class="slist">
      <article v-for="s in subs" :key="s.id" class="scard" :class="{ exp: !s.is_active }">
        <div class="sh">
          <div>
            <b>{{ s.user?.name }}</b>
            <a v-if="s.user?.phone" :href="wa(s.user.phone)" target="_blank" rel="noopener" class="en wa"> · {{ s.user.phone }}</a>
          </div>
          <span class="pill" :class="s.is_active ? 'ok' : 'off'">
            {{ s.is_lifetime ? 'مدى الحياة' : s.is_active ? `نشط (${s.days_left} يوم)` : 'منتهي' }}
          </span>
        </div>
        <p class="small">
          <b>{{ s.plan?.name }}</b>
          <template v-if="s.order"> · طلب <span class="en">{{ s.order.ref }}</span> (<span class="num amber">{{ egp(s.order.amount) }}</span> ج.م)</template>
          <template v-else> · <span class="muted">منح يدوي من الأدمن</span></template>
        </p>
        <p class="muted small">
          البداية: <span class="num">{{ dDate(s.starts_at) }}</span>
          · الانتهاء: <span class="num">{{ s.ends_at ? dDate(s.ends_at) : 'على طول' }}</span>
        </p>
        <p v-if="s.notes" class="muted small">ملاحظة: {{ s.notes }}</p>
        <div class="acts">
          <button class="btn btn-ghost btn-sm" @click="openEdit(s)">تعديل</button>
          <button v-if="!s.is_lifetime" class="btn btn-ghost btn-sm" :disabled="busy[s.id]" @click="quickExtend(s, 7)">+7 أيام</button>
          <button v-if="!s.is_lifetime" class="btn btn-ghost btn-sm" :disabled="busy[s.id]" @click="quickExtend(s, 30)">+30 يوم</button>
          <button v-if="!s.is_lifetime" class="btn btn-ghost btn-sm" :disabled="busy[s.id]" @click="quickExtend(s, null)">مدى الحياة</button>
          <button class="btn btn-ghost btn-sm danger" :disabled="busy[s.id]" @click="cancelSub(s)">إلغاء الاشتراك</button>
        </div>
      </article>
    </div>

    <!-- CREATE MODAL -->
    <div v-if="creating" class="modal" @click.self="creating = false">
      <form class="modal-card" @submit.prevent="createSub">
        <header class="m-head">
          <h2>منح اشتراك جديد</h2>
          <button type="button" class="x" @click="creating = false">✕</button>
        </header>
        <label class="field">
          <span>العضو *</span>
          <select v-model="createForm.user_id" class="input" required>
            <option v-for="m in memberOpts" :key="m.id" :value="m.id">{{ m.name }} — {{ m.phone }}</option>
          </select>
        </label>
        <label class="field">
          <span>الباقة *</span>
          <select v-model="createForm.plan_id" class="input" required>
            <option v-for="pl in plans" :key="pl.id" :value="pl.id">{{ pl.name }} ({{ pl.price }} ج.م)</option>
          </select>
        </label>
        <div class="row2">
          <label class="field"><span>تاريخ البداية</span><input v-model="createForm.starts_at" type="date" class="input ltr" /></label>
          <label v-if="!createForm.lifetime" class="field"><span>تاريخ الانتهاء (تلقائي لو فاضي)</span><input v-model="createForm.ends_at" type="date" class="input ltr" /></label>
        </div>
        <label class="check"><input v-model="createForm.lifetime" type="checkbox" /> مدى الحياة (من غير تاريخ انتهاء)</label>
        <label class="field"><span>ملاحظة (اختياري)</span><input v-model="createForm.notes" class="input" placeholder="مثال: تحويل واتساب / هدية" /></label>
        <div class="m-acts">
          <button class="btn btn-primary btn-sm" :disabled="saving">{{ saving ? 'بنفعّل…' : 'تفعيل الاشتراك' }}</button>
          <button type="button" class="btn btn-ghost btn-sm" @click="creating = false">إلغاء</button>
        </div>
      </form>
    </div>

    <!-- EDIT MODAL -->
    <div v-if="editing" class="modal" @click.self="editing = null">
      <form class="modal-card" @submit.prevent="saveEdit">
        <header class="m-head">
          <h2>تعديل اشتراك: {{ editing.user_name }}</h2>
          <button type="button" class="x" @click="editing = null">✕</button>
        </header>
        <label class="field">
          <span>الباقة</span>
          <select v-model="editing.plan_id" class="input" required>
            <option v-for="pl in plans" :key="pl.id" :value="pl.id">{{ pl.name }} ({{ pl.price }} ج.م)</option>
          </select>
        </label>
        <div class="row2">
          <label class="field"><span>تاريخ البداية</span><input v-model="editing.starts_at" type="date" class="input ltr" required /></label>
          <label v-if="!editing.lifetime" class="field"><span>تاريخ الانتهاء</span><input v-model="editing.ends_at" type="date" class="input ltr" /></label>
        </div>
        <label class="check"><input v-model="editing.lifetime" type="checkbox" /> مدى الحياة</label>
        <label class="field"><span>ملاحظات</span><input v-model="editing.notes" class="input" /></label>
        <div class="m-acts">
          <button class="btn btn-primary btn-sm" :disabled="saving">{{ saving ? 'بنحفظ…' : 'احفظ' }}</button>
          <button type="button" class="btn btn-ghost btn-sm" @click="editing = null">إلغاء</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.adm { padding-block: 24px 70px; display: grid; gap: 16px; max-width: 1100px; }
.top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.top h1 { font-size: 2.2rem; }
.toolbar { display: grid; gap: 10px; }
.sbox { display: flex; gap: 8px; }
.slist { display: grid; gap: 10px; }
.scard { display: grid; gap: 6px; padding: 14px; border-radius: 14px; background: #141414; border: 1px solid #242424; }
.scard.exp { opacity: .65; }
.sh { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
.wa { color: var(--orange); }
.pill { font-size: .74rem; font-weight: 800; padding: 3px 10px; border-radius: 999px; }
.pill.ok { background: #122a1b; color: #5ef09b; }
.pill.off { background: #2c1818; color: #ff8a8a; }
.small { font-size: .85rem; }
.amber { color: var(--amber); }
.acts { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.acts .btn { padding: 5px 10px; font-size: .78rem; }
.danger { color: #ff7a7a; border-color: #3a2020; }

.modal { position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,.8); display: grid; place-items: center; padding: 14px; }
.modal-card { width: 100%; max-width: 480px; max-height: 92svh; overflow: auto; background: #141414; border: 1px solid #2b2b2b; border-radius: 18px; padding: 18px; display: grid; gap: 12px; }
.m-head { display: flex; justify-content: space-between; align-items: center; }
.m-head h2 { font-size: 1.35rem; }
.x { width: 34px; height: 34px; border-radius: 50%; background: #222; color: #fff; display: grid; place-items: center; }
.row2 { display: grid; gap: 10px; grid-template-columns: 1fr 1fr; }
.field { display: grid; gap: 4px; }
.field span { font-size: .8rem; font-weight: 700; color: var(--muted); }
.check { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: .88rem; cursor: pointer; }
.check input { width: 18px; height: 18px; accent-color: var(--orange); }
.m-acts { display: flex; gap: 8px; }
.empty { padding: 28px 0; }
.loading { display: grid; place-items: center; padding: 40px; }
.spin { width: 30px; height: 30px; border: 3px solid #262626; border-top-color: var(--orange); border-radius: 50%; animation: sp .7s linear infinite; }
@keyframes sp { to { transform: rotate(360deg); } }
</style>
