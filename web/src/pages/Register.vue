<script setup>
import { reactive, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthShell from '../components/AuthShell.vue'
import { register, toast, store } from '../stores/app.js'
import { fieldErrors } from '../api.js'
import { goals } from '../nutrition.js'

const route = useRoute()
const router = useRouter()
const next = computed(() => (typeof route.query.next === 'string' && route.query.next.startsWith('/') ? route.query.next : '/account'))
const toCheckout = computed(() => next.value.startsWith('/checkout'))

const f = reactive({ name: store.calc?.leadName || '', phone: store.calc?.leadPhone || '', email: '', password: '', goal: store.calc?.input?.goal || '', terms: false })
const err = reactive({})
const show = ref(false)
const busy = ref(false)

function validate() {
  err.name = f.name.trim().length < 2 ? 'اكتب اسمك' : ''
  err.phone = /^01[0125]\d{8}$/.test(f.phone.replace(/\s/g, '')) ? '' : 'رقم موبايل مصري صح (11 رقم)'
  err.email = f.email && !/^\S+@\S+\.\S+$/.test(f.email) ? 'الإيميل مش مظبوط' : ''
  err.password = f.password.length < 8 ? 'الباسورد 8 حروف على الأقل' : ''
  err.terms = f.terms ? '' : 'لازم توافق على الشروط'
  return !Object.values(err).some(Boolean)
}
async function submit() {
  if (!validate()) return
  busy.value = true
  try {
    await register({ name: f.name.trim(), phone: f.phone.replace(/\s/g, ''), email: f.email || null, password: f.password, goal: f.goal || null, terms: f.terms })
    toast(`أهلًا يا ${f.name.trim()} 👊`)
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
  <AuthShell :title="toCheckout ? 'اعمل حسابك الأول' : 'حساب جديد'" :sub="toCheckout ? 'عشان اشتراكك وتقدّمك يتحفظوا باسمك.' : 'احفظ تقدّمك، وزنك، والبرامج اللي معاك.'" :step="toCheckout ? 'خطوة 1 من 2' : ''">
    <form class="form" novalidate @submit.prevent="submit">
      <label class="field"><span>الاسم</span>
        <input v-model="f.name" class="input" :class="{ invalid: err.name }" autocomplete="name" />
        <small v-if="err.name" class="err">{{ err.name }}</small>
      </label>
      <label class="field"><span>رقم الموبايل (واتساب)</span>
        <input v-model="f.phone" class="input ltr" :class="{ invalid: err.phone }" type="tel" inputmode="tel" placeholder="01xxxxxxxxx" autocomplete="tel" />
        <small v-if="err.phone" class="err">{{ err.phone }} <RouterLink v-if="err.phone.includes('ادخل')" :to="{ path: '/login', query: route.query }">ادخل ←</RouterLink></small>
      </label>
      <label class="field"><span>الإيميل <small class="muted">(اختياري)</small></span>
        <input v-model="f.email" class="input ltr" :class="{ invalid: err.email }" type="email" autocomplete="email" />
        <small v-if="err.email" class="err">{{ err.email }}</small>
      </label>
      <label class="field"><span>الباسورد</span>
        <span class="pw">
          <input v-model="f.password" class="input ltr" :class="{ invalid: err.password }" :type="show ? 'text' : 'password'" autocomplete="new-password" />
          <button type="button" class="eye" :aria-label="show ? 'اخفي الباسورد' : 'اظهر الباسورد'" @click="show = !show">{{ show ? 'اخفي' : 'اظهر' }}</button>
        </span>
        <small v-if="err.password" class="err">{{ err.password }}</small>
      </label>
      <fieldset class="goals">
        <legend>هدفك <small class="muted">(اختياري)</small></legend>
        <label v-for="g in goals" :key="g.id" :class="{ on: f.goal === g.id }"><input type="radio" v-model="f.goal" :value="g.id" />{{ g.label }}</label>
      </fieldset>
      <label class="terms">
        <input v-model="f.terms" type="checkbox" />
        <span>موافق على <a href="#" @click.prevent>الشروط</a> و<a href="#" @click.prevent>سياسة الخصوصية</a></span>
      </label>
      <small v-if="err.terms" class="err">{{ err.terms }}</small>
      <button class="btn btn-primary btn-block" :disabled="busy">{{ busy ? 'ثانية…' : toCheckout ? 'كمّل للدفع' : 'اعمل الحساب' }}</button>
      <p class="alt muted">عندك حساب؟ <RouterLink :to="{ path: '/login', query: route.query }">ادخل</RouterLink></p>
    </form>
  </AuthShell>
</template>

<style scoped>
.form { display: grid; gap: 14px; margin-top: 6px; }
.pw { position: relative; display: block; }
.pw .input { padding-inline-end: 70px; }
.eye { position: absolute; inset-inline-end: 8px; top: 50%; translate: 0 -50%; font-size: .8rem; font-weight: 800; color: var(--muted); padding: 6px; }
.goals { border: 0; padding: 0; margin: 0; display: flex; gap: 6px; flex-wrap: wrap; }
.goals legend { font-size: .85rem; font-weight: 800; color: rgba(255,255,255,.8); margin-bottom: 6px; padding: 0; }
.goals label { padding: 7px 14px; border-radius: 99px; border: 1.5px solid #333; font-weight: 800; font-size: .9rem; cursor: pointer; }
.goals label.on { border-color: var(--orange); color: var(--orange); }
.goals label:focus-within { outline: 3px solid var(--amber); }
.goals input { position: absolute; opacity: 0; pointer-events: none; }
.terms { display: flex; gap: 10px; align-items: flex-start; font-size: .9rem; }
.terms input { width: 20px; height: 20px; accent-color: var(--orange); margin-top: 3px; flex: none; }
.terms a, .alt a, .err a { color: var(--orange); font-weight: 800; }
.err { color: var(--danger); font-weight: 700; font-size: .85rem; }
.alt { text-align: center; font-size: .92rem; }
</style>
