<script setup>
// Signed-in home: everything the member does today in one screen — workout, meals, water, weight, coach.
import { ref, computed } from 'vue'
import { programDay } from '../data.js'
import { store, nextWorkout, doneDays, hasAccess, greeting, logWeight, setWater, toggleMeal, logOf, ymd, streak, toast } from '../stores/app.js'
import { todayMeals, todayScore, lastDays, WATER_GOAL, WD1, fmtDay, ago, daysAgo } from '../member.js'

defineProps({ mobile: Boolean })

const firstName = computed(() => store.user?.name?.split(' ')[0])
const today = ymd()

/* workout */
const next = computed(() => nextWorkout())
const p = computed(() => next.value?.program)
const day = computed(() => (p.value ? programDay(p.value, next.value.day) : null))
const done = computed(() => (p.value ? doneDays(p.value.id).length : 0))
const trainedToday = computed(() => store.workoutDates.includes(today))

/* meals */
const plan = computed(() => todayMeals())
const log = computed(() => logOf(today))
const eaten = computed(() => (plan.value ? plan.value.meals.reduce((s, m, i) => s + (log.value.meals.includes(i) ? m.kcal || 0 : 0), 0) : 0))

/* score + streak */
const score = computed(() => todayScore(plan.value))
const streakN = computed(() => streak())
const week = computed(() => lastDays(7))

/* weight */
const kg = ref('')
const lastW = computed(() => store.weights.at(-1))
const diff = computed(() => (store.weights.length > 1 ? +(store.weights.at(-1).kg - store.weights[0].kg).toFixed(1) : null))
const weighDue = computed(() => !lastW.value || daysAgo(lastW.value.date) >= 7)
const savingW = ref(false)
async function saveWeight() {
  const v = Number(kg.value)
  if (!v || v < 30 || v > 300) return toast('اكتب وزنك بالكيلو، مثلًا 82.5', 'err')
  savingW.value = true
  try { await logWeight(v); kg.value = ''; toast('اتسجّل ✓') } catch (e) { toast(e.message, 'err') } finally { savingW.value = false }
}
const spark = computed(() => {
  const ws = store.weights.slice(-10)
  if (ws.length < 2) return null
  const v = ws.map((w) => w.kg), lo = Math.min(...v) - 0.5, hi = Math.max(...v) + 0.5
  return ws.map((w, i) => `${(i / (ws.length - 1)) * 100},${30 - ((w.kg - lo) / (hi - lo)) * 30}`).join(' ')
})

/* coach */
const coach = computed(() => store.coach)
const checkinDue = computed(() => coach.value.access && (!coach.value.last_checkin || daysAgo(coach.value.last_checkin) >= 7))
const paid = computed(() => !!store.subscription)

const explore = [
  { to: '/training', t: 'أنظمة التدريب', d: 'فول بادي، بوش بول ليجز، 5×5… برنامج 4 أسابيع' },
  { to: '/diets', t: 'أنظمة الأكل', d: 'كيتو، صيام متقطع، الطيبات… خطة 30 يوم' },
  { to: '/programs', t: 'البرامج', d: 'تحدّي 30 يوم وبرامج البيت' },
  { to: '/library', t: 'مكتبة التمارين', d: 'كل تمرين بفيديو قصير' },
]
</script>

<template>
  <section class="mt" :class="{ mob: mobile }">
    <!-- greeting + streak -->
    <header class="mt-h">
      <div class="who">
        <small>{{ fmtDay() }}</small>
        <h1>{{ greeting() }}{{ firstName ? ` يا ${firstName}` : '' }}</h1>
      </div>
      <RouterLink to="/progress" class="streak" :class="{ cold: !streakN }" :aria-label="`${streakN} يوم ورا بعض`">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2c1 3.5 5 5.5 5 11a5 5 0 0 1-10 0c0-2.4 1.2-3.9 2.3-5 .2 1.6.9 2.7 2 3.2C11 8.6 11 5 12 2z" /></svg>
        <b class="num">{{ streakN }}</b>
        <span>{{ streakN === 1 ? 'يوم' : 'يوم ورا بعض' }}</span>
      </RouterLink>
    </header>

    <!-- today's score -->
    <div class="score" role="list" aria-label="يومك">
      <div class="sc" :class="{ ok: score.workout }" role="listitem">
        <span class="ic">🏋️</span>
        <div><b>{{ score.workout ? 'اتمرنت ✓' : 'لسه' }}</b><small>التمرين</small></div>
      </div>
      <div class="sc" :class="{ ok: score.mealsTotal && score.mealsDone >= score.mealsTotal }" role="listitem">
        <span class="ic">🍽️</span>
        <div><b class="num">{{ score.mealsDone }}/{{ score.mealsTotal || '–' }}</b><small>الوجبات</small></div>
        <i class="bar"><i :style="{ width: (score.mealsTotal ? (score.mealsDone / score.mealsTotal) * 100 : 0) + '%' }"></i></i>
      </div>
      <div class="sc" :class="{ ok: score.water >= WATER_GOAL }" role="listitem">
        <span class="ic">💧</span>
        <div><b class="num">{{ score.water }}/{{ WATER_GOAL }}</b><small>كوبايات مية</small></div>
        <i class="bar"><i :style="{ width: Math.min(100, (score.water / WATER_GOAL) * 100) + '%' }"></i></i>
      </div>
    </div>

    <div class="cols">
      <div class="main">
        <!-- workout -->
        <article v-if="p && day" class="card wk" :class="{ fin: trainedToday }">
          <div class="c-h">
            <h2>{{ trainedToday ? 'تمرين النهارده خلص 💪' : 'تمرين النهارده' }}</h2>
            <RouterLink :to="`/programs/${p.id}`" class="lnk">{{ p.title }} ‹</RouterLink>
          </div>
          <RouterLink :to="`/workout/${p.id}/${next.day}`" class="wk-b">
            <img :src="day.items[0]?.img || p.img" alt="" loading="lazy" />
            <div class="wk-t">
              <small class="amber">{{ trainedToday ? 'الجاي' : `الأسبوع ${day.week}` }} · {{ day.title }}</small>
              <b>اليوم <span class="num hl">{{ next.day }}</span> <span class="of">من <span class="num">{{ p.days }}</span></span></b>
              <span class="en moves">{{ day.items.map((i) => i.title).join(' · ') }}</span>
              <i class="bar"><i :style="{ width: (done / p.days) * 100 + '%' }"></i></i>
            </div>
          </RouterLink>
          <RouterLink v-if="!trainedToday" :to="`/workout/${p.id}/${next.day}`" class="btn btn-primary btn-block go">▶ ابدأ التمرين</RouterLink>
          <p v-else class="rest">ارتاح وكُل كويس — العضلة بتتبني وانت مرتاح. لو عايز تكمّل، اليوم الجاي جاهز.</p>
          <RouterLink v-if="!hasAccess(p.id) && next.day > 2" to="/pricing" class="lock">🔓 اليوم <span class="num">4</span> وبعده للمشتركين — افتح البرنامج كله</RouterLink>
        </article>

        <!-- meals -->
        <article class="card ml">
          <div class="c-h">
            <h2>أكلك النهارده</h2>
            <RouterLink v-if="plan" :to="plan.diet.slug === 'balanced' ? '/free-plan' : `/diets/${plan.diet.slug}`" class="lnk">{{ plan.diet.name }} ‹</RouterLink>
          </div>
          <template v-if="plan">
            <div class="ml-sum">
              <span class="pill">يوم <span class="num">{{ plan.n }}</span> من <span class="num">30</span></span>
              <span v-if="plan.type" class="pill alt">{{ plan.type }}</span>
              <span v-if="!plan.noKcal" class="kc">أكلت <b class="num">{{ eaten }}</b> من <span class="num">{{ plan.kcal }}</span> سعرة</span>
              <span v-else class="kc">كُل لما تجوع</span>
            </div>
            <ul class="meals">
              <li v-for="(m, i) in plan.meals" :key="i">
                <button type="button" class="tick" :class="{ on: log.meals.includes(i) }" :aria-pressed="log.meals.includes(i)" :aria-label="`${m.label}: ${log.meals.includes(i) ? 'اتاكلت' : 'لسه'}`" @click="toggleMeal(i)">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg>
                </button>
                <span class="mi">{{ m.icon }}</span>
                <span class="mx"><small>{{ m.label }}</small><b>{{ m.n }}</b></span>
                <span v-if="m.kcal" class="mk num">{{ m.kcal }}</span>
              </li>
            </ul>
            <p v-if="plan.scale && Math.abs(plan.scale) >= 5" class="note">الكميات المكتوبة على نظام عام — {{ plan.scale > 0 ? 'زوّد' : 'قلّل' }} كل وجبة حوالي <span class="num">{{ Math.abs(plan.scale) }}%</span>. السعرات تقريبية.</p>
            <p v-else class="note">السعرات تقريبية. علّم على الوجبة لما تاكلها.</p>
          </template>
          <div v-else class="empty">
            <p>لسه معندكش نظام أكل. احسب سعراتك واختار النظام اللي يناسبك — هيظهرلك هنا كل يوم بوجباته.</p>
            <RouterLink to="/calculator" class="btn btn-primary btn-sm">احسب سعراتك واختار نظامك</RouterLink>
          </div>
        </article>
      </div>

      <aside class="side">
        <!-- coach -->
        <article v-if="coach.access" class="card co">
          <div class="c-h">
            <h2>الكوتش</h2>
            <span v-if="coach.unread" class="badge"><span class="num">{{ coach.unread }}</span> جديد</span>
          </div>
          <RouterLink to="/coach" class="co-last">
            <img src="/img/logo-icon.webp" alt="" class="co-av" />
            <span v-if="coach.last" class="co-x">
              <small>{{ coach.last.from === 'coach' ? coach.last.coach : 'انت' }} · {{ ago(coach.last.at) }}</small>
              <b>{{ coach.last.body }}</b>
            </span>
            <span v-else class="co-x"><small>الكوتش المقنّع</small><b>عندك سؤال عن الأكل أو التمرين؟ ابعتلي هنا.</b></span>
          </RouterLink>
          <div v-if="coach.notes?.plan_adjustments" class="adj">
            <small>آخر تعديل من الكوتش</small>
            <p>{{ coach.notes.plan_adjustments }}</p>
          </div>
          <div class="co-act">
            <RouterLink to="/coach" class="btn btn-ghost btn-sm">ابعت رسالة</RouterLink>
            <RouterLink to="/coach?tab=checkin" class="btn btn-sm" :class="checkinDue ? 'btn-primary' : 'btn-ghost'">
              {{ checkinDue ? 'متابعة الأسبوع' : 'متابعة جديدة' }}
            </RouterLink>
          </div>
          <small v-if="coach.next_checkin" class="muted nx">المتابعة الجاية: <span class="num">{{ coach.next_checkin }}</span></small>
          <small v-else-if="checkinDue" class="muted nx">ابعت وزنك والتزامك كل أسبوع والكوتش يظبطلك النظام.</small>
        </article>
        <RouterLink v-else to="/pricing" class="card co locked">
          <div class="c-h"><h2>كوتش معاك 🔒</h2></div>
          <ul>
            <li>اسأل الكوتش في أي وقت عن أكلك وتمرينك</li>
            <li>متابعة كل أسبوع: وزن، قياسات، التزام</li>
            <li>يظبطلك النظام على حسب نتيجتك</li>
          </ul>
          <span class="btn btn-primary btn-sm btn-block">{{ paid ? 'متاح في الباقات المدفوعة' : 'اشترك وخد كوتش معاك' }}</span>
        </RouterLink>

        <!-- water -->
        <article class="card wa">
          <div class="c-h">
            <h2>المية</h2>
            <span class="muted"><span class="num">{{ (log.water * 0.25).toFixed(log.water % 4 ? 2 : 0) }}</span> لتر من <span class="num">2</span></span>
          </div>
          <div class="cups">
            <button v-for="n in WATER_GOAL" :key="n" type="button" class="cup" :class="{ on: log.water >= n }" :aria-label="`${n} كوباية`" @click="setWater(log.water >= n && log.water === n ? n - 1 : n)">
              <svg viewBox="0 0 24 30" aria-hidden="true"><path d="M3 3h18l-2.2 23a2 2 0 0 1-2 1.8H7.2a2 2 0 0 1-2-1.8z" /><path class="fill" d="M5.6 12h12.8l-1.5 14.2a1 1 0 0 1-1 .9H8.1a1 1 0 0 1-1-.9z" /></svg>
            </button>
          </div>
          <div class="wa-act">
            <button type="button" class="mini" :disabled="!log.water" aria-label="شيل كوباية" @click="setWater(log.water - 1)">−</button>
            <button type="button" class="btn btn-ghost btn-sm" @click="setWater(log.water + 1)">+ كوباية</button>
          </div>
        </article>

        <!-- weight -->
        <article class="card wt">
          <div class="c-h">
            <h2>وزنك</h2>
            <RouterLink to="/progress" class="lnk">تقدّمك ‹</RouterLink>
          </div>
          <div class="wt-now">
            <div>
              <b class="num big">{{ lastW?.kg ?? '—' }}</b><span class="muted"> كجم</span>
              <small v-if="lastW" class="muted d">{{ ago(lastW.date) }}</small>
            </div>
            <span v-if="diff !== null" class="delta" :class="{ down: diff < 0 }"><span class="num">{{ diff > 0 ? '+' : '' }}{{ diff }}</span> من البداية</span>
          </div>
          <svg v-if="spark" viewBox="-2 -3 104 36" class="spark" preserveAspectRatio="none" aria-hidden="true"><polyline :points="spark" /></svg>
          <form class="wt-f" @submit.prevent="saveWeight">
            <input v-model="kg" class="input ltr" type="number" inputmode="decimal" step="0.1" :placeholder="lastW?.kg ?? store.calc?.weight ?? '80'" aria-label="وزنك النهارده بالكيلو" />
            <button class="btn btn-sm" :class="weighDue ? 'btn-primary' : 'btn-ghost'" :disabled="savingW">سجّل</button>
          </form>
          <small v-if="weighDue" class="muted">{{ lastW ? 'عدّى أسبوع — اوزن نفسك الصبح قبل الفطار.' : 'سجّل وزنك النهارده عشان تشوف الفرق بعدين.' }}</small>
        </article>

        <!-- week -->
        <RouterLink to="/progress" class="card wkly" aria-label="آخر 7 أيام">
          <div class="c-h"><h2>آخر 7 أيام</h2><span class="lnk">التفاصيل ‹</span></div>
          <div class="days">
            <div v-for="d in week" :key="d.date" class="dd" :class="{ now: d.date === today }">
              <span class="dots">
                <i :class="{ on: d.workout }" title="تمرين"></i>
                <i :class="{ on: d.meals > 0 }" title="أكل"></i>
                <i :class="{ on: d.water >= WATER_GOAL / 2 }" title="مية"></i>
              </span>
              <small>{{ WD1[d.d.getDay()] }}</small>
            </div>
          </div>
          <div class="legend"><span><i class="l1"></i>تمرين</span><span><i class="l2"></i>أكل</span><span><i class="l3"></i>مية</span></div>
        </RouterLink>

        <!-- explore: everything outside today -->
        <nav class="card xp" aria-label="استكشف">
          <div class="c-h"><h2>استكشف</h2></div>
          <RouterLink v-for="x in explore" :key="x.to" :to="x.to" class="xp-r">
            <span><b>{{ x.t }}</b><small>{{ x.d }}</small></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M15 5l-7 7 7 7" /></svg>
          </RouterLink>
        </nav>
      </aside>
    </div>
  </section>
</template>

<style scoped>
.mt { display: grid; gap: 14px; padding-top: calc(var(--header) + env(safe-area-inset-top) + 18px); padding-bottom: 28px; }
.mt.mob { padding-top: 4px; gap: 12px; }

/* header */
.mt-h { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; }
.who small { color: var(--muted); font-weight: 800; font-size: .82rem; }
.who h1 { font-size: clamp(1.8rem, 6vw, 2.6rem); line-height: 1.1; }
.mob .who h1 { display: none; }
.mob .mt-h { align-items: center; }
.streak { display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 99px; background: rgba(255,106,0,.12); border: 1px solid rgba(255,106,0,.4); font-weight: 800; font-size: .82rem; flex: none; }
.streak svg { width: 18px; height: 18px; fill: var(--orange); }
.streak b { font-size: 1.4rem; line-height: 1; color: var(--amber); }
.streak.cold { background: #141414; border-color: #262626; }
.streak.cold svg { fill: #555; }

/* score */
.score { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.sc { position: relative; display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 16px; background: #141414; border: 1px solid #222; overflow: hidden; }
.sc .ic { font-size: 1.3rem; }
.sc div { display: grid; line-height: 1.15; min-width: 0; }
.sc b { font-size: 1.05rem; }
.sc b.num { font-size: 1.6rem; justify-self: start; }
.sc small { font-size: .7rem; color: var(--muted); font-weight: 800; }
.sc.ok { border-color: rgba(61,220,132,.45); background: linear-gradient(140deg, rgba(61,220,132,.12), #141414 60%); }
.sc .bar { position: absolute; inset-inline: 0; bottom: 0; height: 3px; background: #222; }
.bar { display: block; height: 4px; border-radius: 4px; background: #262626; overflow: hidden; }
.bar > i { display: block; height: 100%; background: var(--orange); border-radius: inherit; transition: width .35s; }
.sc.ok .bar > i { background: var(--ok); }

/* layout */
.cols { display: grid; gap: 12px; }
.main, .side { display: grid; gap: 12px; align-content: start; }
@media (min-width: 960px) {
  .cols { grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr); gap: 16px; align-items: start; }
  .score { gap: 12px; }
  .sc { padding: 16px; }
}

.card { display: grid; gap: 12px; padding: 16px; border-radius: 20px; background: #121212; border: 1px solid #232323; }
.c-h { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.c-h h2 { font-size: 1.35rem; line-height: 1.2; }
.lnk { font-weight: 800; font-size: .8rem; color: var(--orange); white-space: nowrap; }

/* workout */
.wk-b { display: flex; gap: 12px; align-items: center; }
.wk-b img { width: 92px; height: 116px; border-radius: 14px; object-fit: cover; object-position: center 30%; flex: none; background: #000; }
.wk-t { display: grid; gap: 3px; min-width: 0; flex: 1; }
.wk-t small { font-weight: 800; font-size: .78rem; }
.wk-t b { font-family: var(--f-head); font-weight: 400; font-size: 1.7rem; line-height: 1.1; }
.wk-t b .num { font-size: 1.15em; }
.wk-t .of { font-family: var(--f-body); font-size: .8rem; font-weight: 800; color: var(--muted); }
.wk-t .of .num { font-size: 1rem; }
.moves { color: rgba(255,255,255,.75); font-size: 1rem; justify-self: start; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
.wk-t .bar { margin-top: 6px; }
.go { min-height: 52px; font-family: var(--f-head); font-weight: 400; font-size: 1.2rem; }
.wk.fin { border-color: rgba(61,220,132,.35); }
.wk.fin .wk-b img { filter: grayscale(.4) brightness(.8); }
.rest { font-size: .84rem; color: var(--muted); }
.lock { font-size: .8rem; font-weight: 800; padding: 10px 12px; border-radius: 12px; background: rgba(255,106,0,.1); border: 1px solid rgba(255,106,0,.35); }

/* meals */
.ml-sum { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pill { font-size: .74rem; font-weight: 800; padding: 3px 10px; border-radius: 99px; background: #1d1d1d; border: 1px solid #2a2a2a; }
.pill .num { font-size: .95rem; }
.pill.alt { color: var(--amber); }
.kc { margin-inline-start: auto; font-size: .8rem; font-weight: 800; color: var(--muted); }
.kc b { font-size: 1.35rem; color: var(--orange); }
.kc .num { font-size: 1.05rem; }
.meals { list-style: none; margin: 0; padding: 0; display: grid; }
.meals li { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-top: 1px solid #1f1f1f; }
.tick { width: 34px; height: 34px; flex: none; border-radius: 50%; border: 2px solid #3a3a3a; display: grid; place-items: center; transition: background .15s, border-color .15s, transform .1s; }
.tick:active { transform: scale(.9); }
.tick svg { width: 18px; height: 18px; fill: none; stroke: transparent; stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
.tick.on { background: var(--orange); border-color: var(--orange); }
.tick.on svg { stroke: #0a0a0a; }
.mi { font-size: 1.2rem; width: 26px; text-align: center; flex: none; }
.mx { flex: 1; display: grid; min-width: 0; }
.mx small { font-size: .7rem; font-weight: 800; color: var(--muted); }
.mx b { font-size: .9rem; font-weight: 700; line-height: 1.5; }
.tick.on ~ .mx b { color: var(--muted); text-decoration: line-through; text-decoration-color: rgba(255,106,0,.6); }
.mk { font-size: 1.15rem; color: var(--amber); }
.note { font-size: .76rem; color: var(--muted); }
.empty { display: grid; gap: 10px; justify-items: start; }
.empty p { color: var(--muted); font-size: .9rem; }

/* coach */
.badge { font-size: .72rem; font-weight: 900; padding: 2px 9px; border-radius: 99px; background: var(--orange); color: #0a0a0a; }
.co-last { display: flex; gap: 10px; align-items: flex-start; padding: 12px; border-radius: 14px; background: #181818; border: 1px solid #262626; }
.co-av { width: 40px; height: 40px; border-radius: 12px; flex: none; background: #000; object-fit: cover; }
.co-x { display: grid; min-width: 0; }
.co-x small { font-size: .72rem; font-weight: 800; color: var(--muted); }
.co-x b { font-size: .9rem; font-weight: 700; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.adj { padding: 10px 12px; border-radius: 12px; border-inline-start: 3px solid var(--amber); background: rgba(255,176,0,.07); }
.adj small { font-size: .72rem; font-weight: 800; color: var(--amber); }
.adj p { font-size: .86rem; white-space: pre-line; }
.co-act { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.nx { font-size: .76rem; }
.co.locked { background: linear-gradient(150deg, rgba(255,106,0,.16), #121212 55%); border-color: rgba(255,106,0,.4); }
.co.locked ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 6px; font-size: .86rem; font-weight: 700; }
.co.locked li::before { content: '✓ '; color: var(--orange); font-weight: 900; }

/* water */
.cups { display: grid; grid-template-columns: repeat(8, 1fr); gap: 4px; }
.cup { aspect-ratio: .8; display: grid; place-items: center; border-radius: 10px; transition: transform .1s; }
.cup:active { transform: scale(.88); }
.cup svg { width: 100%; max-width: 30px; height: auto; }
.cup path { fill: none; stroke: #444; stroke-width: 1.8; stroke-linejoin: round; }
.cup .fill { fill: transparent; stroke: none; transition: fill .2s; }
.cup.on path { stroke: #4fc3ff; }
.cup.on .fill { fill: #4fc3ff; }
.wa-act { display: flex; gap: 8px; justify-content: flex-end; }
.mini { width: 42px; height: 42px; border-radius: 11px; border: 1.5px solid #333; font-size: 1.3rem; font-weight: 900; }
.mini:disabled { opacity: .35; }

/* weight */
.wt-now { display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; }
.big { font-size: 2.6rem; line-height: 1; }
.wt-now .d { display: block; font-size: .74rem; }
.delta { font-size: .78rem; font-weight: 800; color: var(--muted); padding: 4px 10px; border-radius: 99px; background: #1b1b1b; }
.delta .num { font-size: 1.1rem; }
.delta.down .num { color: var(--ok); }
.spark { width: 100%; height: 40px; }
.spark polyline { fill: none; stroke: var(--orange); stroke-width: 2; vector-effect: non-scaling-stroke; stroke-linejoin: round; stroke-linecap: round; }
.wt-f { display: flex; gap: 8px; }
.wt-f .input { flex: 1; min-width: 0; min-height: 44px; }

/* last 7 days */
.days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.dd { display: grid; justify-items: center; gap: 4px; padding: 8px 0; border-radius: 12px; }
.dd.now { background: #1b1b1b; outline: 1px solid #2c2c2c; }
.dd small { font-size: .72rem; font-weight: 800; color: var(--muted); }
.dots { display: grid; gap: 3px; }
.dots i { width: 10px; height: 10px; border-radius: 50%; background: #262626; }
.dots i.on:nth-child(1) { background: var(--orange); }
.dots i.on:nth-child(2) { background: var(--amber); }
.dots i.on:nth-child(3) { background: #4fc3ff; }
.legend { display: flex; gap: 12px; justify-content: center; font-size: .7rem; font-weight: 800; color: var(--muted); }
.legend i { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-inline-end: 4px; }
.l1 { background: var(--orange); } .l2 { background: var(--amber); } .l3 { background: #4fc3ff; }

@media (max-width: 420px) {
  .sc { flex-direction: column; align-items: flex-start; gap: 4px; padding: 10px; }
  .sc .ic { font-size: 1.1rem; }
}
.xp { display: grid; }
.xp-r { display: flex; align-items: center; gap: 10px; padding: 11px 0; border-top: 1px solid rgba(255,255,255,.07); }
.xp-r span { flex: 1; display: grid; min-width: 0; }
.xp-r b { font-size: .95rem; }
.xp-r small { font-size: .76rem; color: var(--muted); }
.xp-r svg { width: 18px; height: 18px; flex: none; color: rgba(255,255,255,.4); }
.xp-r:hover b, .xp-r:hover svg { color: var(--orange); }
</style>
