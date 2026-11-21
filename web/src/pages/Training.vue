<script setup>
import { ref, reactive, computed, watch, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import NotFound from './NotFound.vue'
import MAppBar from '../mobile/MAppBar.vue'
import { MOBILE } from '../mobile/device.js'
import SxIcon from '../components/SxIcon.vue'
import { TRAININGS, trainingBySlug, trainingDay, fmtRest, PLACE, WEEKDAYS, WD_SHORT, shortKey } from '../training/data.js'
import { videoById } from '../data.js'

const route = useRoute()
const t = computed(() => trainingBySlug(route.params.slug))
watchEffect(() => { if (t.value) document.title = `نظام ${t.value.name} (${t.value.en}) — برنامج 4 أسابيع | فورمة` })

/* ───── 4-week program: week tabs + 7-day row + selected day ───── */
const TOTAL = 28
const week = ref(1)
const day = ref(1)
const KEY = (s) => `forma:training:${s}`
const saved = reactive({ done: [] })
const isTraining = (n) => !trainingDay(t.value, n).rest
const trainingDays = computed(() => (t.value ? Array.from({ length: TOTAL }, (_, i) => i + 1).filter(isTraining) : []))
watch(t, (v) => {
  if (!v) return
  let s = {}
  try { s = JSON.parse(localStorage.getItem(KEY(v.slug)) || '{}') } catch { /* private mode */ }
  saved.done = s.done || []
  const first = trainingDays.value.find((n) => !saved.done.includes(n)) || 1
  day.value = first
  week.value = Math.ceil(first / 7)
}, { immediate: true })
watch(saved, () => { try { localStorage.setItem(KEY(t.value.slug), JSON.stringify(saved)) } catch { /* ignore */ } }, { deep: true })

const weekDays = computed(() => Array.from({ length: 7 }, (_, i) => (week.value - 1) * 7 + i + 1))
const plan = computed(() => (t.value ? trainingDay(t.value, day.value) : null))
const wk = computed(() => t.value.weeks[week.value - 1])
function pickWeek(w) { week.value = w; day.value = (w - 1) * 7 + 1 }
const isDone = computed(() => saved.done.includes(day.value))
const toggleDone = () => { const i = saved.done.indexOf(day.value); i >= 0 ? saved.done.splice(i, 1) : saved.done.push(day.value) }
const nextDay = () => {
  const n = trainingDays.value.find((x) => x > day.value)
  if (n) { day.value = n; week.value = Math.ceil(n / 7) }
}
const hasNext = computed(() => trainingDays.value.some((x) => x > day.value))
const doneCount = computed(() => saved.done.filter((n) => trainingDays.value.includes(n)).length)

// rough session length: sets × (work + rest)
const estMin = computed(() => {
  const p = plan.value
  if (!p || p.rest) return 0
  if (p.minutes) return p.minutes
  if (p.format === 'interval') {
    const per = p.items.reduce((s, x) => s + (parseInt(x.reps) || 40) + (x.rest || 0), 0)
    return Math.round(((p.blocks ? per * p.rounds : per * p.rounds) + 60 * ((p.blocks || p.rounds) - 1)) / 60)
  }
  if (p.format === 'chipper') return null
  return Math.round(p.items.reduce((s, x) => s + (x.sets || 1) * (40 + (x.rest || 60)), 0) / 60)
})
const fmtLabel = { sets: 'مجموعات وعدّات', interval: 'إنترفال', amrap: 'AMRAP', emom: 'EMOM', chipper: 'تشيبر' }

const vid = (id) => (id ? videoById(id) : null)
const others = computed(() => TRAININGS.filter((x) => x.slug !== t.value?.slug))
const scrollToPlan = () => document.getElementById('plan')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
const dayKeyShort = (n) => {
  const d = trainingDay(t.value, n)
  return d.rest ? '' : shortKey(d.key)
}
</script>

<template>
  <NotFound v-if="!t" />
  <article v-else class="sx" :class="{ mob: MOBILE }">
    <MAppBar v-if="MOBILE" :title="t.name" fallback="/training" />

    <!-- ───── HERO ───── -->
    <header class="sx-hero">
      <picture>
        <source media="(min-width: 700px)" srcset="/img/banners/challenge-960.webp" />
        <img class="sx-hero-img" src="/img/banners/challenge-640.webp" alt="" fetchpriority="high" />
      </picture>
      <div class="wrap sx-hero-in">
        <RouterLink v-if="!MOBILE" to="/training" class="sx-crumb"><SxIcon name="right" />أنظمة التدريب</RouterLink>
        <span class="sx-en">{{ t.en }}</span>
        <h1>نظام <span class="hl">{{ t.name }}</span></h1>
        <p class="sx-lead">{{ t.tagline }}</p>
        <ul class="sx-pills"><li class="on">برنامج <span class="num">4</span> أسابيع</li><li>{{ PLACE[t.place] }}</li><li>{{ t.level }}</li></ul>
        <div class="sx-ctas">
          <button class="btn btn-primary" @click="scrollToPlan">{{ doneCount ? 'كمّل' : 'ابدأ' }} من اليوم <span class="num">{{ day }}</span></button>
        </div>
      </div>
    </header>

    <!-- ───── SPEC BAND ───── -->
    <section class="sx-band sx-specband">
      <div class="wrap sx-band-in">
        <dl class="sx-spec">
          <div><dt>في الأسبوع</dt><dd><span class="num">{{ t.daysPerWeek }}</span>أيام</dd></div>
          <div><dt>التمرينة</dt><dd><span class="num">{{ t.minutes }}</span>دقيقة</dd></div>
          <div><dt>المكان</dt><dd>{{ PLACE[t.place] }}</dd></div>
          <div><dt>المستوى</dt><dd>{{ t.level }}</dd></div>
        </dl>
      </div>
    </section>

    <!-- ───── HOW ───── -->
    <section class="wrap sx-sec">
      <div class="sx-h"><h2>بيشتغل <span class="hl">إزاي؟</span></h2></div>
      <ol class="sx-steps">
        <li v-for="h in t.how" :key="h.t"><b>{{ h.t }}</b><p>{{ h.d }}</p></li>
      </ol>
    </section>

    <!-- ───── WEEK MAP + FOR WHO ───── -->
    <section class="wrap sx-sec">
      <div class="sx-h"><h2>شكل <span class="hl">الأسبوع</span></h2></div>
      <div class="sx-cols">
        <div class="sx-panel yes">
          <header>جدولك</header>
          <ul class="sx-map">
            <li v-for="(k, i) in t.split" :key="i" :class="{ rest: k === 'rest' }">
              <small>{{ WEEKDAYS[i] }}</small>
              <b>{{ k === 'rest' ? 'راحة' : t.days[k].title }}</b>
            </li>
          </ul>
          <p v-if="t.splitAlt" class="sx-say" style="padding: 0 16px 14px">الأسبوع التاني والرابع الترتيب بيتبدّل، عشان التمرينتين ياخدوا نفس الحق.</p>
        </div>
        <div class="sx-panel">
          <header>مناسب لمين؟</header>
          <div class="yes"><ul class="sx-check"><li v-for="x in t.goodFor" :key="x"><SxIcon name="check" />{{ x }}</li></ul></div>
          <div class="no"><ul class="sx-check" style="padding-top: 0"><li v-for="x in t.notFor" :key="x"><SxIcon name="x" />{{ x }}</li></ul></div>
          <div class="sx-warn"><b>قبل ما تبدأ</b><p>{{ t.warning }}</p></div>
        </div>
      </div>
    </section>

    <!-- ───── 4-WEEK PROGRAM ───── -->
    <section id="plan" class="sx-plan">
      <span class="sx-plan-big num" aria-hidden="true">4W</span>
      <div class="wrap">
        <div class="sx-h">
          <h2>البرنامج <span class="hl">يوم بيوم</span></h2>
          <small><span class="num">{{ doneCount }}/{{ trainingDays.length }}</span> تمرينة خلّصتها</small>
        </div>
        <div class="sx-weeks" role="tablist">
          <button v-for="w in 4" :key="w" role="tab" :aria-selected="week === w" :class="{ on: week === w }" @click="pickWeek(w)">
            <span>الأسبوع {{ w }}<small>{{ t.weeks[w - 1].t }}</small></span>
          </button>
        </div>
        <p class="sx-wnote"><b>{{ wk.t }}:</b> {{ wk.d }}</p>
        <div class="sx-days">
          <button
            v-for="n in weekDays" :key="n" class="sx-d"
            :class="{ sel: day === n, done: saved.done.includes(n), rest: !isTraining(n) }"
            :aria-label="`اليوم ${n}`" :aria-pressed="day === n" @click="day = n"
          >
            <small>{{ WD_SHORT[(n - 1) % 7] }}</small>
            <span v-if="isTraining(n)" class="num">{{ dayKeyShort(n) }}</span>
            <small v-else>راحة</small>
          </button>
        </div>

        <Transition name="sx-fade" mode="out-in">
          <div v-if="plan" :key="day" class="sx-day">
            <div v-if="plan.rest" class="sx-rest">
              <b>يوم راحة، {{ plan.weekday }}</b>
              <p>العضلة بتكبر وانت مرتاح. امشي خفيف 20 لـ 30 دقيقة أو اعمل إطالة لو حابب، ونام كويس.</p>
              <RouterLink to="/training/mobility">جرّب 15 دقيقة مرونة</RouterLink>
            </div>
            <template v-else>
              <header class="sx-day-h">
                <div>
                  <small>اليوم {{ plan.n }} · {{ plan.weekday }} · {{ fmtLabel[plan.format] }}</small>
                  <b>{{ plan.title }}</b>
                </div>
                <span v-if="estMin" class="sx-kc"><span class="num">{{ estMin }}</span>دقيقة تقريبًا</span>
              </header>
              <div class="sx-day-b">
                <p v-if="plan.format === 'interval' && !plan.blocks" class="sx-fmt">كرّر الدايرة <b class="num">{{ plan.rounds }}</b> مرات، ودقيقة راحة بين كل دايرة.</p>
                <p v-else-if="plan.blocks" class="sx-fmt"><b class="num">{{ plan.blocks }}</b> بلوكات × <b class="num">8</b> جولات (20 ث شغل / 10 راحة)، ودقيقة راحة بين البلوكات.</p>
                <p v-else-if="plan.format === 'amrap'" class="sx-fmt">أكبر عدد جولات في <b class="num">{{ plan.minutes }}</b> دقيقة. سجّل رقمك.</p>
                <p v-else-if="plan.format === 'emom'" class="sx-fmt"><b class="num">{{ plan.minutes }}</b> دقيقة: كل دقيقة تمرين، وكرّر الأربعة بالترتيب.</p>
                <p v-else-if="plan.format === 'chipper'" class="sx-fmt">خلّص القايمة بالترتيب بأسرع وقت، وقسّمها زي ما تحب.</p>

                <ol class="sx-exs">
                  <li v-for="(x, i) in plan.items" :key="i">
                    <RouterLink v-if="vid(x.vid)" :to="`/library/${x.vid}`" class="sx-thumb" :aria-label="`فيديو ${x.n}`"><img :src="vid(x.vid).img" alt="" loading="lazy" /><SxIcon name="play" /></RouterLink>
                    <span v-else class="sx-thumb-none"></span>
                    <span class="sx-ex">
                      <b>{{ x.n }}</b>
                      <small v-if="x.note">{{ x.note }}</small>
                    </span>
                    <span class="sx-sr">
                      <span v-if="x.sets" class="v">{{ x.sets }}×{{ x.reps }}</span>
                      <span v-else class="v txt">{{ x.reps }}</span>
                      <small v-if="x.rest">راحة {{ fmtRest(x.rest) }}</small>
                    </span>
                  </li>
                </ol>
                <p class="sx-say">سخّن 5 لـ 10 دقايق الأول. الأرقام إرشادية، ولو التكنيك باظ قلّل الوزن أو العدد.</p>
              </div>
            </template>
            <div class="sx-day-f">
              <button v-if="!plan.rest" class="sx-btn" :class="{ on: isDone }" :aria-pressed="isDone" @click="toggleDone"><SxIcon v-if="isDone" name="check" />{{ isDone ? 'خلّصتها' : 'علّم إنها خلصت' }}</button>
              <button v-if="hasNext" class="sx-btn link" @click="nextDay">التمرينة الجاية<SxIcon name="left" /></button>
            </div>
          </div>
        </Transition>
      </div>
    </section>

    <!-- ───── PROGRESSION ───── -->
    <section class="wrap sx-sec">
      <div class="sx-h"><h2>هتتطوّر <span class="hl">إزاي؟</span></h2></div>
      <ol class="sx-prog">
        <li v-for="(w, i) in t.weeks" :key="i" :class="{ cur: week === i + 1 }">
          <span class="num">W{{ i + 1 }}</span><b>{{ w.t }}</b><p>{{ w.d }}</p>
        </li>
      </ol>
      <p class="sx-say">بعد الـ 4 أسابيع: كرّر البرنامج بأوزان أتقل شوية، أو انقل لنظام تاني.</p>
    </section>

    <!-- ───── OTHER SYSTEMS ───── -->
    <section class="wrap sx-sec">
      <div class="sx-h"><h2>أنظمة <span class="hl">تانية</span></h2><RouterLink to="/training">كل الأنظمة ←</RouterLink></div>
      <div class="sx-rail">
        <RouterLink v-for="o in others" :key="o.slug" :to="`/training/${o.slug}`"><span class="sx-en">{{ o.en }}</span><b>{{ o.name }}</b></RouterLink>
      </div>
    </section>
  </article>
</template>
