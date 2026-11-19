<script setup>
import { ref, reactive, onMounted } from 'vue'
import AdminNav from '../components/AdminNav.vue'
import { api, fieldErrors } from '../api.js'
import { egp } from '../data.js'
import { toast } from '../stores/app.js'

const members = ref([])
const plans = ref([])
const loading = ref(true)
const q = ref('')
const type = ref('all')

const creating = ref(false)
const createForm = reactive({ name: '', phone: '', email: '', password: '', goal: 'loss', is_admin: false })
const saving = ref(false)

const detail = ref(null)
const detailLoading = ref(false)
const detailTab = ref('profile') // profile | subs | progress | followups
const editForm = reactive({ name: '', phone: '', email: '', password: '', goal: '', is_admin: false })
const newWeight = reactive({ kg: '', logged_on: new Date().toISOString().slice(0, 10) })
const grantSub = reactive({ plan_id: '', lifetime: false, ends_at: '', notes: '' })

const tabs = [
  { id: 'all', label: 'الكل' },
  { id: 'subscribed', label: 'مشتركين نشطين' },
  { id: 'free', label: 'مجاني' },
  { id: 'admin', label: 'أدمن' },
]
const goalLabels = { loss: 'تخسيس', cut: 'تنشيف', gain: 'عضل' }
const fuLabels = { on_track: 'ماشي تمام', needs_attention: 'محتاج متابعة', delayed: 'متأخر', completed: 'تمت' }
const wa = (phone) => `https://wa.me/2${phone}`
const dDate = (iso) => (iso ? iso.slice(0, 10) : '—')

async function load() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (q.value.trim()) params.set('q', q.value.trim())
    if (type.value !== 'all') params.set('type', type.value)
    const [mRes, sRes] = await Promise.all([
      api(`/admin/members?${params.toString()}`),
      plans.value.length ? Promise.resolve(null) : api('/admin/subscriptions'),
    ])
    members.value = mRes.data
    if (sRes) plans.value = sRes.plans || []
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    loading.value = false
  }
}
onMounted(load)

async function createMember() {
  saving.value = true
  try {
    await api('/admin/members', {
      method: 'POST',
      body: {
        ...createForm,
        email: createForm.email.trim() || null,
        goal: createForm.goal || null,
      },
    })
    creating.value = false
    Object.assign(createForm, { name: '', phone: '', email: '', password: '', goal: 'loss', is_admin: false })
    toast('اتضاف العضو ✓')
    await load()
  } catch (e) {
    const fe = fieldErrors(e)
    toast(Object.values(fe)[0] || e.message, 'err')
  } finally {
    saving.value = false
  }
}

async function openMember(m) {
  detailLoading.value = true
  detailTab.value = 'profile'
  detail.value = { id: m.id, name: m.name }
  try {
    const res = await api(`/admin/members/${m.id}`)
    detail.value = res.data
    Object.assign(editForm, {
      name: res.data.name,
      phone: res.data.phone,
      email: res.data.email || '',
      password: '',
      goal: res.data.goal || '',
      is_admin: res.data.is_admin,
    })
    grantSub.plan_id = plans.value[0]?.id || ''
    grantSub.lifetime = false
    grantSub.ends_at = ''
    grantSub.notes = ''
  } catch (e) {
    toast(e.message, 'err')
    detail.value = null
  } finally {
    detailLoading.value = false
  }
}

async function saveProfile() {
  if (!detail.value) return
  saving.value = true
  try {
    await api(`/admin/members/${detail.value.id}`, {
      method: 'PUT',
      body: {
        name: editForm.name.trim(),
        phone: editForm.phone.trim(),
        email: editForm.email.trim() || null,
        password: editForm.password.trim() || null,
        goal: editForm.goal || null,
        is_admin: editForm.is_admin,
      },
    })
    toast('اتحفظت بيانات العضو ✓')
    await Promise.all([openMember(detail.value), load()])
  } catch (e) {
    const fe = fieldErrors(e)
    toast(Object.values(fe)[0] || e.message, 'err')
  } finally {
    saving.value = false
  }
}

async function removeMember() {
  if (!detail.value || !confirm(`متأكد إنك عايز تحذف حساب "${detail.value.name}"؟ (حذف ناعم)`)) return
  try {
    await api(`/admin/members/${detail.value.id}`, { method: 'DELETE' })
    detail.value = null
    toast('اتحذف الحساب ✓')
    await load()
  } catch (e) {
    toast(e.message, 'err')
  }
}

async function addSubscription() {
  if (!detail.value || !grantSub.plan_id) return
  saving.value = true
  try {
    await api('/admin/subscriptions', {
      method: 'POST',
      body: {
        user_id: detail.value.id,
        plan_id: Number(grantSub.plan_id),
        lifetime: grantSub.lifetime,
        ends_at: grantSub.lifetime ? null : (grantSub.ends_at || null),
        notes: grantSub.notes.trim() || null,
      },
    })
    toast('اتفعّل الاشتراك ✓')
    await Promise.all([openMember(detail.value), load()])
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    saving.value = false
  }
}

async function addWeight() {
  if (!detail.value || !newWeight.kg) return
  try {
    await api(`/admin/members/${detail.value.id}/weights`, {
      method: 'POST',
      body: { kg: Number(newWeight.kg), logged_on: newWeight.logged_on },
    })
    newWeight.kg = ''
    toast('اتسجّل الوزن ✓')
    await openMember(detail.value)
  } catch (e) {
    toast(e.message, 'err')
  }
}

async function deleteWeight(w) {
  if (!detail.value) return
  try {
    await api(`/admin/members/${detail.value.id}/weights/${w.id}`, { method: 'DELETE' })
    toast('اتمسح ✓')
    await openMember(detail.value)
  } catch (e) {
    toast(e.message, 'err')
  }
}

async function toggleDay(prog, day) {
  if (!detail.value) return
  try {
    await api(`/admin/members/${detail.value.id}/completions`, {
      method: 'POST',
      body: { program_id: prog.program_id, day },
    })
    const idx = prog.completed_days.indexOf(day)
    if (idx >= 0) prog.completed_days.splice(idx, 1)
    else prog.completed_days.push(day)
  } catch (e) {
    toast(e.message, 'err')
  }
}
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <div>
        <h1>الأعضاء</h1>
        <p class="muted">كل الأعضاء المسجلين، اشتراكاتهم، تقدمهم في التمارين، أوزانهم، ومتابعاتهم.</p>
      </div>
      <button class="btn btn-primary btn-sm" @click="creating = true">+ إضافة عضو جديد</button>
    </header>

    <div class="toolbar">
      <div class="filters no-scrollbar">
        <button v-for="t in tabs" :key="t.id" :class="{ on: type === t.id }" @click="type = t.id; load()"><span>{{ t.label }}</span></button>
      </div>
      <div class="sbox">
        <input v-model="q" class="input" placeholder="ابحث بالاسم أو الموبايل أو الإيميل…" @keyup.enter="load" />
        <button class="btn btn-ghost btn-sm" :disabled="loading" @click="load">بحث</button>
      </div>
    </div>

    <div v-if="loading" class="loading"><span class="spin" aria-label="بنحمّل"></span></div>
    <p v-else-if="!members.length" class="muted empty">مفيش أعضاء مطابقين.</p>

    <div v-else class="mlist">
      <article v-for="m in members" :key="m.id" class="mrow" @click="openMember(m)">
        <span class="av">{{ m.name.charAt(0) }}</span>
        <div class="minfo">
          <div class="l1">
            <b>{{ m.name }}</b>
            <span v-if="m.is_admin" class="pill adm-p">أدمن</span>
            <span v-if="m.has_active_sub" class="pill ok">{{ m.active_plan }}</span>
            <span v-else class="pill">مجاني</span>
          </div>
          <p class="muted small">
            <a :href="wa(m.phone)" target="_blank" rel="noopener" class="en wa" @click.stop>{{ m.phone }}</a>
            <template v-if="m.goal"> · {{ goalLabels[m.goal] || m.goal }}</template>
            <template v-if="m.latest_weight"> · <span class="num">{{ m.latest_weight }}</span> كجم</template>
            · <span class="num">{{ m.completions_count }}</span> يوم تمرين
            · <span class="num">{{ m.followups_count }}</span> متابعة
          </p>
        </div>
        <button type="button" class="btn btn-ghost btn-sm">التفاصيل ←</button>
      </article>
    </div>

    <!-- CREATE MEMBER MODAL -->
    <div v-if="creating" class="modal" @click.self="creating = false">
      <form class="modal-card sm" @submit.prevent="createMember">
        <header class="m-head">
          <h2>إضافة عضو جديد</h2>
          <button type="button" class="x" @click="creating = false">✕</button>
        </header>
        <label class="field"><span>الاسم *</span><input v-model="createForm.name" class="input" required /></label>
        <label class="field"><span>رقم الموبايل (واتساب) *</span><input v-model="createForm.phone" class="input ltr" required placeholder="01xxxxxxxxx" /></label>
        <label class="field"><span>الإيميل (اختياري)</span><input v-model="createForm.email" type="email" class="input ltr" /></label>
        <label class="field"><span>كلمة السر *</span><input v-model="createForm.password" type="password" class="input ltr" required minlength="6" /></label>
        <label class="field">
          <span>الهدف</span>
          <select v-model="createForm.goal" class="input">
            <option value="">— غير محدد —</option>
            <option value="loss">تخسيس</option>
            <option value="cut">تنشيف</option>
            <option value="gain">زيادة عضل</option>
          </select>
        </label>
        <label class="check"><input v-model="createForm.is_admin" type="checkbox" /> صلاحية أدمن</label>
        <div class="m-acts">
          <button class="btn btn-primary btn-sm" :disabled="saving">{{ saving ? 'بنحفظ…' : 'إضافة' }}</button>
          <button type="button" class="btn btn-ghost btn-sm" @click="creating = false">إلغاء</button>
        </div>
      </form>
    </div>

    <!-- MEMBER DETAIL MODAL -->
    <div v-if="detail" class="modal" @click.self="detail = null">
      <div class="modal-card lg">
        <header class="m-head">
          <div>
            <h2>{{ detail.name }}</h2>
            <a v-if="detail.phone" :href="wa(detail.phone)" target="_blank" rel="noopener" class="en wa">{{ detail.phone }} (واتساب)</a>
          </div>
          <button type="button" class="x" @click="detail = null">✕</button>
        </header>

        <div v-if="detailLoading" class="loading"><span class="spin"></span></div>
        <template v-else>
          <div class="dtabs">
            <button :class="{ on: detailTab === 'profile' }" @click="detailTab = 'profile'">البيانات</button>
            <button :class="{ on: detailTab === 'subs' }" @click="detailTab = 'subs'">الاشتراكات والطلبات ({{ detail.subscriptions?.length || 0 }})</button>
            <button :class="{ on: detailTab === 'progress' }" @click="detailTab = 'progress'">التقدم والوزن</button>
            <button :class="{ on: detailTab === 'followups' }" @click="detailTab = 'followups'">المتابعات ({{ detail.followups?.length || 0 }})</button>
          </div>

          <!-- PROFILE TAB -->
          <form v-if="detailTab === 'profile'" class="dsec" @submit.prevent="saveProfile">
            <div class="row2">
              <label class="field"><span>الاسم</span><input v-model="editForm.name" class="input" required /></label>
              <label class="field"><span>الموبايل</span><input v-model="editForm.phone" class="input ltr" required /></label>
            </div>
            <div class="row2">
              <label class="field"><span>الإيميل</span><input v-model="editForm.email" type="email" class="input ltr" /></label>
              <label class="field">
                <span>الهدف</span>
                <select v-model="editForm.goal" class="input">
                  <option value="">— غير محدد —</option>
                  <option value="loss">تخسيس</option>
                  <option value="cut">تنشيف</option>
                  <option value="gain">زيادة عضل</option>
                </select>
              </label>
            </div>
            <label class="field"><span>تغيير كلمة السر (سيبها فاضية لو مش هتغيّرها)</span><input v-model="editForm.password" type="password" class="input ltr" minlength="6" /></label>
            <label class="check"><input v-model="editForm.is_admin" type="checkbox" /> صلاحية أدمن</label>
            <div class="m-acts between">
              <button class="btn btn-primary btn-sm" :disabled="saving">{{ saving ? 'بنحفظ…' : 'احفظ التعديلات' }}</button>
              <button type="button" class="btn btn-ghost btn-sm danger" @click="removeMember">حذف الحساب</button>
            </div>
          </form>

          <!-- SUBSCRIPTIONS & ORDERS TAB -->
          <div v-if="detailTab === 'subs'" class="dsec">
            <h3>منح اشتراك جديد للعضو</h3>
            <form class="sub-grant" @submit.prevent="addSubscription">
              <select v-model="grantSub.plan_id" class="input" required>
                <option v-for="pl in plans" :key="pl.id" :value="pl.id">{{ pl.name }} ({{ pl.price }} ج.م)</option>
              </select>
              <input v-if="!grantSub.lifetime" v-model="grantSub.ends_at" type="date" class="input ltr" title="تاريخ الانتهاء (اختياري)" />
              <label class="check"><input v-model="grantSub.lifetime" type="checkbox" /> مدى الحياة</label>
              <input v-model="grantSub.notes" class="input" placeholder="ملاحظة (مثلاً: تجديد واتساب)" />
              <button class="btn btn-primary btn-sm" :disabled="saving">تفعيل</button>
            </form>

            <h3>سجل الاشتراكات</h3>
            <p v-if="!detail.subscriptions.length" class="muted small">مفيش اشتراكات.</p>
            <div v-for="s in detail.subscriptions" :key="s.id" class="srow">
              <div>
                <b>{{ s.plan_name }}</b>
                <span class="pill" :class="{ ok: s.is_active }">{{ s.is_active ? 'نشط' : 'منتهي' }}</span>
                <p class="muted small">من {{ dDate(s.starts_at) }} · {{ s.ends_at ? `لحد ${dDate(s.ends_at)}` : 'مدى الحياة' }}</p>
                <p v-if="s.notes" class="muted small">ملاحظة: {{ s.notes }}</p>
              </div>
              <span v-if="s.order_ref" class="en muted">{{ s.order_ref }}</span>
            </div>

            <h3>سجل الطلبات</h3>
            <p v-if="!detail.orders.length" class="muted small">مفيش طلبات.</p>
            <div v-for="o in detail.orders" :key="o.id" class="srow">
              <div>
                <b class="en">{{ o.id }}</b> · {{ o.plan_name }} · <b class="num amber">{{ egp(o.amount) }} ج.م</b>
                <p class="muted small">{{ o.status }} · {{ dDate(o.created_at) }}</p>
              </div>
            </div>
          </div>

          <!-- PROGRESS & WEIGHTS TAB -->
          <div v-if="detailTab === 'progress'" class="dsec">
            <h3>تقدم التمارين (اضغط على أي يوم للتعديل)</h3>
            <div v-for="p in detail.progress" :key="p.program_id" class="pbox">
              <div class="pb-h">
                <b>{{ p.title }}</b>
                <span class="num amber">{{ p.completed_days.length }} / {{ p.total_days }}</span>
              </div>
              <div class="days-g">
                <button
                  v-for="d in p.total_days"
                  :key="d"
                  type="button"
                  class="dcell num"
                  :class="{ done: p.completed_days.includes(d) }"
                  @click="toggleDay(p, d)"
                >{{ d }}</button>
              </div>
            </div>

            <h3>سجل الوزن</h3>
            <form class="wadd" @submit.prevent="addWeight">
              <input v-model="newWeight.kg" type="number" step="0.1" min="25" max="350" class="input ltr" placeholder="الوزن كجم" required />
              <input v-model="newWeight.logged_on" type="date" class="input ltr" required />
              <button class="btn btn-primary btn-sm">سجّل</button>
            </form>
            <div class="wlist">
              <div v-for="w in detail.weights" :key="w.id" class="wchip">
                <span class="num">{{ w.logged_on }}</span>
                <b class="num amber">{{ w.kg }} كجم</b>
                <button type="button" class="del-x" @click="deleteWeight(w)">✕</button>
              </div>
            </div>
          </div>

          <!-- FOLLOWUPS TAB -->
          <div v-if="detailTab === 'followups'" class="dsec">
            <div class="between">
              <h3>سجل المتابعات الدقيقة</h3>
              <RouterLink :to="`/admin/followups?user_id=${detail.id}`" class="btn btn-primary btn-sm" @click="detail = null">إدارة / تسجيل متابعة ←</RouterLink>
            </div>
            <p v-if="!detail.followups.length" class="muted small">مفيش متابعات متسجلة للعضو ده لسه.</p>
            <div v-for="f in detail.followups" :key="f.id" class="fucard">
              <div class="between">
                <b class="num">{{ f.followup_date }}</b>
                <span class="pill" :class="f.status">{{ fuLabels[f.status] || f.status }}</span>
              </div>
              <div class="chips">
                <span v-if="f.weight_kg">وزن: <b class="num">{{ f.weight_kg }}</b> كجم</span>
                <span v-if="f.waist_cm">وسط: <b class="num">{{ f.waist_cm }}</b> سم</span>
                <span v-if="f.diet_adherence">أكل: <b class="num">{{ f.diet_adherence }}/10</b></span>
                <span v-if="f.workout_adherence">تمرين: <b class="num">{{ f.workout_adherence }}/10</b></span>
              </div>
              <p v-if="f.coach_notes" class="small"><b>ملاحظات الكوتش:</b> {{ f.coach_notes }}</p>
              <p v-if="f.plan_adjustments" class="small amber"><b>تعديل النظام:</b> {{ f.plan_adjustments }}</p>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.adm { padding-block: 24px 70px; display: grid; gap: 16px; max-width: 1100px; }
.top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.top h1 { font-size: 2.2rem; }
.toolbar { display: grid; gap: 10px; }
.sbox { display: flex; gap: 8px; }
.mlist { display: grid; gap: 8px; }
.mrow {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: #141414;
  border: 1px solid #242424;
  cursor: pointer;
  transition: border-color .15s;
}
.mrow:hover { border-color: var(--orange); }
.av { width: 42px; height: 42px; border-radius: 50%; background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-family: var(--f-head); font-size: 1.3rem; flex-shrink: 0; }
.minfo { flex: 1; min-width: 0; display: grid; gap: 3px; }
.l1 { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pill { font-size: .72rem; font-weight: 800; padding: 2px 8px; border-radius: 999px; background: #222; color: var(--muted); }
.pill.ok { background: #122a1b; color: #5ef09b; }
.pill.adm-p { background: #2a1c0d; color: var(--amber); }
.pill.needs_attention, .pill.delayed { background: #2c1818; color: #ff8a8a; }
.pill.on_track, .pill.completed { background: #122a1b; color: #5ef09b; }
.wa { color: var(--orange); }
.small { font-size: .82rem; }
.amber { color: var(--amber); }
.danger { color: #ff7a7a; border-color: #3a2020; }

.modal { position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,.8); display: grid; place-items: center; padding: 14px; }
.modal-card { width: 100%; max-height: 92svh; overflow: auto; background: #141414; border: 1px solid #2b2b2b; border-radius: 18px; padding: 18px; display: grid; gap: 14px; }
.modal-card.sm { max-width: 440px; }
.modal-card.lg { max-width: 780px; }
.m-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.m-head h2 { font-size: 1.45rem; }
.x { width: 34px; height: 34px; border-radius: 50%; background: #222; color: #fff; display: grid; place-items: center; }
.dtabs { display: flex; gap: 6px; flex-wrap: wrap; border-bottom: 1px solid #242424; padding-bottom: 10px; }
.dtabs button { padding: 7px 12px; border-radius: 9px; background: #1c1c1c; color: var(--muted); font-weight: 800; font-size: .84rem; }
.dtabs button.on { background: var(--orange); color: #0a0a0a; }
.dsec { display: grid; gap: 12px; }
.dsec h3 { font-size: 1.05rem; color: var(--amber); }
.row2 { display: grid; gap: 10px; }
@media (min-width: 600px) { .row2 { grid-template-columns: 1fr 1fr; } }
.field { display: grid; gap: 4px; }
.field span { font-size: .8rem; font-weight: 700; color: var(--muted); }
.check { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: .88rem; cursor: pointer; }
.check input { width: 18px; height: 18px; accent-color: var(--orange); }
.m-acts { display: flex; gap: 8px; flex-wrap: wrap; }
.between { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
.sub-grant { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 10px; border-radius: 12px; background: #0e0e0e; border: 1px solid #222; }
.sub-grant .input { flex: 1 1 140px; }
.srow { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 12px; background: #0e0e0e; border: 1px solid #202020; }
.pbox { display: grid; gap: 8px; padding: 10px; border-radius: 12px; background: #0e0e0e; border: 1px solid #202020; }
.pb-h { display: flex; justify-content: space-between; }
.days-g { display: flex; flex-wrap: wrap; gap: 5px; }
.dcell { width: 32px; height: 32px; border-radius: 8px; background: #1c1c1c; color: var(--muted); font-size: .9rem; }
.dcell.done { background: var(--orange); color: #0a0a0a; font-weight: 800; }
.wadd { display: flex; gap: 8px; flex-wrap: wrap; }
.wadd .input { flex: 1 1 120px; }
.wlist { display: flex; flex-wrap: wrap; gap: 8px; }
.wchip { display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 10px; background: #0e0e0e; border: 1px solid #222; font-size: .85rem; }
.del-x { color: #ff7a7a; font-size: .8rem; }
.fucard { display: grid; gap: 6px; padding: 12px; border-radius: 12px; background: #0e0e0e; border: 1px solid #222; }
.chips { display: flex; flex-wrap: wrap; gap: 6px; }
.chips span { font-size: .78rem; background: #1a1a1a; padding: 3px 9px; border-radius: 999px; }
.empty { padding: 28px 0; }
.loading { display: grid; place-items: center; padding: 40px; }
.spin { width: 30px; height: 30px; border: 3px solid #262626; border-top-color: var(--orange); border-radius: 50%; animation: sp .7s linear infinite; }
@keyframes sp { to { transform: rotate(360deg); } }
</style>
