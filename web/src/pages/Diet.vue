<script setup>
import { ref, reactive, computed, watch, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import NotFound from './NotFound.vue'
import MAppBar from '../mobile/MAppBar.vue'
import SxIcon from '../components/SxIcon.vue'
import { MOBILE } from '../mobile/device.js'
import { DIETS, dietBySlug, dietDay, CYCLE_LABEL } from '../diets/data.js'
import { store } from '../stores/app.js'

const route = useRoute()
const d = computed(() => dietBySlug(route.params.slug))
watchEffect(() => { if (d.value) document.title = `نظام ${d.value.name} (${d.value.en}) — خطة 30 يوم | فورمة` })

/* ───── 30-day plan: week tabs + 7-day row + selected day ───── */
const week = ref(1)
const day = ref(1)
const KEY = (s) => `forma:diet:${s}`
const saved = reactive({ done: [], shop: [] })
watch(d, (v) => {
  if (!v) return
  let s = {}
  try { s = JSON.parse(localStorage.getItem(KEY(v.slug)) || '{}') } catch { /* private mode */ }
  saved.done = s.done || []
  saved.shop = s.shop || []
  const first = Array.from({ length: 30 }, (_, i) => i + 1).find((n) => !saved.done.includes(n)) || 1
  day.value = first
  week.value = Math.ceil(first / 7)
}, { immediate: true })
watch(saved, () => { try { localStorage.setItem(KEY(d.value.slug), JSON.stringify(saved)) } catch { /* ignore */ } }, { deep: true })

const weeks = [1, 2, 3, 4, 5]
const weekDays = computed(() => Array.from({ length: week.value === 5 ? 2 : 7 }, (_, i) => (week.value - 1) * 7 + i + 1))
const plan = computed(() => (d.value ? dietDay(d.value, day.value) : null))
function pickWeek(w) { week.value = w; day.value = (w - 1) * 7 + 1 }
const toggleDone = () => {
  const i = saved.done.indexOf(day.value)
  i >= 0 ? saved.done.splice(i, 1) : saved.done.push(day.value)
}
const isDone = computed(() => saved.done.includes(day.value))
const nextDay = () => { if (day.value < 30) { day.value++; week.value = Math.ceil(day.value / 7) } }

// scale portions to the visitor's calculator result, if any
const target = computed(() => store.calc?.kcal || null)
const scale = computed(() => (target.value && plan.value ? Math.round(((target.value - plan.value.kcal) / plan.value.kcal) * 100) : null))

const tab = ref('eat')
const toggleShop = (item) => { const i = saved.shop.indexOf(item); i >= 0 ? saved.shop.splice(i, 1) : saved.shop.push(item) }
const others = computed(() => DIETS.filter((x) => x.slug !== d.value?.slug))
const scrollToPlan = () => document.getElementById('plan')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
const cycleLabels = computed(() => d.value?.cycleLabels || CYCLE_LABEL)
</script>

<template>
  <NotFound v-if="!d" />
  <article v-else class="sx" :class="{ mob: MOBILE }">
    <MAppBar v-if="MOBILE" :title="d.name" fallback="/diets" />

    <!-- ───── HERO: name + spec sheet ───── -->
    <header class="wrap sx-hero">
      <RouterLink v-if="!MOBILE" to="/diets" class="sx-crumb"><SxIcon name="right" />أنظمة الأكل</RouterLink>
      <h1>نظام {{ d.name }}</h1>
      <span class="sx-en">{{ d.en }}</span>
      <p class="sx-lead">{{ d.tagline }}</p>
      <div v-if="d.tags?.length" class="sx-tags"><span v-for="t in d.tags" :key="t">{{ t }}</span></div>

      <dl class="sx-spec">
        <template v-if="d.macros">
          <div><dt>كارب</dt><dd><span class="num">{{ d.macros.c }}%</span></dd></div>
          <div><dt>بروتين</dt><dd><span class="num">{{ d.macros.p }}%</span></dd></div>
          <div><dt>دهون</dt><dd><span class="num">{{ d.macros.f }}%</span></dd></div>
        </template>
        <template v-else>
          <div><dt>السعرات</dt><dd>من غير عدّ</dd></div>
          <div><dt>مسموح</dt><dd><span class="num">{{ d.eat.length }}</span> مجموعات</dd></div>
          <div><dt>ممنوع</dt><dd><span class="num">{{ d.avoid.length }}</span> مجموعات</dd></div>
        </template>
        <div><dt>الصعوبة</dt><dd>{{ d.level }}</dd></div>
      </dl>
      <div v-if="d.macros" class="sx-bar" aria-hidden="true">
        <i class="c" :style="{ flex: d.macros.c || 0.001 }"></i><i class="p" :style="{ flex: d.macros.p }"></i><i class="f" :style="{ flex: d.macros.f }"></i>
      </div>

      <div class="sx-cta">
        <button class="btn btn-primary" @click="scrollToPlan">{{ saved.done.length ? 'كمّل' : 'ابدأ' }} من اليوم <span class="num">{{ day }}</span></button>
        <small v-if="saved.done.length"><span class="num">{{ saved.done.length }}/30</span> يوم خلّصته</small>
      </div>
    </header>

    <!-- ───── HOW ───── -->
    <section class="wrap sx-sec">
      <div class="sx-h"><h2>بيشتغل إزاي؟</h2></div>
      <ol class="sx-steps">
        <li v-for="h in d.how" :key="h.t"><b>{{ h.t }}</b><p>{{ h.d }}</p></li>
      </ol>
    </section>

    <!-- ───── EAT / AVOID ───── -->
    <section class="wrap sx-sec">
      <div class="sx-h"><h2>تاكل إيه؟</h2></div>
      <div v-if="MOBILE" class="sx-seg" role="tablist">
        <button role="tab" :aria-selected="tab === 'eat'" :class="{ on: tab === 'eat' }" @click="tab = 'eat'">كُل</button>
        <button role="tab" :aria-selected="tab === 'avoid'" :class="{ on: tab === 'avoid' }" @click="tab = 'avoid'">ابعد عن</button>
      </div>
      <div class="sx-cols">
        <div v-if="!MOBILE || tab === 'eat'">
          <h3 v-if="!MOBILE">كُل</h3>
          <ul class="sx-check yes"><li v-for="x in d.eat" :key="x"><SxIcon name="check" />{{ x }}</li></ul>
        </div>
        <div v-if="!MOBILE || tab === 'avoid'">
          <h3 v-if="!MOBILE">ابعد عن</h3>
          <ul class="sx-check no"><li v-for="x in d.avoid" :key="x"><SxIcon name="x" />{{ x }}</li></ul>
        </div>
      </div>
    </section>

    <!-- ───── FOR WHO ───── -->
    <section class="wrap sx-sec">
      <div class="sx-h"><h2>مناسب لمين؟</h2></div>
      <div class="sx-cols">
        <div><h3>مناسب لـ</h3><ul class="sx-check yes"><li v-for="x in d.goodFor" :key="x"><SxIcon name="check" />{{ x }}</li></ul></div>
        <div><h3>مش مناسب لـ</h3><ul class="sx-check no"><li v-for="x in d.notFor" :key="x"><SxIcon name="x" />{{ x }}</li></ul></div>
      </div>
    </section>

    <!-- ───── 30-DAY PLAN ───── -->
    <section id="plan" class="wrap sx-sec">
      <div class="sx-h">
        <h2>خطة الـ <span class="num">30</span> يوم</h2>
        <small><span class="num">{{ saved.done.length }}/30</span> يوم خلّصته</small>
      </div>
      <div class="sx-weeks" role="tablist">
        <button v-for="w in weeks" :key="w" role="tab" :aria-selected="week === w" :class="{ on: week === w }" @click="pickWeek(w)">
          {{ w === 5 ? 'آخر يومين' : `الأسبوع ${w}` }}
        </button>
      </div>
      <div class="sx-days">
        <button
          v-for="n in weekDays" :key="n" class="sx-d"
          :class="{ sel: day === n, done: saved.done.includes(n), [`t-${dietDay(d, n).type}`]: d.cycle }"
          :aria-label="`اليوم ${n}`" :aria-pressed="day === n" @click="day = n"
        ><span class="num">{{ n }}</span></button>
      </div>
      <div v-if="d.cycle" class="sx-key">
        <span v-if="cycleLabels.high"><i style="background: var(--amber)"></i>{{ cycleLabels.high }}</span>
        <span v-if="cycleLabels.mid"><i style="background: #777"></i>{{ cycleLabels.mid }}</span>
        <span v-if="cycleLabels.low"><i style="border: 1px solid #555"></i>{{ cycleLabels.low }}</span>
      </div>

      <Transition name="sx-fade" mode="out-in">
        <div v-if="plan" :key="day" class="sx-day">
          <header class="sx-day-h">
            <div>
              <small v-if="plan.type">{{ cycleLabels[plan.type] }}</small>
              <b>اليوم <span class="num">{{ day }}</span></b>
            </div>
            <span v-if="!d.noKcal" class="sx-kc"><span class="num">{{ plan.kcal }}</span>سعرة تقريبًا</span>
          </header>
          <div class="sx-day-b">
            <ul class="sx-meals">
              <li v-for="(meal, i) in plan.meals" :key="i">
                <small>{{ meal.label }}</small>
                <b>{{ meal.n }}</b>
                <span v-if="meal.k" class="num">{{ meal.k }}</span>
              </li>
            </ul>
            <p v-if="d.noKcal" class="sx-say">الكميات على قد جوعك: «كُل لما تجوع وبطّل قبل الشبع».</p>
            <p v-else-if="scale !== null && Math.abs(scale) >= 5" class="sx-say">
              احتياجك حسب الحاسبة <b class="num">{{ target }}</b> سعرة، فـ{{ scale > 0 ? 'زوّد' : 'قلّل' }} الكميات حوالي <b class="num">{{ Math.abs(scale) }}%</b>.
            </p>
            <p v-else-if="scale === null" class="sx-say">الكميات تقريبية. <RouterLink to="/calculator">احسب سعراتك</RouterLink> عشان نظبطها على قدّك.</p>
          </div>
          <div class="sx-day-f">
            <button class="sx-btn" :class="{ on: isDone }" :aria-pressed="isDone" @click="toggleDone"><SxIcon v-if="isDone" name="check" />{{ isDone ? 'خلّصته' : 'علّم إنه خلص' }}</button>
            <button v-if="day < 30" class="sx-btn link" @click="nextDay">اليوم الجاي<SxIcon name="left" /></button>
          </div>
        </div>
      </Transition>
    </section>

    <!-- ───── SHOPPING + TIPS ───── -->
    <section class="wrap sx-sec">
      <div class="sx-cols">
        <div>
          <div class="sx-h"><h2>المشتريات</h2><small><span class="num">{{ saved.shop.length }}/{{ d.shopping.length }}</span></small></div>
          <ul class="sx-shop">
            <li v-for="item in d.shopping" :key="item">
              <button :class="{ got: saved.shop.includes(item) }" :aria-pressed="saved.shop.includes(item)" @click="toggleShop(item)"><i><SxIcon name="check" /></i>{{ item }}</button>
            </li>
          </ul>
        </div>
        <div>
          <div class="sx-h"><h2>نصايح</h2></div>
          <ul class="sx-tips"><li v-for="t in d.tips" :key="t">{{ t }}</li></ul>
          <div class="sx-warn"><b>قبل ما تبدأ</b><p>{{ d.warning }}</p></div>
          <a v-if="d.source" :href="d.source.url" target="_blank" rel="noopener" class="sx-src">المصدر: {{ d.source.label }}</a>
        </div>
      </div>
    </section>

    <!-- ───── OTHER DIETS ───── -->
    <section class="wrap sx-sec">
      <div class="sx-h"><h2>أنظمة تانية</h2><RouterLink to="/diets" class="sx-crumb">الكل</RouterLink></div>
      <ul class="sx-others">
        <li v-for="o in others" :key="o.slug"><RouterLink :to="`/diets/${o.slug}`"><b>{{ o.name }}</b><span class="sx-en">{{ o.en }}</span></RouterLink></li>
      </ul>
    </section>
  </article>
</template>
