<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MAppBar from './MAppBar.vue'
import MSeg from './MSeg.vue'
import { register, login, toast, store } from '../stores/app.js'
import { fieldErrors } from '../api.js'

const route = useRoute()
const router = useRouter()
const mode = ref(route.path === '/login' ? 'login' : 'register')
watch(mode, (m) => router.replace({ path: m === 'login' ? '/login' : '/register', query: route.query }))
const next = computed(() => (typeof route.query.next === 'string' && route.query.next.startsWith('/') ? route.query.next : '/'))
const toCheckout = computed(() => next.value.startsWith('/checkout'))

const f = reactive({ name: store.calc?.leadName || '', phone: store.calc?.leadPhone || '', password: '', terms: true })
const err = reactive({})
const show = ref(false)
const busy = ref(false)
const clean = (p) => p.replace(/\s/g, '')

async function submit() {
  Object.keys(err).forEach((k) => (err[k] = ''))
  if (mode.value === 'register' && f.name.trim().length < 2) err.name = 'اكتب اسمك'
  if (!/^01[0125]\d{8}$/.test(clean(f.phone))) err.phone = 'رقم موبايل مصري صح (11 رقم)'
  if (mode.value === 'register' ? f.password.length < 8 : !f.password) err.password = mode.value === 'register' ? 'الباسورد 8 حروف على الأقل' : 'اكتب الباسورد'
  if (mode.value === 'register' && !f.terms) err.terms = 'لازم توافق على الشروط'
  if (Object.values(err).some(Boolean)) return
  busy.value = true
  try {
    if (mode.value === 'register') {
      await register({ name: f.name.trim(), phone: clean(f.phone), email: null, password: f.password, goal: store.calc?.input?.goal || null, terms: f.terms })
      toast(`أهلًا يا ${f.name.trim()} 👊`)
    } else {
      await login({ phone: clean(f.phone), password: f.password })
      toast('أهلًا بيك تاني 👊')
    }
    router.replace(next.value)
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
  <div class="auth">
    <div class="art">
      <img src="/img/coach-arms-640.webp" alt="" />
      <MAppBar transparent close fallback="/" />
      <p v-if="toCheckout" class="step">خطوة 1 من 2 · الحساب</p>
    </div>
    <form class="sheet" novalidate @submit.prevent="submit">
      <MSeg v-model="mode" :options="[{ id: 'register', label: 'حساب جديد' }, { id: 'login', label: 'دخول' }]" />
      <h1>{{ mode === 'register' ? 'يلا نبدأ 👊' : 'أهلًا بيك تاني' }}</h1>

      <label v-if="mode === 'register'" class="fld">
        <span class="m-label">الاسم</span>
        <input v-model="f.name" class="m-input" :class="{ invalid: err.name }" autocomplete="name" />
        <small v-if="err.name" class="m-err">{{ err.name }}</small>
      </label>
      <label class="fld">
        <span class="m-label">رقم الموبايل (واتساب)</span>
        <input v-model="f.phone" class="m-input ltr" :class="{ invalid: err.phone }" type="tel" inputmode="tel" placeholder="01xxxxxxxxx" autocomplete="tel" />
        <small v-if="err.phone" class="m-err">{{ err.phone }} <button v-if="err.phone.includes('ادخل')" type="button" class="lnk" @click="mode = 'login'">ادخل ←</button></small>
      </label>
      <label class="fld">
        <span class="m-label">الباسورد</span>
        <span class="pw">
          <input v-model="f.password" class="m-input ltr" :class="{ invalid: err.password }" :type="show ? 'text' : 'password'" :autocomplete="mode === 'register' ? 'new-password' : 'current-password'" />
          <button type="button" class="eye" @click="show = !show">{{ show ? 'اخفي' : 'اظهر' }}</button>
        </span>
        <small v-if="err.password" class="m-err">{{ err.password }}</small>
      </label>
      <label v-if="mode === 'register'" class="terms">
        <input v-model="f.terms" type="checkbox" />
        <span>موافق على الشروط وسياسة الخصوصية</span>
      </label>
      <small v-if="err.terms" class="m-err">{{ err.terms }}</small>
      <button v-else-if="mode === 'login'" type="button" class="lnk forgot" @click="toast('كلّمنا على الواتساب ونرجّعلك حسابك')">نسيت الباسورد؟</button>

      <div class="m-cta in">
        <button class="btn btn-primary" :disabled="busy">{{ busy ? 'ثانية…' : mode === 'login' ? 'دخول' : toCheckout ? 'كمّل للدفع' : 'اعمل الحساب' }}</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.auth { min-height: 100dvh; display: flex; flex-direction: column; background: #121212; }
.art { position: relative; height: 32dvh; min-height: 210px; flex: none; background: #0a0a0a; }
.art > img { width: 100%; height: 100%; object-fit: cover; object-position: center 20%; }
.art::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, #0a0a0a 5%, transparent 60%); pointer-events: none; }
.step { position: absolute; z-index: 1; bottom: 36px; inset-inline-start: 16px; font-size: .78rem; font-weight: 800; color: var(--amber); background: rgba(0,0,0,.6); border: 1px solid rgba(255,176,0,.4); padding: 3px 10px; border-radius: 99px; }
.sheet { flex: 1; margin-top: -26px; position: relative; z-index: 2; background: #121212; border-radius: 28px 28px 0 0; border-top: 1px solid #262626; padding: 18px 16px var(--m-cta); display: grid; gap: 12px; align-content: start; }
.sheet h1 { font-size: 1.9rem; }
.fld { display: grid; gap: 6px; }
.pw { position: relative; display: block; }
.pw .m-input { padding-inline-end: 70px; }
.eye { position: absolute; inset-inline-end: 8px; top: 50%; translate: 0 -50%; font-size: .8rem; font-weight: 800; color: var(--muted); padding: 6px; }
.terms { display: flex; gap: 10px; align-items: center; font-size: .88rem; }
.terms input { width: 20px; height: 20px; accent-color: var(--orange); flex: none; }
.lnk { color: var(--orange); font-weight: 800; font-size: .85rem; }
.forgot { justify-self: start; }
.m-cta.in { background: linear-gradient(to top, #121212 72%, rgba(18,18,18,0)); }
</style>
