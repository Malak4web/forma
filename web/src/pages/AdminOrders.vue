<script setup>
import { ref, reactive, watch } from 'vue'
import { api } from '../api.js'
import AdminNav from '../components/AdminNav.vue'
import { egp, methodById } from '../data.js'
import { toast } from '../stores/app.js'

const tabs = [
  { id: 'pending', label: 'بيتراجع' },
  { id: 'paid', label: 'مدفوع' },
  { id: 'rejected', label: 'متقبلش' },
  { id: 'all', label: 'الكل' },
]
const status = ref('pending')
const orders = ref([])
const loading = ref(false)
const busy = reactive({})
const notes = reactive({})
const open = ref(null) // receipt viewer

async function load() {
  loading.value = true
  try { orders.value = (await api(`/admin/orders?status=${status.value}`)).data }
  catch (e) { toast(e.message, 'err') }
  finally { loading.value = false }
}
watch(status, load, { immediate: true })

async function act(o, action) {
  if (action === 'reject' && !notes[o.id]?.trim()) return toast('اكتب سبب الرفض عشان يوصل للعميل', 'err')
  busy[o.id] = true
  try {
    await api(`/admin/orders/${o.id}/${action}`, { method: 'POST', body: { note: notes[o.id] || null } })
    toast(action === 'approve' ? `اتفعّل اشتراك ${o.user.name} ✓` : 'اترفض')
    orders.value = orders.value.filter((x) => x.id !== o.id || status.value === 'all')
    if (status.value === 'all') load()
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    busy[o.id] = false
  }
}
const when = (iso) => new Date(iso).toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' })
const wa = (phone) => `https://wa.me/2${phone}`
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <h1>الطلبات</h1>
      <button class="btn btn-ghost btn-sm" :disabled="loading" @click="load">تحديث</button>
    </header>
    <div class="filters no-scrollbar" role="tablist">
      <button v-for="t in tabs" :key="t.id" role="tab" :aria-selected="status === t.id" :class="{ on: status === t.id }" @click="status = t.id"><span>{{ t.label }}</span></button>
    </div>

    <p v-if="!loading && !orders.length" class="empty muted">مفيش طلبات هنا.</p>

    <article v-for="o in orders" :key="o.id" class="ord" :class="o.status">
      <button v-if="o.has_receipt" class="thumb" :aria-label="`إيصال ${o.id}`" @click="open = o">
        <img :src="`/api/admin/orders/${o.id}/receipt`" alt="" loading="lazy" />
      </button>
      <div class="info">
        <div class="l1">
          <b class="en ref">{{ o.id }}</b>
          <span class="st">{{ tabs.find((t) => t.id === o.status)?.label || o.status }}</span>
        </div>
        <p class="who">{{ o.user.name }} · <a :href="wa(o.user.phone)" target="_blank" rel="noopener" class="en">{{ o.user.phone }}</a></p>
        <p>{{ o.plan_name }} · <b class="num amber">{{ egp(o.amount) }}</b> ج.م · {{ methodById(o.method)?.label || o.method }}</p>
        <p v-if="o.payer_phone" class="muted">حوّل من: <span class="en">{{ o.payer_phone }}</span></p>
        <p class="muted small">{{ when(o.created_at) }}</p>
        <p v-if="o.admin_note" class="muted small">ملاحظة: {{ o.admin_note }}</p>

        <div v-if="o.status === 'pending'" class="acts">
          <input v-model="notes[o.id]" class="input" placeholder="ملاحظة / سبب الرفض" />
          <div class="btns">
            <button class="btn btn-primary btn-sm" :disabled="busy[o.id]" @click="act(o, 'approve')">فعّل الاشتراك</button>
            <button class="btn btn-ghost btn-sm" :disabled="busy[o.id]" @click="act(o, 'reject')">ارفض</button>
          </div>
        </div>
      </div>
    </article>

    <div v-if="open" class="viewer" role="dialog" aria-modal="true" @click.self="open = null">
      <img :src="`/api/admin/orders/${open.id}/receipt`" alt="الإيصال" />
      <button class="x" aria-label="قفل" @click="open = null">✕</button>
    </div>
  </div>
</template>

<style scoped>
.adm { padding-block: 24px 60px; display: grid; gap: 14px; max-width: 900px; }
.top { display: flex; justify-content: space-between; align-items: center; }
.top h1 { font-size: 2.4rem; }
.empty { padding: 30px 0; }
.ord { display: grid; grid-template-columns: 96px 1fr; gap: 14px; padding: 12px; border-radius: 16px; background: #121212; border: 1px solid #242424; }
.ord.pending { border-color: rgba(255,176,0,.4); }
.thumb { width: 96px; height: 130px; border-radius: 10px; overflow: hidden; background: #000; }
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.info { display: grid; gap: 2px; align-content: start; min-width: 0; }
.l1 { display: flex; justify-content: space-between; gap: 8px; }
.ref { font-size: 1.4rem; }
.st { font-size: .78rem; font-weight: 800; color: var(--muted); }
.pending .st { color: var(--amber); }
.paid .st { color: var(--ok); }
.rejected .st { color: var(--danger); }
.who a { color: var(--orange); }
.small { font-size: .8rem; }
.acts { display: grid; gap: 8px; margin-top: 8px; }
.btns { display: flex; gap: 8px; flex-wrap: wrap; }
.viewer { position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,.9); display: grid; place-items: center; padding: 16px; }
.viewer img { max-width: 100%; max-height: 90vh; border-radius: 10px; }
.x { position: absolute; top: 16px; inset-inline-start: 16px; width: 44px; height: 44px; border-radius: 50%; background: #222; }
</style>
