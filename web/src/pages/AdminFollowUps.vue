<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AdminNav from '../components/AdminNav.vue'
import { api, fieldErrors } from '../api.js'
import { toast } from '../stores/app.js'

const route = useRoute()
const mainTab = ref(route.query.tab === 'leads' ? 'leads' : 'checkins')

// ---- Member Follow-ups ----
const followups = ref([])
const fuCounts = ref({})
const fuLoading = ref(true)
const fuFilter = ref('all') // all | due | needs_attention | delayed | on_track | completed
const fuQuery = ref('')
const memberOpts = ref([])
const editing = ref(null)
const saving = ref(false)
const busy = reactive({})

const fuStatusTabs = [
  { id: 'all', label: 'الكل' },
  { id: 'due', label: 'مستحقة الآن' },
  { id: 'needs_attention', label: 'محتاج متابعة' },
  { id: 'delayed', label: 'متأخر' },
  { id: 'on_track', label: 'ماشي تمام' },
  { id: 'completed', label: 'تمت' },
]
const fuStatusOpts = [
  { id: 'on_track', label: 'ماشي تمام' },
  { id: 'needs_attention', label: 'محتاج متابعة عاجلة' },
  { id: 'delayed', label: 'متأخر / مش ملتزم' },
  { id: 'completed', label: 'تمت المتابعة' },
]
const fuLabel = (s) => fuStatusOpts.find((x) => x.id === s)?.label || s

async function loadFollowUps() {
  fuLoading.value = true
  try {
    const params = new URLSearchParams()
    if (fuFilter.value === 'due') params.set('due', '1')
    else if (fuFilter.value !== 'all') params.set('status', fuFilter.value)
    if (fuQuery.value.trim()) params.set('q', fuQuery.value.trim())
    if (route.query.user_id) params.set('user_id', String(route.query.user_id))

    const [res, mRes] = await Promise.all([
      api(`/admin/followups?${params.toString()}`),
      memberOpts.value.length ? Promise.resolve(null) : api('/admin/members'),
    ])
    followups.value = res.data
    fuCounts.value = res.counts || {}
    if (mRes) memberOpts.value = mRes.data || []
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    fuLoading.value = false
  }
}

function openCreateFU(presetUserId = null) {
  const today = new Date().toISOString().slice(0, 10)
  const nextWeek = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10)
  editing.value = {
    id: null,
    user_id: presetUserId || Number(route.query.user_id) || memberOpts.value[0]?.id || '',
    followup_date: today,
    next_followup_date: nextWeek,
    status: 'on_track',
    weight_kg: '',
    waist_cm: '',
    chest_cm: '',
    arm_cm: '',
    thigh_cm: '',
    hips_cm: '',
    body_fat: '',
    diet_adherence: '',
    workout_adherence: '',
    sleep_hours: '',
    water_liters: '',
    energy_level: '',
    member_feedback: '',
    coach_notes: '',
    plan_adjustments: '',
    sync_weight: true,
  }
}

function openEditFU(f) {
  editing.value = {
    ...f,
    next_followup_date: f.next_followup_date || '',
    weight_kg: f.weight_kg ?? '',
    waist_cm: f.waist_cm ?? '',
    chest_cm: f.chest_cm ?? '',
    arm_cm: f.arm_cm ?? '',
    thigh_cm: f.thigh_cm ?? '',
    hips_cm: f.hips_cm ?? '',
    body_fat: f.body_fat ?? '',
    diet_adherence: f.diet_adherence ?? '',
    workout_adherence: f.workout_adherence ?? '',
    sleep_hours: f.sleep_hours ?? '',
    water_liters: f.water_liters ?? '',
    energy_level: f.energy_level ?? '',
    member_feedback: f.member_feedback || '',
    coach_notes: f.coach_notes || '',
    plan_adjustments: f.plan_adjustments || '',
    sync_weight: false,
  }
}

const numOrNull = (v) => (v === '' || v === null || v === undefined ? null : Number(v))

async function saveFU() {
  const ed = editing.value
  if (!ed) return
  saving.value = true
  try {
    const body = {
      user_id: Number(ed.user_id),
      followup_date: ed.followup_date,
      next_followup_date: ed.next_followup_date || null,
      status: ed.status,
      weight_kg: numOrNull(ed.weight_kg),
      waist_cm: numOrNull(ed.waist_cm),
      chest_cm: numOrNull(ed.chest_cm),
      arm_cm: numOrNull(ed.arm_cm),
      thigh_cm: numOrNull(ed.thigh_cm),
      hips_cm: numOrNull(ed.hips_cm),
      body_fat: numOrNull(ed.body_fat),
      diet_adherence: numOrNull(ed.diet_adherence),
      workout_adherence: numOrNull(ed.workout_adherence),
      sleep_hours: numOrNull(ed.sleep_hours),
      water_liters: numOrNull(ed.water_liters),
      energy_level: numOrNull(ed.energy_level),
      member_feedback: ed.member_feedback.trim() || null,
      coach_notes: ed.coach_notes.trim() || null,
      plan_adjustments: ed.plan_adjustments.trim() || null,
      sync_weight: !!ed.sync_weight,
    }
    if (ed.id) {
      await api(`/admin/followups/${ed.id}`, { method: 'PUT', body })
      toast('اتحدثت المتابعة ✓')
    } else {
      await api('/admin/followups', { method: 'POST', body })
      toast('اتسجلت المتابعة ✓')
    }
    editing.value = null
    await loadFollowUps()
  } catch (e) {
    const fe = fieldErrors(e)
    toast(Object.values(fe)[0] || e.message, 'err')
  } finally {
    saving.value = false
  }
}

async function deleteFU(f) {
  if (!confirm('متأكد إنك عايز تحذف المتابعة دي؟ (حذف ناعم)')) return
  busy[`fu_${f.id}`] = true
  try {
    await api(`/admin/followups/${f.id}`, { method: 'DELETE' })
    toast('اتحذفت ✓')
    await loadFollowUps()
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[`fu_${f.id}`] = false
  }
}

// ---- Leads ----
const leads = ref([])
const leadCounts = ref({})
const leadLoading = ref(false)
const leadStatus = ref('new')
const leadQuery = ref('')
const leadDrafts = reactive({})

const leadTabs = [
  { id: 'new', label: 'جديد' },
  { id: 'contacted', label: 'تم التواصل' },
  { id: 'converted', label: 'اشترك' },
  { id: 'all', label: 'الكل' },
]
const goalLabels = { loss: 'تخسيس', cut: 'تنشيف', gain: 'زيادة عضل' }

async function loadLeads() {
  leadLoading.value = true
  try {
    const params = new URLSearchParams({ status: leadStatus.value })
    if (leadQuery.value.trim()) params.set('q', leadQuery.value.trim())
    const res = await api(`/admin/leads?${params.toString()}`)
    leads.value = res.data
    leadCounts.value = res.counts || {}
    for (const l of res.data) {
      leadDrafts[l.id] = { status: l.status, notes: l.notes || '' }
    }
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    leadLoading.value = false
  }
}

async function saveLead(l) {
  busy[`l_${l.id}`] = true
  try {
    const d = leadDrafts[l.id]
    await api(`/admin/leads/${l.id}`, { method: 'PUT', body: { status: d.status, notes: d.notes.trim() || null } })
    toast('اتحفظ ✓')
    await loadLeads()
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[`l_${l.id}`] = false
  }
}

async function deleteLead(l) {
  if (!confirm(`متأكد إنك عايز تحذف Lead "${l.name}"؟ (حذف ناعم)`)) return
  busy[`l_${l.id}`] = true
  try {
    await api(`/admin/leads/${l.id}`, { method: 'DELETE' })
    toast('اتحذف ✓')
    await loadLeads()
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[`l_${l.id}`] = false
  }
}

const waMember = (u) => {
  const txt = encodeURIComponent(`أهلاً يا ${u.name} 💪 كوتش فورمة معاك، طمنّي أخبار التمرين والأكل إيه الأسبوع ده؟`)
  return `https://wa.me/2${u.phone}?text=${txt}`
}
const waLead = (l) => {
  const kcal = l.kcal ? ` (${l.kcal} سعرة / ${l.protein || 0}g بروتين)` : ''
  const txt = encodeURIComponent(`أهلاً يا ${l.name} 🔥 وصلني طلبك لنظام الأكل المجاني من فورمة${kcal}، جاهز تبدأ؟`)
  return `https://wa.me/2${l.phone}?text=${txt}`
}

watch(mainTab, (t) => {
  if (t === 'leads' && !leads.value.length) loadLeads()
})
onMounted(() => {
  loadFollowUps()
  if (mainTab.value === 'leads') loadLeads()
})
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <div>
        <h1>المتابعات وتفاصيلها الدقيقة</h1>
        <p class="muted">متابعة الأعضاء بالقياسات والالتزام وتعديلات النظام + متابعة الـ Leads من نظام الأكل المجاني.</p>
      </div>
      <button v-if="mainTab === 'checkins'" class="btn btn-primary btn-sm" @click="openCreateFU()">+ تسجيل متابعة جديدة</button>
    </header>

    <!-- MAIN TABS -->
    <div class="mtabs">
      <button :class="{ on: mainTab === 'checkins' }" @click="mainTab = 'checkins'">
        متابعات الأعضاء <b class="num">({{ fuCounts.all ?? 0 }})</b>
      </button>
      <button :class="{ on: mainTab === 'leads' }" @click="mainTab = 'leads'">
        الـ Leads (نظام الأكل) <b class="num">({{ leadCounts.new ?? '' }})</b>
      </button>
    </div>

    <!-- ============ MEMBER CHECK-INS ============ -->
    <template v-if="mainTab === 'checkins'">
      <div class="toolbar">
        <div class="filters no-scrollbar">
          <button v-for="t in fuStatusTabs" :key="t.id" :class="{ on: fuFilter === t.id }" @click="fuFilter = t.id; loadFollowUps()">
            <span>{{ t.label }} <b class="num">({{ fuCounts[t.id] ?? 0 }})</b></span>
          </button>
        </div>
        <div class="sbox">
          <input v-model="fuQuery" class="input" placeholder="ابحث باسم العضو أو الموبايل…" @keyup.enter="loadFollowUps" />
          <button class="btn btn-ghost btn-sm" :disabled="fuLoading" @click="loadFollowUps">بحث</button>
        </div>
      </div>

      <div v-if="fuLoading" class="loading"><span class="spin"></span></div>
      <p v-else-if="!followups.length" class="muted empty">مفيش متابعات هنا لسه. اضغط «تسجيل متابعة جديدة» عشان تبدأ.</p>

      <div v-else class="flist">
        <article v-for="f in followups" :key="f.id" class="fcard" :class="{ due: f.is_due }">
          <header class="fh">
            <div>
              <b>{{ f.user?.name }}</b>
              <a v-if="f.user?.phone" :href="waMember(f.user)" target="_blank" rel="noopener" class="en wa"> · {{ f.user.phone }} (واتساب)</a>
            </div>
            <div class="fbadges">
              <span v-if="f.is_due" class="pill warn">مستحقة الآن</span>
              <span class="pill" :class="f.status">{{ fuLabel(f.status) }}</span>
            </div>
          </header>

          <p class="muted small">
            تاريخ المتابعة: <b class="num">{{ f.followup_date }}</b>
            <template v-if="f.next_followup_date"> · الجاية: <b class="num amber">{{ f.next_followup_date }}</b></template>
            <template v-if="f.admin_name"> · الكوتش: {{ f.admin_name }}</template>
          </p>

          <!-- MEASUREMENTS -->
          <div class="mchips">
            <span v-if="f.weight_kg">الوزن: <b class="num amber">{{ f.weight_kg }}</b> كجم</span>
            <span v-if="f.waist_cm">الوسط: <b class="num">{{ f.waist_cm }}</b> سم</span>
            <span v-if="f.chest_cm">الصدر: <b class="num">{{ f.chest_cm }}</b> سم</span>
            <span v-if="f.arm_cm">الدراع: <b class="num">{{ f.arm_cm }}</b> سم</span>
            <span v-if="f.thigh_cm">الفخد: <b class="num">{{ f.thigh_cm }}</b> سم</span>
            <span v-if="f.hips_cm">الأرداف: <b class="num">{{ f.hips_cm }}</b> سم</span>
            <span v-if="f.body_fat">دهون: <b class="num">{{ f.body_fat }}%</b></span>
          </div>

          <!-- ADHERENCE -->
          <div v-if="f.diet_adherence || f.workout_adherence || f.sleep_hours || f.water_liters || f.energy_level" class="mchips adh">
            <span v-if="f.diet_adherence">التزام الأكل: <b class="num">{{ f.diet_adherence }}/10</b></span>
            <span v-if="f.workout_adherence">التزام التمرين: <b class="num">{{ f.workout_adherence }}/10</b></span>
            <span v-if="f.sleep_hours">النوم: <b class="num">{{ f.sleep_hours }}</b> س</span>
            <span v-if="f.water_liters">المية: <b class="num">{{ f.water_liters }}</b> لتر</span>
            <span v-if="f.energy_level">الطاقة: <b class="num">{{ f.energy_level }}/5</b></span>
          </div>

          <div v-if="f.member_feedback || f.coach_notes || f.plan_adjustments" class="notes-box">
            <p v-if="f.member_feedback"><b>كلام العضو:</b> {{ f.member_feedback }}</p>
            <p v-if="f.coach_notes"><b>ملاحظات الكوتش:</b> {{ f.coach_notes }}</p>
            <p v-if="f.plan_adjustments" class="amber"><b>تعديلات النظام:</b> {{ f.plan_adjustments }}</p>
          </div>

          <div class="acts">
            <button class="btn btn-primary btn-sm" @click="openEditFU(f)">تعديل التفاصيل</button>
            <button class="btn btn-ghost btn-sm" @click="openCreateFU(f.user_id)">+ متابعة جديدة لنفس العضو</button>
            <button class="btn btn-ghost btn-sm danger" :disabled="busy[`fu_${f.id}`]" @click="deleteFU(f)">حذف</button>
          </div>
        </article>
      </div>
    </template>

    <!-- ============ LEADS ============ -->
    <template v-else>
      <div class="toolbar">
        <div class="filters no-scrollbar">
          <button v-for="t in leadTabs" :key="t.id" :class="{ on: leadStatus === t.id }" @click="leadStatus = t.id; loadLeads()">
            <span>{{ t.label }} <b class="num">({{ leadCounts[t.id] ?? 0 }})</b></span>
          </button>
        </div>
        <div class="sbox">
          <input v-model="leadQuery" class="input" placeholder="ابحث بالاسم أو الموبايل…" @keyup.enter="loadLeads" />
          <button class="btn btn-ghost btn-sm" :disabled="leadLoading" @click="loadLeads">بحث</button>
        </div>
      </div>

      <div v-if="leadLoading" class="loading"><span class="spin"></span></div>
      <p v-else-if="!leads.length" class="muted empty">مفيش Leads في القسم ده.</p>

      <div v-else class="flist">
        <article v-for="l in leads" :key="l.id" class="fcard">
          <header class="fh">
            <div>
              <b>{{ l.name }}</b>
              <a :href="waLead(l)" target="_blank" rel="noopener" class="en wa"> · {{ l.phone }} (ابعتله على واتساب)</a>
            </div>
            <span class="pill" :class="l.status">{{ leadTabs.find((x) => x.id === l.status)?.label || l.status }}</span>
          </header>

          <div class="mchips">
            <span v-if="l.goal">الهدف: <b>{{ goalLabels[l.goal] || l.goal }}</b></span>
            <span v-if="l.sex">{{ l.sex === 'f' ? 'أنثى' : 'ذكر' }}</span>
            <span v-if="l.age">السن: <b class="num">{{ l.age }}</b></span>
            <span v-if="l.weight">الوزن: <b class="num">{{ l.weight }}</b> كجم</span>
            <span v-if="l.height">الطول: <b class="num">{{ l.height }}</b> سم</span>
            <span v-if="l.kcal" class="amber">السعرات: <b class="num">{{ l.kcal }}</b></span>
            <span v-if="l.protein">P:<b class="num">{{ l.protein }}</b> · C:<b class="num">{{ l.carbs }}</b> · F:<b class="num">{{ l.fat }}</b></span>
          </div>

          <div v-if="leadDrafts[l.id]" class="lead-ed">
            <select v-model="leadDrafts[l.id].status" class="input sel">
              <option value="new">جديد</option>
              <option value="contacted">تم التواصل</option>
              <option value="converted">اشترك ✓</option>
            </select>
            <input v-model="leadDrafts[l.id].notes" class="input" placeholder="ملاحظة المتابعة مع العميل…" />
            <button class="btn btn-primary btn-sm" :disabled="busy[`l_${l.id}`]" @click="saveLead(l)">حفظ</button>
            <button class="btn btn-ghost btn-sm danger" :disabled="busy[`l_${l.id}`]" @click="deleteLead(l)">حذف</button>
          </div>
        </article>
      </div>
    </template>

    <!-- CREATE / EDIT FOLLOW-UP MODAL -->
    <div v-if="editing" class="modal" @click.self="editing = null">
      <form class="modal-card" @submit.prevent="saveFU">
        <header class="m-head">
          <h2>{{ editing.id ? 'تعديل تفاصيل المتابعة' : 'تسجيل متابعة جديدة' }}</h2>
          <button type="button" class="x" @click="editing = null">✕</button>
        </header>

        <section class="m-sec">
          <h3>1. العضو والحالة والمواعيد</h3>
          <div class="row2">
            <label class="field">
              <span>العضو *</span>
              <select v-model="editing.user_id" class="input" required :disabled="!!editing.id">
                <option v-for="m in memberOpts" :key="m.id" :value="m.id">{{ m.name }} — {{ m.phone }}</option>
              </select>
            </label>
            <label class="field">
              <span>الحالة *</span>
              <select v-model="editing.status" class="input" required>
                <option v-for="s in fuStatusOpts" :key="s.id" :value="s.id">{{ s.label }}</option>
              </select>
            </label>
          </div>
          <div class="row2">
            <label class="field"><span>تاريخ المتابعة *</span><input v-model="editing.followup_date" type="date" class="input ltr" required /></label>
            <label class="field"><span>ميعاد المتابعة الجاية</span><input v-model="editing.next_followup_date" type="date" class="input ltr" /></label>
          </div>
        </section>

        <section class="m-sec">
          <h3>2. القياسات الدقيقة (اختياري)</h3>
          <div class="row4">
            <label class="field"><span>الوزن (كجم)</span><input v-model="editing.weight_kg" type="number" step="0.1" class="input ltr" placeholder="82.5" /></label>
            <label class="field"><span>محيط الوسط (سم)</span><input v-model="editing.waist_cm" type="number" step="0.1" class="input ltr" placeholder="90" /></label>
            <label class="field"><span>الصدر (سم)</span><input v-model="editing.chest_cm" type="number" step="0.1" class="input ltr" /></label>
            <label class="field"><span>الدراع (سم)</span><input v-model="editing.arm_cm" type="number" step="0.1" class="input ltr" /></label>
          </div>
          <div class="row3">
            <label class="field"><span>الفخد (سم)</span><input v-model="editing.thigh_cm" type="number" step="0.1" class="input ltr" /></label>
            <label class="field"><span>الأرداف (سم)</span><input v-model="editing.hips_cm" type="number" step="0.1" class="input ltr" /></label>
            <label class="field"><span>نسبة الدهون %</span><input v-model="editing.body_fat" type="number" step="0.1" class="input ltr" /></label>
          </div>
          <label v-if="editing.weight_kg" class="check">
            <input v-model="editing.sync_weight" type="checkbox" /> سجّل الوزن ده في سجل وزن العضو على حسابه كمان
          </label>
        </section>

        <section class="m-sec">
          <h3>3. الالتزام والحالة اليومية</h3>
          <div class="row5">
            <label class="field"><span>الأكل (/10)</span><input v-model="editing.diet_adherence" type="number" min="1" max="10" class="input ltr" /></label>
            <label class="field"><span>التمرين (/10)</span><input v-model="editing.workout_adherence" type="number" min="1" max="10" class="input ltr" /></label>
            <label class="field"><span>النوم (س)</span><input v-model="editing.sleep_hours" type="number" step="0.5" min="1" max="20" class="input ltr" /></label>
            <label class="field"><span>المية (لتر)</span><input v-model="editing.water_liters" type="number" step="0.5" min="0" max="15" class="input ltr" /></label>
            <label class="field"><span>الطاقة (/5)</span><input v-model="editing.energy_level" type="number" min="1" max="5" class="input ltr" /></label>
          </div>
        </section>

        <section class="m-sec">
          <h3>4. الملاحظات وتعديلات النظام</h3>
          <label class="field"><span>كلام / شكوى العضو</span><textarea v-model="editing.member_feedback" class="input ta" rows="2" placeholder="مثلاً: جعان بالليل / ركبتي بتوجعني في السكوات…"></textarea></label>
          <label class="field"><span>ملاحظات الكوتش</span><textarea v-model="editing.coach_notes" class="input ta" rows="2"></textarea></label>
          <label class="field"><span>تعديلات النظام (سعرات / وجبات / تمارين)</span><textarea v-model="editing.plan_adjustments" class="input ta" rows="2" placeholder="مثلاً: قللنا 150 سعرة + بدّلنا السكوات بـ Wall Sit"></textarea></label>
        </section>

        <div class="m-acts">
          <button class="btn btn-primary btn-sm" :disabled="saving">{{ saving ? 'بنحفظ…' : 'احفظ المتابعة' }}</button>
          <button type="button" class="btn btn-ghost btn-sm" @click="editing = null">إلغاء</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.adm { padding-block: 24px 70px; display: grid; gap: 16px; max-width: 1120px; }
.top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.top h1 { font-size: 2.2rem; }
.mtabs { display: flex; gap: 8px; }
.mtabs button { padding: 10px 18px; border-radius: 12px; background: #161616; border: 1px solid #262626; font-weight: 800; color: var(--muted); }
.mtabs button.on { background: #fff; color: #0a0a0a; border-color: #fff; }
.toolbar { display: grid; gap: 10px; }
.sbox { display: flex; gap: 8px; }

.flist { display: grid; gap: 12px; }
.fcard { display: grid; gap: 8px; padding: 14px; border-radius: 16px; background: #141414; border: 1px solid #242424; }
.fcard.due { border-color: rgba(255, 176, 0, .55); }
.fh { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
.fbadges { display: flex; gap: 6px; flex-wrap: wrap; }
.wa { color: var(--orange); }
.pill { font-size: .74rem; font-weight: 800; padding: 3px 10px; border-radius: 999px; background: #222; color: var(--muted); }
.pill.on_track, .pill.completed, .pill.converted { background: #122a1b; color: #5ef09b; }
.pill.needs_attention, .pill.delayed { background: #2c1818; color: #ff8a8a; }
.pill.warn, .pill.new { background: #2d210b; color: var(--amber); }
.pill.contacted { background: #192233; color: #8cb8ff; }

.mchips { display: flex; flex-wrap: wrap; gap: 6px; }
.mchips span { font-size: .78rem; background: #1d1d1d; padding: 4px 10px; border-radius: 999px; }
.mchips.adh span { background: #161c22; }
.notes-box { display: grid; gap: 4px; padding: 10px 12px; border-radius: 10px; background: #0e0e0e; border: 1px solid #202020; font-size: .86rem; }
.small { font-size: .84rem; }
.amber { color: var(--amber); }
.acts { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
.acts .btn { padding: 5px 10px; font-size: .78rem; }
.danger { color: #ff7a7a; border-color: #3a2020; }

.lead-ed { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
.lead-ed .sel { width: auto; min-width: 140px; }
.lead-ed input { flex: 1 1 200px; }

.modal { position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,.8); display: grid; place-items: center; padding: 14px; }
.modal-card { width: 100%; max-width: 760px; max-height: 92svh; overflow: auto; background: #141414; border: 1px solid #2b2b2b; border-radius: 18px; padding: 18px; display: grid; gap: 14px; }
.m-head { display: flex; justify-content: space-between; align-items: center; }
.m-head h2 { font-size: 1.4rem; }
.x { width: 34px; height: 34px; border-radius: 50%; background: #222; color: #fff; display: grid; place-items: center; }
.m-sec { display: grid; gap: 10px; padding: 12px; border-radius: 12px; background: #0e0e0e; border: 1px solid #202020; }
.m-sec h3 { font-size: .98rem; color: var(--amber); }
.row2, .row3, .row4, .row5 { display: grid; gap: 10px; }
@media (min-width: 620px) {
  .row2 { grid-template-columns: 1fr 1fr; }
  .row3 { grid-template-columns: repeat(3, 1fr); }
  .row4 { grid-template-columns: repeat(4, 1fr); }
  .row5 { grid-template-columns: repeat(5, 1fr); }
}
.field { display: grid; gap: 4px; }
.field span { font-size: .78rem; font-weight: 700; color: var(--muted); }
.ta { min-height: 64px; padding-block: 8px; resize: vertical; }
.check { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: .86rem; cursor: pointer; }
.check input { width: 18px; height: 18px; accent-color: var(--orange); }
.m-acts { display: flex; gap: 8px; }
.empty { padding: 28px 0; }
.loading { display: grid; place-items: center; padding: 40px; }
.spin { width: 30px; height: 30px; border: 3px solid #262626; border-top-color: var(--orange); border-radius: 50%; animation: sp .7s linear infinite; }
@keyframes sp { to { transform: rotate(360deg); } }
</style>
