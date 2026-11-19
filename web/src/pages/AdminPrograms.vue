<script setup>
import { ref, reactive, onMounted } from 'vue'
import AdminNav from '../components/AdminNav.vue'
import { api, fieldErrors } from '../api.js'
import { loadContent } from '../data.js'
import { toast } from '../stores/app.js'

const programs = ref([])
const allPlans = ref([])
const allExercises = ref([])
const loading = ref(true)
const saving = ref(false)
const busy = reactive({})

const editing = ref(null)
const errs = ref({})
const thumbFile = ref(null)
const exSearch = ref('')

async function load() {
  loading.value = true
  try {
    const [pRes, exRes] = await Promise.all([api('/admin/programs'), api('/admin/exercises')])
    programs.value = pRes.data
    allPlans.value = pRes.plans
    allExercises.value = exRes.data
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    loading.value = false
  }
}
onMounted(load)

function openCreate() {
  errs.value = {}
  thumbFile.value = null
  exSearch.value = ''
  editing.value = {
    id: null,
    slug: '',
    title: '',
    subtitle: '',
    pitch: '',
    days: 30,
    minutes: '15–20',
    level: 'مبتدئ ← متوسط',
    thumb: '/img/thumbs/cover-karsh.webp',
    tag: '',
    sort: programs.value.length,
    is_active: true,
    plan_ids: [],
    weeks: [{ title: 'الأسبوع الأول', pool: allExercises.value.slice(0, 3).map((e) => e.slug) }],
  }
}

function openEdit(p) {
  errs.value = {}
  thumbFile.value = null
  exSearch.value = ''
  editing.value = {
    ...p,
    tag: p.tag || '',
    plan_ids: [...(p.plan_ids || [])],
    weeks: (p.weeks || []).map((w) => ({ title: w.title, pool: [...(w.pool || [])] })),
  }
}

function addWeek() {
  if (!editing.value) return
  editing.value.weeks.push({
    title: `الأسبوع ${editing.value.weeks.length + 1}`,
    pool: [],
  })
}

function removeWeek(idx) {
  if (!editing.value || editing.value.weeks.length <= 1) return
  editing.value.weeks.splice(idx, 1)
}

function toggleExerciseInWeek(week, slug) {
  const i = week.pool.indexOf(slug)
  if (i >= 0) week.pool.splice(i, 1)
  else week.pool.push(slug)
}

function togglePlan(planId) {
  const i = editing.value.plan_ids.indexOf(planId)
  if (i >= 0) editing.value.plan_ids.splice(i, 1)
  else editing.value.plan_ids.push(planId)
}

async function save() {
  const ed = editing.value
  if (!ed) return
  if (ed.weeks.some((w) => !w.pool.length)) {
    return toast('كل أسبوع لازم يكون فيه تمرين واحد على الأقل', 'err')
  }
  saving.value = true
  errs.value = {}
  try {
    const form = new FormData()
    if (ed.id) form.append('_method', 'PUT')
    if (ed.slug.trim()) form.append('slug', ed.slug.trim())
    form.append('title', ed.title.trim())
    form.append('subtitle', ed.subtitle.trim())
    form.append('pitch', ed.pitch.trim())
    form.append('days', String(Number(ed.days) || 14))
    form.append('minutes', ed.minutes.trim())
    form.append('level', ed.level.trim())
    form.append('thumb', ed.thumb.trim())
    form.append('tag', ed.tag.trim())
    form.append('sort', String(Number(ed.sort) || 0))
    form.append('is_active', ed.is_active ? '1' : '0')
    form.append('plan_ids', JSON.stringify(ed.plan_ids))
    form.append('weeks', JSON.stringify(ed.weeks))
    if (thumbFile.value) form.append('thumb_file', thumbFile.value)

    const url = ed.id ? `/admin/programs/${ed.id}` : '/admin/programs'
    const res = await api(url, { method: 'POST', form })
    if (ed.id) {
      const idx = programs.value.findIndex((x) => x.id === ed.id)
      if (idx >= 0) programs.value[idx] = res.data
    } else {
      programs.value.push(res.data)
    }
    programs.value.sort((a, b) => a.sort - b.sort || a.id - b.id)
    editing.value = null
    await loadContent()
    toast(ed.id ? 'اتحفظ البرنامج ✓' : 'اتضاف البرنامج ✓')
  } catch (e) {
    errs.value = fieldErrors(e)
    toast(Object.values(errs.value)[0] || e.message, 'err')
  } finally {
    saving.value = false
  }
}

async function toggleActive(p) {
  busy[p.id] = true
  try {
    const res = await api(`/admin/programs/${p.id}`, { method: 'PUT', body: { is_active: !p.is_active } })
    const idx = programs.value.findIndex((x) => x.id === p.id)
    if (idx >= 0) programs.value[idx] = res.data
    await loadContent()
    toast(res.data.is_active ? 'اتفعّل ✓' : 'اتخفى ✓')
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[p.id] = false
  }
}

async function removeProgram(p) {
  if (!confirm(`متأكد إنك عايز تحذف برنامج "${p.title}"؟ (حذف ناعم)`)) return
  busy[p.id] = true
  try {
    await api(`/admin/programs/${p.id}`, { method: 'DELETE' })
    programs.value = programs.value.filter((x) => x.id !== p.id)
    await loadContent()
    toast('اتحذف ✓')
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[p.id] = false
  }
}

const exName = (slug) => allExercises.value.find((e) => e.slug === slug)?.title || slug
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <div>
        <h1>البرامج والأسابيع</h1>
        <p class="muted">عدّل البرامج، عدد الأيام، الأسابيع وتمارين كل أسبوع، والباقات اللي بتفتح كل برنامج.</p>
      </div>
      <button class="btn btn-primary btn-sm" @click="openCreate">+ برنامج جديد</button>
    </header>

    <div v-if="loading" class="loading"><span class="spin" aria-label="بنحمّل"></span></div>

    <div v-else class="plist">
      <article v-for="p in programs" :key="p.id" class="pcard" :class="{ off: !p.is_active }">
        <img :src="p.thumb" :alt="p.title" class="pthumb" />
        <div class="pbody">
          <div class="ph">
            <div>
              <h2>{{ p.title }} <small class="en muted">/{{ p.slug }}</small></h2>
              <p class="muted">{{ p.subtitle }}</p>
            </div>
            <span v-if="!p.is_active" class="off-b">مخفي</span>
          </div>
          <p class="pitch">{{ p.pitch }}</p>
          <div class="chips">
            <span><b class="num">{{ p.days }}</b> يوم</span>
            <span><b class="num">{{ p.minutes }}</b> دقيقة</span>
            <span>{{ p.level }}</span>
            <span><b class="num">{{ p.weeks.length }}</b> أسابيع</span>
            <span v-if="p.plan_names.length" class="amber">الباقات: {{ p.plan_names.join('، ') }}</span>
          </div>
          <div class="wpreview">
            <div v-for="w in p.weeks" :key="w.number" class="wchip">
              <b>أ{{ w.number }}: {{ w.title }}</b>
              <small class="en muted">{{ w.pool.map(exName).join(' · ') }}</small>
            </div>
          </div>
          <div class="acts">
            <button class="btn btn-primary btn-sm" @click="openEdit(p)">تعديل البرنامج والأسابيع</button>
            <button class="btn btn-ghost btn-sm" :disabled="busy[p.id]" @click="toggleActive(p)">{{ p.is_active ? 'إخفاء' : 'إظهار' }}</button>
            <button class="btn btn-ghost btn-sm danger" :disabled="busy[p.id]" @click="removeProgram(p)">حذف</button>
          </div>
        </div>
      </article>
    </div>

    <!-- MODAL -->
    <div v-if="editing" class="modal" @click.self="editing = null">
      <form class="modal-card" @submit.prevent="save">
        <header class="m-head">
          <h2>{{ editing.id ? `تعديل: ${editing.title}` : 'برنامج جديد' }}</h2>
          <button type="button" class="x" aria-label="إغلاق" @click="editing = null">✕</button>
        </header>

        <div class="row2">
          <label class="field"><span>اسم البرنامج *</span><input v-model="editing.title" class="input" required /></label>
          <label class="field"><span>الـ Slug</span><input v-model="editing.slug" class="input ltr" placeholder="30 / home-fatburn" /></label>
        </div>
        <div class="row2">
          <label class="field"><span>العنوان الفرعي *</span><input v-model="editing.subtitle" class="input" required /></label>
          <label class="field"><span>شارة (اختياري)</span><input v-model="editing.tag" class="input" placeholder="الأكتر طلبًا" /></label>
        </div>
        <label class="field"><span>الوصف (Pitch) *</span><textarea v-model="editing.pitch" class="input ta" rows="2" required></textarea></label>

        <div class="row4">
          <label class="field"><span>عدد الأيام *</span><input v-model="editing.days" type="number" min="1" max="365" class="input ltr" required /></label>
          <label class="field"><span>المدة (دقائق) *</span><input v-model="editing.minutes" class="input ltr" required placeholder="15–20" /></label>
          <label class="field"><span>المستوى *</span><input v-model="editing.level" class="input" required /></label>
          <label class="field"><span>الترتيب</span><input v-model="editing.sort" type="number" min="0" class="input ltr" /></label>
        </div>

        <div class="row2">
          <label class="field"><span>رابط البوستر</span><input v-model="editing.thumb" class="input ltr" /></label>
          <label class="field"><span>أو ارفع صورة جديدة</span><input type="file" accept="image/*" class="input" @change="(e) => (thumbFile = e.target.files?.[0] || null)" /></label>
        </div>

        <div class="sec">
          <h3>الباقات اللي بتفتح البرنامج ده</h3>
          <div class="plan-checks">
            <label v-for="pl in allPlans" :key="pl.id" class="check">
              <input type="checkbox" :checked="editing.plan_ids.includes(pl.id)" @change="togglePlan(pl.id)" />
              {{ pl.name }} ({{ pl.price }} ج.م)
            </label>
          </div>
          <label class="check"><input v-model="editing.is_active" type="checkbox" /> ظاهر في الموقع</label>
        </div>

        <!-- WEEKS BUILDER -->
        <div class="sec">
          <header class="whead">
            <h3>أسابيع البرنامج وتمارين كل أسبوع</h3>
            <button type="button" class="btn btn-ghost btn-sm" @click="addWeek">+ أسبوع جديد</button>
          </header>
          <input v-model="exSearch" class="input" placeholder="فلتر التمارين بالاسم عشان تختار بسرعة…" />

          <div v-for="(w, wi) in editing.weeks" :key="wi" class="wbox">
            <div class="wbox-h">
              <b class="num amber">أسبوع {{ wi + 1 }}</b>
              <input v-model="w.title" class="input" placeholder="عنوان الأسبوع" required />
              <button v-if="editing.weeks.length > 1" type="button" class="btn btn-ghost btn-sm danger" @click="removeWeek(wi)">حذف</button>
            </div>
            <div class="ex-picker">
              <button
                v-for="ex in allExercises.filter((e) => !exSearch.trim() || e.title.toLowerCase().includes(exSearch.toLowerCase()) || e.hook.includes(exSearch))"
                :key="ex.slug"
                type="button"
                class="ep"
                :class="{ on: w.pool.includes(ex.slug) }"
                @click="toggleExerciseInWeek(w, ex.slug)"
              >
                <span class="en">{{ ex.title }}</span>
              </button>
            </div>
            <small class="muted">متحدد: {{ w.pool.length }} تمرين</small>
          </div>
        </div>

        <div class="m-acts">
          <button class="btn btn-primary btn-sm" :disabled="saving">{{ saving ? 'بنحفظ…' : 'احفظ البرنامج' }}</button>
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
.plist { display: grid; gap: 14px; }
.pcard { display: grid; gap: 14px; padding: 14px; border-radius: 16px; background: #141414; border: 1px solid #242424; }
@media (min-width: 720px) { .pcard { grid-template-columns: 150px 1fr; } }
.pcard.off { opacity: .55; }
.pthumb { width: 100%; aspect-ratio: 4/5; object-fit: cover; border-radius: 12px; background: #0a0a0a; }
.pbody { display: grid; gap: 8px; align-content: start; }
.ph { display: flex; justify-content: space-between; gap: 10px; }
.ph h2 { font-size: 1.4rem; }
.off-b { background: #2a1818; color: #ff8a8a; padding: 3px 10px; border-radius: 999px; font-size: .78rem; font-weight: 800; align-self: start; }
.pitch { font-size: .9rem; color: #ddd; }
.chips { display: flex; flex-wrap: wrap; gap: 6px; }
.chips span { font-size: .78rem; background: #1f1f1f; padding: 3px 10px; border-radius: 999px; font-weight: 700; }
.amber { color: var(--amber); }
.wpreview { display: grid; gap: 6px; margin-top: 4px; }
.wchip { padding: 7px 10px; border-radius: 10px; background: #0e0e0e; border: 1px solid #202020; display: grid; gap: 2px; font-size: .82rem; }
.acts { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.danger { color: #ff7a7a; border-color: #3a2020; }

.modal { position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,.8); display: grid; place-items: center; padding: 14px; }
.modal-card { width: 100%; max-width: 780px; max-height: 92svh; overflow: auto; background: #141414; border: 1px solid #2b2b2b; border-radius: 18px; padding: 18px; display: grid; gap: 12px; }
.m-head { display: flex; justify-content: space-between; align-items: center; }
.m-head h2 { font-size: 1.4rem; }
.x { width: 34px; height: 34px; border-radius: 50%; background: #222; color: #fff; display: grid; place-items: center; }
.row2, .row4 { display: grid; gap: 10px; }
@media (min-width: 620px) { .row2 { grid-template-columns: 1fr 1fr; } .row4 { grid-template-columns: repeat(4, 1fr); } }
.field { display: grid; gap: 4px; }
.field span { font-size: .8rem; font-weight: 700; color: var(--muted); }
.ta { min-height: 68px; padding-block: 8px; resize: vertical; }
.sec { display: grid; gap: 10px; padding: 12px; border-radius: 12px; background: #0e0e0e; border: 1px solid #202020; }
.sec h3 { font-size: 1rem; color: var(--amber); }
.plan-checks { display: flex; flex-wrap: wrap; gap: 14px; }
.check { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: .88rem; cursor: pointer; }
.check input { width: 18px; height: 18px; accent-color: var(--orange); }
.whead { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.wbox { display: grid; gap: 8px; padding: 10px; border-radius: 12px; background: #141414; border: 1px solid #262626; }
.wbox-h { display: flex; align-items: center; gap: 8px; }
.ex-picker { display: flex; flex-wrap: wrap; gap: 6px; }
.ep { padding: 5px 10px; border-radius: 999px; background: #1e1e1e; border: 1px solid #2c2c2c; color: var(--muted); font-size: .8rem; font-weight: 700; }
.ep.on { background: var(--orange); color: #0a0a0a; border-color: var(--orange); }
.m-acts { display: flex; gap: 8px; }
.loading { display: grid; place-items: center; padding: 48px; }
.spin { width: 32px; height: 32px; border: 3px solid #262626; border-top-color: var(--orange); border-radius: 50%; animation: sp .7s linear infinite; }
@keyframes sp { to { transform: rotate(360deg); } }
</style>
