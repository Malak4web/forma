<script setup>
import { reactive, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthShell from '../components/AuthShell.vue'
import { login, toast } from '../stores/app.js'
import { fieldErrors } from '../api.js'

const route = useRoute()
const router = useRouter()
const next = computed(() => (typeof route.query.next === 'string' && route.query.next.startsWith('/') ? route.query.next : '/account'))
const f = reactive({ phone: '', password: '' })
const err = reactive({})
const show = ref(false)
const busy = ref(false)

async function submit() {
  err.phone = /^01[0125]\d{8}$/.test(f.phone.replace(/\s/g, '')) ? '' : 'رقم موبايل مصري صح (11 رقم)'
  err.password = f.password ? '' : 'اكتب الباسورد'
  if (err.phone || err.password) return
  busy.value = true
  try {
    await login({ phone: f.phone.replace(/\s/g, ''), password: f.password })
    toast('أهلًا بيك تاني 👊')
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
  <AuthShell title="ادخل حسابك" sub="كمّل من المكان اللي وقفت عنده.">
    <form class="form" novalidate @submit.prevent="submit">
      <label class="field"><span>رقم الموبايل</span>
        <input v-model="f.phone" class="input ltr" :class="{ invalid: err.phone }" type="tel" inputmode="tel" placeholder="01xxxxxxxxx" autocomplete="tel" />
        <small v-if="err.phone" class="err">{{ err.phone }}</small>
      </label>
      <label class="field"><span>الباسورد</span>
        <span class="pw">
          <input v-model="f.password" class="input ltr" :class="{ invalid: err.password }" :type="show ? 'text' : 'password'" autocomplete="current-password" />
          <button type="button" class="eye" @click="show = !show">{{ show ? 'اخفي' : 'اظهر' }}</button>
        </span>
        <small v-if="err.password" class="err">{{ err.password }}</small>
      </label>
      <a href="#" class="forgot" @click.prevent="toast('كلّمنا على الواتساب ونرجّعلك حسابك')">نسيت الباسورد؟</a>
      <button class="btn btn-primary btn-block" :disabled="busy">{{ busy ? 'ثانية…' : 'دخول' }}</button>
      <p class="alt muted">أول مرة؟ <RouterLink :to="{ path: '/register', query: route.query }">اعمل حساب</RouterLink></p>
    </form>
  </AuthShell>
</template>

<style scoped>
.form { display: grid; gap: 14px; margin-top: 6px; }
.pw { position: relative; display: block; }
.pw .input { padding-inline-end: 70px; }
.eye { position: absolute; inset-inline-end: 8px; top: 50%; translate: 0 -50%; font-size: .8rem; font-weight: 800; color: var(--muted); padding: 6px; }
.forgot { justify-self: start; font-size: .88rem; font-weight: 800; color: var(--muted); }
.forgot:hover { color: var(--orange); }
.err { color: var(--danger); font-weight: 700; font-size: .85rem; }
.alt { text-align: center; font-size: .92rem; }
.alt a { color: var(--orange); font-weight: 800; }
</style>
