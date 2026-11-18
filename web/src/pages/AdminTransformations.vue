<script setup>
import { ref, reactive, onMounted } from 'vue'
import AdminNav from '../components/AdminNav.vue'
import { api, fieldErrors } from '../api.js'
import { loadContent } from '../data.js'
import { toast } from '../stores/app.js'

const items = ref([])
const loading = ref(true)
const uploading = ref(false)
const busy = reactive({})

const files = ref([])
const title = ref('')
const age = ref('')
const subtitle = ref('')
const isActive = ref(true)
const fileInput = ref(null)

const editing = ref(null) // { id, title, age, subtitle, sort, is_active, image_url, newFile }

async function load() {
  loading.value = true
  try {
    items.value = (await api('/admin/transformations')).data
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    loading.value = false
  }
}
onMounted(load)

function onPickFiles(e) {
  files.value = Array.from(e.target.files || [])
}

async function upload() {
  if (!files.value.length) return toast('اختار صورة أو أكتر الأول', 'err')
  uploading.value = true
  let ok = 0
  try {
    for (const file of files.value) {
      const form = new FormData()
      form.append('image', file)
      if (files.value.length === 1 && title.value.trim()) form.append('title', title.value.trim())
      if (files.value.length === 1 && age.value !== '' && age.value !== null) form.append('age', String(Number(age.value)))
      if (files.value.length === 1 && subtitle.value.trim()) form.append('subtitle', subtitle.value.trim())
      form.append('is_active', isActive.value ? '1' : '0')
      const res = await api('/admin/transformations', { method: 'POST', form })
      items.value.push(res.data)
      ok++
    }
    files.value = []
    title.value = ''
    age.value = ''
    subtitle.value = ''
    if (fileInput.value) fileInput.value.value = ''
    await loadContent()
    toast(ok > 1 ? `اترفع ${ok} صور ✓` : 'اترفعت الصورة ✓')
  } catch (e) {
    const fe = fieldErrors(e)
    toast(Object.values(fe)[0] || e.message, 'err')
  } finally {
    uploading.value = false
  }
}

function startEdit(item) {
  editing.value = {
    id: item.id,
    title: item.title || '',
    age: item.age ?? '',
    subtitle: item.subtitle || '',
    sort: item.sort,
    is_active: item.is_active,
    image_url: item.image_url,
    newFile: null,
  }
}

async function saveEdit() {
  const ed = editing.value
  if (!ed) return
  busy[ed.id] = true
  try {
    const form = new FormData()
    form.append('_method', 'PUT')
    form.append('title', ed.title.trim())
    if (ed.age !== '' && ed.age !== null) form.append('age', String(Number(ed.age)))
    else form.append('age', '')
    form.append('subtitle', ed.subtitle.trim())
    form.append('sort', String(Number(ed.sort) || 0))
    form.append('is_active', ed.is_active ? '1' : '0')
    if (ed.newFile) form.append('image', ed.newFile)

    const res = await api(`/admin/transformations/${ed.id}`, { method: 'POST', form })
    const idx = items.value.findIndex((x) => x.id === ed.id)
    if (idx >= 0) items.value[idx] = res.data
    items.value.sort((a, b) => a.sort - b.sort || a.id - b.id)
    editing.value = null
    await loadContent()
    toast('اتحفظ التعديل ✓')
  } catch (e) {
    const fe = fieldErrors(e)
    toast(Object.values(fe)[0] || e.message, 'err')
  } finally {
    busy[ed.id] = false
  }
}

async function toggleActive(item) {
  busy[item.id] = true
  try {
    const res = await api(`/admin/transformations/${item.id}`, {
      method: 'PUT',
      body: { is_active: !item.is_active },
    })
    const idx = items.value.findIndex((x) => x.id === item.id)
    if (idx >= 0) items.value[idx] = res.data
    await loadContent()
    toast(res.data.is_active ? 'اتفعّلت ✓' : 'اتخفت ✓')
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[item.id] = false
  }
}

async function removeItem(item) {
  if (!confirm('متأكد إنك عايز تشيل الصورة دي من التحولات؟')) return
  busy[item.id] = true
  try {
    await api(`/admin/transformations/${item.id}`, { method: 'DELETE' })
    items.value = items.value.filter((x) => x.id !== item.id)
    await loadContent()
    toast('اتشالت ✓')
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[item.id] = false
  }
}
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <div>
        <h1>التحولات</h1>
        <p class="muted">ارفع صور التحولات، عدّل الترتيب أو الإظهار، وأي تعديل بيظهر على الصفحة الرئيسية فورًا.</p>
      </div>
      <span class="badge">{{ items.length }} صورة</span>
    </header>

    <!-- CREATE -->
    <form class="card add-box" @submit.prevent="upload">
      <h2>إضافة تحول جديد</h2>
      <div class="row2">
        <label class="field">
          <span>اختار صورة (أو أكتر في نفس الوقت)</span>
          <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp" multiple class="input" @change="onPickFiles" />
          <small v-if="files.length" class="muted">متحدد: {{ files.length }} {{ files.length === 1 ? 'صورة' : 'صور' }}</small>
        </label>
        <label class="check self-end">
          <input v-model="isActive" type="checkbox" /> ظاهرة في الموقع
        </label>
      </div>
      <div v-if="files.length <= 1" class="row3">
        <label class="field">
          <span>الاسم (اختياري)</span>
          <input v-model="title" class="input" placeholder="مثال: كريم محمود" maxlength="80" />
        </label>
        <label class="field">
          <span>السن (اختياري)</span>
          <input v-model="age" type="number" min="10" max="99" class="input ltr" placeholder="28" />
        </label>
        <label class="field">
          <span>تفصيل / نتيجة (اختياري)</span>
          <input v-model="subtitle" class="input" placeholder="مثال: -14 كيلو في البيت" maxlength="120" />
        </label>
      </div>
      <button class="btn btn-primary btn-sm" :disabled="uploading || !files.length">
        {{ uploading ? 'بنرفع…' : 'ارفع دلوقتي' }}
      </button>
    </form>

    <div v-if="loading" class="loading"><span class="spin" aria-label="بنحمّل"></span></div>

    <div v-else class="grid">
      <article v-for="item in items" :key="item.id" class="t-card" :class="{ off: !item.is_active }">
        <div class="t-img">
          <img :src="item.image_url" :alt="item.title || `تحول #${item.id}`" loading="lazy" />
          <span class="t-sort num">#{{ item.sort }}</span>
          <span v-if="!item.is_active" class="t-off">مخفي</span>
        </div>
        <div class="t-body">
          <div v-if="item.title || item.age" class="t-meta">
            <b v-if="item.title">{{ item.title }}</b>
            <span v-if="item.age" class="t-age"><span class="num">{{ item.age }}</span> سنة</span>
          </div>
          <small v-if="item.subtitle" class="muted">{{ item.subtitle }}</small>
          <small v-if="!item.title && !item.age && !item.subtitle" class="muted">بدون نص</small>
          <div class="t-acts">
            <button type="button" class="btn btn-ghost btn-sm" :disabled="busy[item.id]" @click="startEdit(item)">تعديل</button>
            <button type="button" class="btn btn-ghost btn-sm" :disabled="busy[item.id]" @click="toggleActive(item)">
              {{ item.is_active ? 'إخفاء' : 'إظهار' }}
            </button>
            <button type="button" class="btn btn-ghost btn-sm danger" :disabled="busy[item.id]" @click="removeItem(item)">حذف</button>
          </div>
        </div>
      </article>
    </div>

    <!-- EDIT MODAL -->
    <div v-if="editing" class="modal" @click.self="editing = null">
      <form class="modal-card" @submit.prevent="saveEdit">
        <header class="m-head">
          <h2>تعديل التحول</h2>
          <button type="button" class="x" aria-label="إغلاق" @click="editing = null">✕</button>
        </header>
        <img :src="editing.image_url" alt="" class="preview" />
        <label class="field">
          <span>تغيير الصورة (اختياري)</span>
          <input type="file" accept="image/png,image/jpeg,image/webp" class="input" @change="(e) => (editing.newFile = e.target.files?.[0] || null)" />
        </label>
        <div class="row3">
          <label class="field">
            <span>الاسم</span>
            <input v-model="editing.title" class="input" maxlength="80" />
          </label>
          <label class="field">
            <span>السن</span>
            <input v-model="editing.age" type="number" min="10" max="99" class="input ltr" />
          </label>
          <label class="field">
            <span>الترتيب</span>
            <input v-model="editing.sort" type="number" min="0" class="input ltr" />
          </label>
        </div>
        <label class="field">
          <span>تفصيل / نتيجة</span>
          <input v-model="editing.subtitle" class="input" maxlength="120" />
        </label>
        <label class="check"><input v-model="editing.is_active" type="checkbox" /> ظاهر في الموقع</label>
        <div class="m-acts">
          <button class="btn btn-primary btn-sm" :disabled="busy[editing.id]">{{ busy[editing.id] ? 'بنحفظ…' : 'احفظ' }}</button>
          <button type="button" class="btn btn-ghost btn-sm" @click="editing = null">إلغاء</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.adm { padding-block: 36px 80px; display: grid; gap: 18px; }
.top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.card { background: #141414; border: 1px solid #222; border-radius: 14px; padding: 16px; display: grid; gap: 12px; }
.add-box h2 { font-size: 1.25rem; }
.add-box .btn { justify-self: start; }
.row2, .row3 { display: grid; gap: 10px; }
@media (min-width: 600px) { .row2 { grid-template-columns: 1fr 1fr; } .row3 { grid-template-columns: 1.4fr .8fr 1fr; } }
.field { display: grid; gap: 5px; }
.field span { font-size: .85rem; color: var(--muted); font-weight: 700; }
.check { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: .92rem; cursor: pointer; }
.check input { width: 18px; height: 18px; accent-color: var(--orange); }
.self-end { align-self: end; padding-bottom: 10px; }

.grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
@media (min-width: 700px) { .grid { grid-template-columns: repeat(4, 1fr); } }
@media (min-width: 1050px) { .grid { grid-template-columns: repeat(5, 1fr); } }

.t-card { background: #141414; border: 1px solid #222; border-radius: 14px; overflow: hidden; display: grid; align-content: start; }
.t-card.off { opacity: .55; }
.t-img { position: relative; aspect-ratio: 4 / 5; background: #0a0a0a; }
.t-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.t-sort { position: absolute; top: 8px; inset-inline-start: 8px; background: rgba(10, 10, 10, .85); color: var(--amber); padding: 2px 8px; border-radius: 999px; font-size: .8rem; border: 1px solid #333; }
.t-off { position: absolute; top: 8px; inset-inline-end: 8px; background: #2a1818; color: #ff8a8a; padding: 2px 8px; border-radius: 999px; font-size: .75rem; font-weight: 800; }
.t-body { padding: 10px; display: grid; gap: 6px; }
.t-meta { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.t-body b { font-size: .9rem; }
.t-age { font-size: .74rem; font-weight: 800; color: var(--amber); background: #221810; padding: 2px 7px; border-radius: 999px; white-space: nowrap; }
.t-acts { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
.t-acts .btn { padding: 5px 9px; font-size: .78rem; }
.danger { color: #ff7a7a; border-color: #3a2020; }

.modal { position: fixed; inset: 0; z-index: 300; background: rgba(0, 0, 0, .75); display: grid; place-items: center; padding: 16px; }
.modal-card { width: 100%; max-width: 440px; max-height: 92svh; overflow: auto; background: #141414; border: 1px solid #2a2a2a; border-radius: 16px; padding: 16px; display: grid; gap: 12px; }
.m-head { display: flex; align-items: center; justify-content: space-between; }
.m-head h2 { font-size: 1.25rem; }
.x { width: 32px; height: 32px; border-radius: 50%; background: #222; color: #fff; display: grid; place-items: center; }
.preview { width: 120px; aspect-ratio: 4 / 5; object-fit: cover; border-radius: 10px; border: 1px solid #2a2a2a; justify-self: center; }
.m-acts { display: flex; gap: 8px; }

.loading { display: grid; place-items: center; padding: 48px; }
.spin { width: 28px; height: 28px; border: 3px solid #262626; border-top-color: var(--orange); border-radius: 50%; animation: sp .7s linear infinite; }
@keyframes sp { to { transform: rotate(360deg); } }
</style>
