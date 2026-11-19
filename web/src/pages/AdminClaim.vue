<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, fieldErrors } from '../api.js'
import { store, toast, loadMe } from '../stores/app.js'

const route = useRoute()
const router = useRouter()
const token = ref(typeof route.query.t === 'string' ? route.query.t : '')
const err = ref('')
const busy = ref(false)

async function claim() {
  err.value = ''
  if (!token.value.trim()) return (err.value = 'اكتب كود التفعيل')
  busy.value = true
  try {
    await api('/admin/claim', { method: 'POST', body: { token: token.value.trim() } })
    await loadMe()
    toast('بقيت أدمن ✓')
    router.replace('/admin/orders')
  } catch (e) {
    err.value = fieldErrors(e).token || e.message
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="wrap claim">
    <h1>تفعيل <span class="hl">أول أدمن</span></h1>
    <p class="muted">بتتعمل مرة واحدة بس. الحساب اللي داخل بيه دلوقتي (<b>{{ store.user?.name }}</b> · <span class="en">{{ store.user?.phone }}</span>) هو اللي هيبقى أدمن.</p>
    <form class="f" @submit.prevent="claim">
      <label class="field">
        <span>كود التفعيل</span>
        <input v-model="token" class="input ltr" autocomplete="off" spellcheck="false" />
        <small v-if="err" class="err">{{ err }}</small>
      </label>
      <button class="btn btn-primary" :disabled="busy">{{ busy ? 'ثانية…' : 'فعّلني كأدمن' }}</button>
    </form>
  </section>
</template>

<style scoped>
.claim { padding-block: 32px 60px; max-width: 520px; display: grid; gap: 14px; }
.claim h1 { font-size: clamp(2rem, 7vw, 2.8rem); }
.f { display: grid; gap: 12px; }
.err { color: var(--danger); font-weight: 800; font-size: .85rem; }
.input.ltr { font-family: var(--f-body); font-size: 1rem; letter-spacing: 0; }
</style>
