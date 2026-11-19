<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import AdminNav from '../components/AdminNav.vue'
import { api, fieldErrors } from '../api.js'
import { categories, loadContent, fmt } from '../data.js'
import { toast } from '../stores/app.js'

const items = ref([])
const loading = ref(true)
const saving = ref(false)
const busy = reactive({})

const q = ref('')
const cat = ref('all')
const videoFilter = ref('all') // all | 1 | 0

const editing = ref(null) // null or form object
const errs = ref({})
const thumbFile = ref(null)
const videoFile = ref(null)

const catOpts = categories.filter((c) => c.id !== 'all')
const levels = ['', 'مبتدئ', 'متوسط', 'متقدم']
const catLabel = (id) => categories.find((c) => c.id === id)?.label || id

async function load() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (q.value.trim()) params.set('q', q.value.trim())
    if (cat.value !== 'all') params.set('category', cat.value)
    if (videoFilter.value !== 'all') params.set('has_video', videoFilter.value)
    const qs = params.toString()
    items.value = (await api(`/admin/exercises${qs ? '?' + qs : ''}`)).data
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    loading.value = false
  }
}
onMounted(load)

const withVideoCount = computed(() => items.value.filter((x) => x.video_url).length)

function openCreate() {
  errs.value = {}
  thumbFile.value = null
  videoFile.value = null
  editing.value = {
    id: null,
    slug: '',
    title: '',
    hook: '',
    category: 'belly',
    seconds: 30,
    sets: 3,
    work_seconds: 40,
    rest_seconds: 20,
    reps: '',
    level: 'مبتدئ',
    muscles: '',
    equipment: 'بدون أدوات',
    calories_est: '',
    tips_text: '',
    description: '',
    thumb: '/img/thumbs/squat.webp',
    video_url: '',
    is_home: true,
    is_active: true,
    sort: items.value.length,
  }
}

function openEdit(item) {
  errs.value = {}
  thumbFile.value = null
  videoFile.value = null
  editing.value = {
    ...item,
    reps: item.reps || '',
    level: item.level || '',
    muscles: item.muscles || '',
    equipment: item.equipment || '',
    calories_est: item.calories_est ?? '',
    description: item.description || '',
    video_url: item.video_url || '',
    tips_text: (item.tips || []).join('\n'),
  }
}

async function save() {
  const ed = editing.value
  if (!ed) return
  saving.value = true
  errs.value = {}
  try {
    const form = new FormData()
    if (ed.id) form.append('_method', 'PUT')
    if (ed.slug.trim()) form.append('slug', ed.slug.trim())
    form.append('title', ed.title.trim())
    form.append('hook', ed.hook.trim())
    form.append('category', ed.category)
    form.append('seconds', String(Number(ed.seconds) || 25))
    if (ed.sets !== '' && ed.sets !== null) form.append('sets', String(Number(ed.sets)))
    if (ed.work_seconds !== '' && ed.work_seconds !== null) form.append('work_seconds', String(Number(ed.work_seconds)))
    if (ed.rest_seconds !== '' && ed.rest_seconds !== null) form.append('rest_seconds', String(Number(ed.rest_seconds)))
    form.append('reps', ed.reps.trim())
    form.append('level', ed.level)
    form.append('muscles', ed.muscles.trim())
    form.append('equipment', ed.equipment.trim())
    if (ed.calories_est !== '' && ed.calories_est !== null) form.append('calories_est', String(Number(ed.calories_est)))
    form.append('tips_text', ed.tips_text)
    form.append('description', ed.description.trim())
    form.append('thumb', ed.thumb.trim())
    form.append('video_url', ed.video_url.trim())
    form.append('is_home', ed.is_home ? '1' : '0')
    form.append('is_active', ed.is_active ? '1' : '0')
    form.append('sort', String(Number(ed.sort) || 0))
    if (thumbFile.value) form.append('thumb_file', thumbFile.value)
    if (videoFile.value) form.append('video_file', videoFile.value)

    const url = ed.id ? `/admin/exercises/${ed.id}` : '/admin/exercises'
    const res = await api(url, { method: 'POST', form })
    if (ed.id) {
      const idx = items.value.findIndex((x) => x.id === ed.id)
      if (idx >= 0) items.value[idx] = res.data
    } else {
      items.value.push(res.data)
    }
    items.value.sort((a, b) => a.sort - b.sort || a.id - b.id)
    editing.value = null
    await loadContent()
    toast(ed.id ? 'اتحفظ التعديل ✓' : 'اتضاف التمرين ✓')
  } catch (e) {
    errs.value = fieldErrors(e)
    toast(Object.values(errs.value)[0] || e.message, 'err')
  } finally {
    saving.value = false
  }
}

async function toggleActive(item) {
  busy[item.id] = true
  try {
    const res = await api(`/admin/exercises/${item.id}`, {
      method: 'PUT',
      body: { is_active: !item.is_active },
    })
    const idx = items.value.findIndex((x) => x.id === item.id)
    if (idx >= 0) items.value[idx] = res.data
    await loadContent()
    toast(res.data.is_active ? 'اتفعّل ✓' : 'اتخفى ✓')
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[item.id] = false
  }
}

async function removeItem(item) {
  if (!confirm(`متأكد إنك عايز تحذف "${item.title}"؟ (حذف ناعم بس)`)) return
  busy[item.id] = true
  try {
    await api(`/admin/exercises/${item.id}`, { method: 'DELETE' })
    items.value = items.value.filter((x) => x.id !== item.id)
    await loadContent()
    toast('اتحذف ✓')
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[item.id] = false
  }
}

const isDirectVideo = (url) => /\.(mp4|webm|mov|m4v)(\?.*)?$/i.test(url || '') || (url || '').startsWith('/api/media/videos/')
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <div>
        <h1>الفيديوهات والتمارين</h1>
        <p class="muted">تحكم كامل بكل فيديو وتمرين: رفع الفيديو والبوستر، المدة، الجولات، العضلات، والنصائح.</p>
      </div>
      <div class="top-acts">
        <span class="badge"><span class="num">{{ items.length }}</span> تمرين · <span class="num">{{ withVideoCount }}</span> بفيديو</span>
        <button class="btn btn-primary btn-sm" @click="openCreate">+ إضافة فيديو / تمرين</button>
      </div>
    </header>

    <!-- FILTERS -->
    <div class="toolbar">
      <input v-model="q" class="input search" placeholder="دوّر بالاسم أو العضلة أو الـ slug…" @keyup.enter="load" />
      <select v-model="cat" class="input sel" @change="load">
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.label }}</option>
      </select>
      <select v-model="videoFilter" class="input sel" @change="load">
        <option value="all">الكل (فيديو / بدون)</option>
        <option value="1">بفيديو مرفوع/مربوط</option>
        <option value="0">بدون فيديو لسه</option>
      </select>
      <button class="btn btn-ghost btn-sm" :disabled="loading" @click="load">بحث</button>
    </div>

    <div v-if="loading" class="loading"><span class="spin" aria-label="بنحمّل"></span></div>
    <p v-else-if="!items.length" class="muted empty">مفيش تمارين مطابقة للبحث.</p>

    <div v-else class="grid">
      <article v-for="item in items" :key="item.id" class="card" :class="{ off: !item.is_active }">
        <div class="thumb">
          <img :src="item.thumb" :alt="item.title" loading="lazy" />
          <span class="pill-sort num">#{{ item.sort }}</span>
          <span class="pill-dur num">{{ fmt(item.seconds) }}</span>
          <span v-if="item.video_url" class="pill-vid ok">▶ فيديو</span>
          <span v-else class="pill-vid no">بدون فيديو</span>
          <span v-if="!item.is_active" class="pill-off">مخفي</span>
        </div>
        <div class="body">
          <div class="h-row">
            <b class="en title">{{ item.title }}</b>
            <span class="cat-tag">{{ catLabel(item.category) }}</span>
          </div>
          <p class="hook">{{ item.hook }}</p>
          <div class="chips">
            <span v-if="item.level">{{ item.level }}</span>
            <span v-if="item.muscles">{{ item.muscles }}</span>
            <span v-if="item.is_home" class="num">{{ item.sets || 3 }}×{{ item.work_seconds || 40 }}ث</span>
            <span v-if="item.reps">{{ item.reps }}</span>
            <span v-if="item.equipment">{{ item.equipment }}</span>
          </div>
          <div class="acts">
            <button type="button" class="btn btn-primary btn-sm" @click="openEdit(item)">تعديل التفاصيل</button>
            <button type="button" class="btn btn-ghost btn-sm" :disabled="busy[item.id]" @click="toggleActive(item)">
              {{ item.is_active ? 'إخفاء' : 'إظهار' }}
            </button>
            <button type="button" class="btn btn-ghost btn-sm danger" :disabled="busy[item.id]" @click="removeItem(item)">حذف</button>
          </div>
        </div>
      </article>
    </div>

    <!-- CREATE / EDIT MODAL -->
    <div v-if="editing" class="modal" @click.self="editing = null">
      <form class="modal-card" @submit.prevent="save">
        <header class="m-head">
          <h2>{{ editing.id ? `تعديل: ${editing.title}` : 'إضافة فيديو / تمرين جديد' }}</h2>
          <button type="button" class="x" aria-label="إغلاق" @click="editing = null">✕</button>
        </header>

        <section class="m-sec">
          <h3>1. البيانات الأساسية</h3>
          <div class="row2">
            <label class="field">
              <span>الاسم الإنجليزي (Title) *</span>
              <input v-model="editing.title" class="input ltr" required placeholder="Squat / Plank" />
              <small v-if="errs.title" class="err">{{ errs.title }}</small>
            </label>
            <label class="field">
              <span>الـ Slug (يتولّد تلقائي لو فاضي)</span>
              <input v-model="editing.slug" class="input ltr" placeholder="squat" />
              <small v-if="errs.slug" class="err">{{ errs.slug }}</small>
            </label>
          </div>
          <label class="field">
            <span>العنوان العربي / الهوك *</span>
            <input v-model="editing.hook" class="input" required placeholder="مثال: تمرين من البيت هيخسّسك الكرش" />
          </label>
          <div class="row3">
            <label class="field">
              <span>التصنيف *</span>
              <select v-model="editing.category" class="input">
                <option v-for="c in catOpts" :key="c.id" :value="c.id">{{ c.label }}</option>
              </select>
            </label>
            <label class="field">
              <span>المستوى</span>
              <select v-model="editing.level" class="input">
                <option v-for="l in levels" :key="l" :value="l">{{ l || '— بدون —' }}</option>
              </select>
            </label>
            <label class="field">
              <span>الترتيب</span>
              <input v-model="editing.sort" type="number" min="0" class="input ltr" />
            </label>
          </div>
          <div class="checks">
            <label class="check"><input v-model="editing.is_home" type="checkbox" /> تمرين (يظهر فيه جولات/مؤقت)</label>
            <label class="check"><input v-model="editing.is_active" type="checkbox" /> ظاهر في المكتبة</label>
          </div>
        </section>

        <section class="m-sec">
          <h3>2. الفيديو والبوستر</h3>
          <div class="row2">
            <label class="field">
              <span>رابط الفيديو (MP4 / YouTube / Bunny / Vimeo)</span>
              <input v-model="editing.video_url" class="input ltr" placeholder="https://... أو ارفع ملف تحت" />
            </label>
            <label class="field">
              <span>أو ارفع ملف فيديو (MP4 / WebM حتى 100MB)</span>
              <input type="file" accept="video/mp4,video/webm,video/quicktime" class="input" @change="(e) => (videoFile = e.target.files?.[0] || null)" />
            </label>
          </div>
          <video v-if="editing.video_url && isDirectVideo(editing.video_url)" :src="editing.video_url" class="vprev" controls muted playsinline></video>
          <div class="row2">
            <label class="field">
              <span>رابط البوستر (Thumbnail)</span>
              <input v-model="editing.thumb" class="input ltr" placeholder="/img/thumbs/squat.webp" />
            </label>
            <label class="field">
              <span>أو ارفع صورة بوستر جديدة (تتحول WebP)</span>
              <input type="file" accept="image/png,image/jpeg,image/webp" class="input" @change="(e) => (thumbFile = e.target.files?.[0] || null)" />
            </label>
          </div>
        </section>

        <section class="m-sec">
          <h3>3. تفاصيل التمرين الدقيقة</h3>
          <div class="row4">
            <label class="field">
              <span>مدة الفيديو (ث) *</span>
              <input v-model="editing.seconds" type="number" min="1" max="3600" class="input ltr" required />
            </label>
            <label class="field">
              <span>الجولات</span>
              <input v-model="editing.sets" type="number" min="1" max="20" class="input ltr" />
            </label>
            <label class="field">
              <span>شغل (ثانية)</span>
              <input v-model="editing.work_seconds" type="number" min="5" max="600" class="input ltr" />
            </label>
            <label class="field">
              <span>راحة (ثانية)</span>
              <input v-model="editing.rest_seconds" type="number" min="0" max="600" class="input ltr" />
            </label>
          </div>
          <div class="row3">
            <label class="field">
              <span>التكرارات (اختياري)</span>
              <input v-model="editing.reps" class="input" placeholder="مثال: 12-15 عدة" />
            </label>
            <label class="field">
              <span>الأدوات</span>
              <input v-model="editing.equipment" class="input" placeholder="بدون أدوات / دمبل" />
            </label>
            <label class="field">
              <span>سعرات تقريبية</span>
              <input v-model="editing.calories_est" type="number" min="0" class="input ltr" placeholder="45" />
            </label>
          </div>
          <label class="field">
            <span>العضلات المستهدفة</span>
            <input v-model="editing.muscles" class="input" placeholder="مثال: البطن + الكتف + نَفَس" />
          </label>
          <label class="field">
            <span>نصائح الأداء الصح (كل نصيحة في سطر)</span>
            <textarea v-model="editing.tips_text" class="input ta" rows="4" placeholder="إيدك تحت كتفك بالظبط&#10;وسطك مفرود"></textarea>
          </label>
          <label class="field">
            <span>وصف تفصيلي إضافي (اختياري)</span>
            <textarea v-model="editing.description" class="input ta" rows="3" placeholder="شرح كامل للحركة أو بدائل المبتدئين…"></textarea>
          </label>
        </section>

        <div class="m-acts">
          <button class="btn btn-primary btn-sm" :disabled="saving">{{ saving ? 'بنحفظ…' : 'احفظ' }}</button>
          <button type="button" class="btn btn-ghost btn-sm" @click="editing = null">إلغاء</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.adm { padding-block: 24px 70px; display: grid; gap: 16px; max-width: 1160px; }
.top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.top h1 { font-size: 2.2rem; }
.top-acts { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.toolbar { display: flex; gap: 8px; flex-wrap: wrap; }
.search { flex: 1 1 220px; }
.sel { width: auto; min-width: 160px; }

.grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
@media (min-width: 680px) { .grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1020px) { .grid { grid-template-columns: repeat(3, 1fr); } }

.card { background: #141414; border: 1px solid #242424; border-radius: 16px; overflow: hidden; display: grid; grid-template-columns: 110px 1fr; }
.card.off { opacity: .55; }
.thumb { position: relative; background: #0a0a0a; min-height: 155px; }
.thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pill-sort { position: absolute; top: 6px; inset-inline-start: 6px; background: rgba(10,10,10,.85); color: var(--amber); padding: 2px 7px; border-radius: 999px; font-size: .75rem; border: 1px solid #333; }
.pill-dur { position: absolute; bottom: 6px; inset-inline-start: 6px; background: rgba(10,10,10,.85); padding: 2px 7px; border-radius: 999px; font-size: .75rem; }
.pill-vid { position: absolute; bottom: 6px; inset-inline-end: 6px; padding: 2px 7px; border-radius: 999px; font-size: .7rem; font-weight: 800; }
.pill-vid.ok { background: #122a1b; color: #5ef09b; }
.pill-vid.no { background: #261f12; color: #ffbe5c; }
.pill-off { position: absolute; top: 6px; inset-inline-end: 6px; background: #2a1818; color: #ff8a8a; padding: 2px 7px; border-radius: 999px; font-size: .7rem; font-weight: 800; }

.body { padding: 12px; display: grid; gap: 6px; align-content: start; min-width: 0; }
.h-row { display: flex; justify-content: space-between; align-items: center; gap: 6px; }
.title { font-size: 1.25rem; line-height: 1.1; }
.cat-tag { font-size: .72rem; font-weight: 800; color: var(--orange); background: #22160d; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
.hook { font-size: .88rem; color: #e8e8e8; line-height: 1.4; }
.chips { display: flex; flex-wrap: wrap; gap: 5px; }
.chips span { font-size: .73rem; background: #1e1e1e; color: var(--muted); padding: 2px 8px; border-radius: 999px; font-weight: 700; }
.acts { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.acts .btn { padding: 5px 10px; font-size: .78rem; }
.danger { color: #ff7a7a; border-color: #3a2020; }

.modal { position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,.8); display: grid; place-items: center; padding: 14px; }
.modal-card { width: 100%; max-width: 720px; max-height: 92svh; overflow: auto; background: #141414; border: 1px solid #2b2b2b; border-radius: 18px; padding: 18px; display: grid; gap: 16px; }
.m-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.m-head h2 { font-size: 1.45rem; }
.x { width: 34px; height: 34px; border-radius: 50%; background: #222; color: #fff; display: grid; place-items: center; }
.m-sec { display: grid; gap: 10px; padding: 12px; border-radius: 12px; background: #0f0f0f; border: 1px solid #202020; }
.m-sec h3 { font-size: 1rem; color: var(--amber); }
.row2, .row3, .row4 { display: grid; gap: 10px; }
@media (min-width: 620px) {
  .row2 { grid-template-columns: 1fr 1fr; }
  .row3 { grid-template-columns: repeat(3, 1fr); }
  .row4 { grid-template-columns: repeat(4, 1fr); }
}
.field { display: grid; gap: 4px; }
.field span { font-size: .8rem; font-weight: 700; color: var(--muted); }
.ta { min-height: 82px; padding-block: 8px; line-height: 1.6; resize: vertical; }
.checks { display: flex; flex-wrap: wrap; gap: 16px; }
.check { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: .88rem; cursor: pointer; }
.check input { width: 18px; height: 18px; accent-color: var(--orange); }
.vprev { width: 100%; max-height: 240px; border-radius: 10px; background: #000; }
.err { color: var(--danger); font-size: .78rem; font-weight: 700; }
.m-acts { display: flex; gap: 8px; }
.empty { padding: 28px 0; }
.loading { display: grid; place-items: center; padding: 48px; }
.spin { width: 32px; height: 32px; border: 3px solid #262626; border-top-color: var(--orange); border-radius: 50%; animation: sp .7s linear infinite; }
@keyframes sp { to { transform: rotate(360deg); } }
</style>
