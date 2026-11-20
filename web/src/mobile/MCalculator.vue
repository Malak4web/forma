<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import MAppBar from './MAppBar.vue'
import MSeg from './MSeg.vue'
import DietPicker from '../components/DietPicker.vue'
import { dietSplit } from '../diets/data.js'
import { goals, activities, calcCalories } from '../nutrition.js'
import { store, saveLead, toast } from '../stores/app.js'
import { fieldErrors } from '../api.js'

const router = useRouter()
const step = ref(1)
const STEPS = 5
const f = reactive({ sex: 'm', age: 28, weight: 85, height: 175, act: 1.375, goal: 'loss', diet: 'balanced', ...(store.calc?.input || {}) })
const res = computed(() => calcCalories(f, dietSplit(f.diet)))
watch([() => ({ ...f }), res], ([input, r]) => { store.calc = { ...store.calc, input, ...r, weight: input.weight } }, { deep: true })

const lead = reactive({ name: store.user?.name || store.calc?.leadName || '', phone: store.user?.phone || store.calc?.leadPhone || '' })
const err = reactive({})
const busy = ref(false)

const goalIcons = { loss: '🔥', cut: '⚡', gain: '💪' }
const goalHints = { loss: 'تنزل وزن وتخسّ الكرش', cut: 'تحرق دهون وتحافظ على العضل', gain: 'تكبّر عضلك وتقوى' }
const actIcons = ['🛋️', '🚶', '🏃', '🔥']
const steppers = [
  { k: 'weight', label: 'الوزن', unit: 'كجم', min: 35, max: 250, step: 1 },
  { k: 'height', label: 'الطول', unit: 'سم', min: 120, max: 220, step: 1 },
  { k: 'age', label: 'السن', unit: 'سنة', min: 14, max: 80, step: 1 },
]
const bump = (s, d) => (f[s.k] = Math.min(s.max, Math.max(s.min, (Number(f[s.k]) || s.min) + d * s.step)))
let hold
const press = (s, d) => { bump(s, d); hold = setTimeout(function rep() { bump(s, d); hold = setTimeout(rep, 70) }, 380) }
const release = () => clearTimeout(hold)

const titles = ['هدفك إيه؟', 'جسمك عامل إزاي دلوقتي؟', 'بتتحرك قد إيه في اليوم؟', 'عايز تمشي على أنهي نظام؟', 'نبعتلك النظام فين؟']
function next() {
  if (step.value < STEPS) return step.value++
  submit()
}
async function submit() {
  err.name = lead.name.trim().length < 2 ? 'اكتب اسمك' : ''
  err.phone = /^01[0125]\d{8}$/.test(lead.phone.replace(/\s/g, '')) ? '' : 'رقم موبايل مصري صح (11 رقم يبدأ بـ 01)'
  if (err.name || err.phone) return
  busy.value = true
  try {
    const c = { ...res.value }
    store.calc = { input: { ...f }, ...c, weight: f.weight }
    await saveLead({
      name: lead.name.trim(), phone: lead.phone.replace(/\s/g, ''),
      goal: f.goal, diet: f.diet, sex: f.sex, age: f.age, weight: f.weight, height: f.height, activity: f.act, ...c,
    })
    toast('تمام! نظامك جاهز')
    router.push('/free-plan')
  } catch (e) {
    const fe = fieldErrors(e)
    Object.assign(err, fe)
    if (!Object.keys(fe).length) toast(e.message, 'err')
  } finally {
    busy.value = false
  }
}
function back() { step.value > 1 ? step.value-- : router.back() }
</script>

<template>
  <div class="m-screen with-cta calc">
    <MAppBar :back="false" title="احسب سعراتك">
      <template #start>
        <button class="mib" :aria-label="step > 1 ? 'الخطوة اللي فاتت' : 'رجوع'" @click="back"><svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" /></svg></button>
      </template>
      <span class="num muted cnt">{{ step }}/{{ STEPS }}</span>
    </MAppBar>
    <div class="bar m-pad"><i v-for="i in STEPS" :key="i" :class="{ on: i <= step }"></i></div>

    <Transition name="slide" mode="out-in">
      <section :key="step" class="m-pad body">
        <h1>{{ titles[step - 1] }}</h1>

        <!-- 1. goal -->
        <div v-if="step === 1" class="opts">
          <button v-for="g in goals" :key="g.id" class="opt" :class="{ on: f.goal === g.id }" @click="f.goal = g.id">
            <span class="ic">{{ goalIcons[g.id] }}</span>
            <span class="x"><b>{{ g.label }}</b><small>{{ goalHints[g.id] }}</small></span>
            <span class="radio"></span>
          </button>
        </div>

        <!-- 2. body -->
        <div v-else-if="step === 2" class="m-stack">
          <MSeg v-model="f.sex" :options="[{ id: 'm', label: 'راجل' }, { id: 'f', label: 'ست' }]" />
          <div v-for="s in steppers" :key="s.k" class="m-card stp">
            <span class="lbl"><b>{{ s.label }}</b><small>{{ s.unit }}</small></span>
            <div class="ctl" dir="ltr">
              <button class="mib" :aria-label="`قلّل ${s.label}`" @pointerdown="press(s, -1)" @pointerup="release" @pointerleave="release">−</button>
              <input v-model.number="f[s.k]" class="num val" type="number" inputmode="numeric" :min="s.min" :max="s.max" :aria-label="s.label" />
              <button class="mib" :aria-label="`زوّد ${s.label}`" @pointerdown="press(s, 1)" @pointerup="release" @pointerleave="release">+</button>
            </div>
          </div>
        </div>

        <!-- 3. activity -->
        <div v-else-if="step === 3" class="opts">
          <button v-for="(a, i) in activities" :key="a.v" class="opt" :class="{ on: f.act === a.v }" @click="f.act = a.v">
            <span class="ic">{{ actIcons[i] }}</span>
            <span class="x"><b>{{ a.label }}</b></span>
            <span class="radio"></span>
          </button>
        </div>

        <!-- 4. diet -->
        <div v-else-if="step === 4" class="m-stack">
          <p class="muted sub">هنفصّلك النظام ده على سعراتك بالظبط. مش متأكد؟ سيبها على «فورمة المتوازن».</p>
          <DietPicker v-model="f.diet" :goal="f.goal" />
        </div>

        <!-- 5. result preview + lead -->
        <div v-else class="m-stack">
          <div class="peek">
            <small class="amber">احتياجك تقريبًا</small>
            <div><span class="num big">{{ res?.kcal ?? '—' }}</span><small class="muted">سعرة/يوم</small></div>
          </div>
          <label class="m-stack fld">
            <span class="m-label">اسمك</span>
            <input v-model="lead.name" class="m-input" :class="{ invalid: err.name }" autocomplete="given-name" />
            <small v-if="err.name" class="m-err">{{ err.name }}</small>
          </label>
          <label class="m-stack fld">
            <span class="m-label">رقم الواتساب</span>
            <input v-model="lead.phone" class="m-input ltr" :class="{ invalid: err.phone }" type="tel" inputmode="tel" placeholder="01xxxxxxxxx" autocomplete="tel" />
            <small v-if="err.phone" class="m-err">{{ err.phone }}</small>
          </label>
          <small class="muted">مش هنبعتلك سبام. رقمك للنظام والمتابعة بس.</small>
        </div>

        <p class="muted disc">الأرقام تقريبية — مش حسابات دكتور.</p>
      </section>
    </Transition>

    <div class="m-cta">
      <button class="btn btn-primary" :disabled="busy" @click="next">{{ step < STEPS ? 'التالي ←' : busy ? 'ثانية…' : 'وريني نظامي' }}</button>
    </div>
  </div>
</template>

<style scoped>
.cnt { font-size: 1.15rem; }
.bar { display: flex; gap: 5px; }
.bar i { flex: 1; height: 5px; border-radius: 9px; background: #262626; transition: background .2s; }
.bar i.on { background: var(--orange); }
.body { padding-top: 24px; display: grid; gap: 16px; align-content: start; }
.body h1 { font-size: 2.1rem; line-height: 1.15; }
.opts { display: grid; gap: 10px; }
.opt { display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 18px; background: var(--surface); border: 1.5px solid #262626; text-align: start; transition: border-color .15s, background .15s; }
.opt.on { border-color: var(--orange); background: rgba(255,106,0,.08); }
.opt .ic { width: 46px; height: 46px; border-radius: 14px; background: #1f1f1f; display: grid; place-items: center; font-size: 1.4rem; flex: none; }
.opt .x { flex: 1; display: grid; }
.opt .x b { font-size: 1.05rem; }
.opt .x small { color: var(--muted); font-size: .8rem; }
.radio { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #444; flex: none; }
.opt.on .radio { border: 7px solid var(--orange); }
.stp { padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; }
.lbl { display: grid; }
.lbl small { color: var(--muted); font-size: .75rem; }
.ctl { display: flex; align-items: center; gap: 10px; }
.ctl .mib { font-size: 1.4rem; font-weight: 800; touch-action: manipulation; user-select: none; }
.val { width: 74px; background: none; border: 0; color: #fff; text-align: center; font-size: 2.3rem; padding: 0; -moz-appearance: textfield; }
.val::-webkit-inner-spin-button { -webkit-appearance: none; }
.val:focus { outline: none; color: var(--orange); }
.peek { padding: 16px; border-radius: 20px; border: 2px solid var(--orange); box-shadow: 5px 5px 0 var(--orange); background: #0f0f0f; display: grid; }
.peek small.amber { font-weight: 800; font-size: .8rem; }
.peek div { display: flex; align-items: baseline; gap: 8px; }
.big { font-size: 3.6rem; line-height: .9; }
.fld { gap: 6px; }
.sub { font-size: .85rem; margin-top: -8px; }
.disc { text-align: center; font-size: .78rem; }
.slide-enter-active, .slide-leave-active { transition: opacity .15s; }
.slide-enter-from, .slide-leave-to { opacity: 0; }
</style>
