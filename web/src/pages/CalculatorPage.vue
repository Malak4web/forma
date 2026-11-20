<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import Calculator from '../components/Calculator.vue'
import DietPicker from '../components/DietPicker.vue'
import { store, saveLead, toast } from '../stores/app.js'
import { fieldErrors } from '../api.js'

const router = useRouter()
const lead = reactive({ name: store.user?.name || '', phone: store.user?.phone || '' })
const err = reactive({ name: '', phone: '' })
const sending = ref(false)
const EG_PHONE = /^01[0125]\d{8}$/

function submit() {
  err.name = lead.name.trim().length < 2 ? 'اكتب اسمك' : ''
  err.phone = EG_PHONE.test(lead.phone.replace(/\s/g, '')) ? '' : 'رقم موبايل مصري صح (11 رقم يبدأ بـ 01)'
  if (err.name || err.phone || !store.calc?.kcal) return
  sending.value = true
  const c = store.calc
  saveLead({
    name: lead.name.trim(), phone: lead.phone.replace(/\s/g, ''),
    goal: c.input.goal, diet: c.input.diet || 'balanced', sex: c.input.sex, age: c.input.age, weight: c.input.weight, height: c.input.height,
    activity: c.input.act, kcal: c.kcal, protein: c.protein, carbs: c.carbs, fat: c.fat,
  })
    .then(() => { toast('تمام! نظامك جاهز'); router.push('/free-plan') })
    .catch((e) => { const fe = fieldErrors(e); Object.assign(err, fe); if (!Object.keys(fe).length) toast(e.message, 'err') })
    .finally(() => (sending.value = false))
}
</script>

<template>
  <div>
    <header class="page-head">
      <div class="wrap">
        <nav class="crumbs"><RouterLink to="/">الرئيسية</RouterLink><span>/</span><span>الحاسبة</span></nav>
        <h1>احسب سعراتك<br /><span class="hl">وخد نظامك ببلاش</span></h1>
        <p>6 أسئلة في دقيقة، واختار النظام اللي عايز تمشي عليه (كيتو، صيام متقطع، الطيبات…). الرقم بيتحسب قدامك، واكتب اسمك ورقمك وخد نظامك مفصّل على قدّك.</p>
      </div>
    </header>

    <section class="wrap body">
      <Calculator v-slot="{ input }">
        <div class="pick">
          <b class="lead-t">اختار نظامك الغذائي</b>
          <small class="muted">هنفصّلهولك على سعراتك — ولو مش متأكد سيبها على «فورمة المتوازن».</small>
          <DietPicker v-model="input.diet" :goal="input.goal" compact />
        </div>
        <form class="lead" novalidate @submit.prevent="submit">
          <b class="lead-t">ابعتلي النظام كامل</b>
          <label class="field">
            <span>اسمك</span>
            <input v-model="lead.name" class="input" :class="{ invalid: err.name }" autocomplete="given-name" />
            <small v-if="err.name" class="err">{{ err.name }}</small>
          </label>
          <label class="field">
            <span>رقم الواتساب</span>
            <input v-model="lead.phone" class="input ltr" :class="{ invalid: err.phone }" type="tel" inputmode="tel" placeholder="01xxxxxxxxx" autocomplete="tel" />
            <small v-if="err.phone" class="err">{{ err.phone }}</small>
          </label>
          <button class="btn btn-primary btn-block" :disabled="sending">{{ sending ? 'ثانية…' : 'وريني نظامي' }}</button>
          <small class="muted">مش هنبعتلك سبام. رقمك للنظام والمتابعة بس.</small>
        </form>
      </Calculator>
    </section>
  </div>
</template>

<style scoped>
.body { padding-top: 28px; }
.pick { display: grid; gap: 8px; position: relative; }
.lead { display: grid; gap: 12px; margin-top: 6px; position: relative; }
.lead-t { font-family: var(--f-head); font-weight: 400; font-size: 1.35rem; }
</style>
