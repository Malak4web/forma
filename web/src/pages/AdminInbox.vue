<script setup>
// Coach inbox: member threads (chat + weekly check-ins waiting for a review) with a member snapshot.
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminNav from '../components/AdminNav.vue'
import { api, fieldErrors } from '../api.js'
import { toast } from '../stores/app.js'
import { dietBySlug, BALANCED } from '../diets/data.js'

const route = useRoute()
const router = useRouter()

const threads = ref([])
const counts = ref({ unread: 0, pending_checkins: 0 })
const q = ref('')
const loadingList = ref(true)
const openId = ref(Number(route.query.u) || null)
const thread = ref(null) // { member, messages, checkins }
const draft = ref('')
const sending = ref(false)
const list = ref(null)

async function loadThreads() {
  try {
    const r = await api(`/admin/inbox${q.value ? `?q=${encodeURIComponent(q.value)}` : ''}`)
    threads.value = r.data
    counts.value = r.counts
  } catch (e) { toast(e.message, 'err') } finally { loadingList.value = false }
}
async function open(id, quiet = false) {
  openId.value = id
  if (!quiet) router.replace({ query: { u: id } })
  try {
    const r = await api(`/admin/inbox/${id}`)
    const grew = r.messages.length !== thread.value?.messages.length
    thread.value = r
    const t = threads.value.find((x) => x.id === id)
    if (t) t.unread = 0
    if (grew) nextTick(() => { if (list.value) list.value.scrollTop = list.value.scrollHeight })
  } catch (e) { toast(e.message, 'err') }
}
async function reply() {
  const body = draft.value.trim()
  if (!body || !openId.value) return
  sending.value = true
  try {
    const r = await api(`/admin/inbox/${openId.value}`, { method: 'POST', body: { body } })
    thread.value.messages.push(r.message)
    draft.value = ''
    nextTick(() => { if (list.value) list.value.scrollTop = list.value.scrollHeight })
    loadThreads()
  } catch (e) { toast(e.message, 'err') } finally { sending.value = false }
}
const onKey = (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); reply() } }

/* check-in review */
const rev = reactive({}) // id → form
const STATUS = [{ id: 'on_track', label: 'ماشي تمام' }, { id: 'needs_attention', label: 'محتاج متابعة' }, { id: 'delayed', label: 'متأخر' }, { id: 'completed', label: 'تمت' }]
function startReview(c) {
  const d = new Date(); d.setDate(d.getDate() + 7)
  rev[c.id] = { coach_notes: c.coach_notes || '', plan_adjustments: c.plan_adjustments || '', next_followup_date: c.next || d.toISOString().slice(0, 10), status: c.status === 'needs_attention' && !c.reviewed ? 'on_track' : c.status, errs: {} }
}
async function saveReview(c) {
  const f = rev[c.id]
  try {
    const { errs, ...body } = f
    const r = await api(`/admin/inbox/${openId.value}/checkins/${c.id}`, { method: 'PUT', body: { ...body, next_followup_date: body.next_followup_date || null } })
    Object.assign(c, r.checkin)
    delete rev[c.id]
    toast('اتبعت للمشترك ✓')
    loadThreads()
  } catch (e) { f.errs = fieldErrors(e); toast(e.message, 'err') }
}

const m = computed(() => thread.value?.member)
const diet = computed(() => (m.value?.nutrition ? dietBySlug(m.value.nutrition.input?.diet) || BALANCED : null))
const wDiff = computed(() => (m.value?.weights.length > 1 ? +(m.value.weights.at(-1).kg - m.value.weights[0].kg).toFixed(1) : null))
const goalLabel = { loss: 'تخسيس', cut: 'تنشيف', gain: 'عضل' }
const time = (iso) => new Date(iso).toLocaleString('ar-EG', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' })
const wd = (iso) => ['ح', 'ن', 'ث', 'ر', 'خ', 'ج', 'س'][new Date(iso).getDay()]

let timer
onMounted(async () => {
  await loadThreads()
  if (openId.value) open(openId.value, true)
  else if (threads.value[0]) open(threads.value[0].id)
  timer = setInterval(() => {
    if (document.visibilityState !== 'visible') return
    loadThreads()
    if (openId.value) open(openId.value, true)
  }, 20000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <div>
        <h1>رسايل المشتركين</h1>
        <p class="muted">
          <span class="num">{{ counts.unread }}</span> رسالة مش مقروءة ·
          <span class="num">{{ counts.pending_checkins }}</span> متابعة أسبوعية مستنية ردك
        </p>
      </div>
    </header>

    <div class="box">
      <!-- threads -->
      <aside class="threads" :class="{ hide: openId }">
        <input v-model="q" class="input" placeholder="دوّر بالاسم أو الموبايل" @input="loadThreads" />
        <p v-if="!loadingList && !threads.length" class="muted pad">لسه مفيش رسايل أو متابعات من المشتركين.</p>
        <button v-for="t in threads" :key="t.id" type="button" class="th" :class="{ on: openId === t.id }" @click="open(t.id)">
          <span class="av">{{ t.name.charAt(0) }}</span>
          <span class="tx">
            <b>{{ t.name }}</b>
            <small v-if="t.last">{{ t.last.from === 'coach' ? 'انت: ' : '' }}{{ t.last.body }}</small>
            <small v-else class="amber">بعت متابعة أسبوعية</small>
          </span>
          <span class="bd">
            <i v-if="t.unread" class="u num">{{ t.unread }}</i>
            <i v-if="t.pending_checkins" class="c" title="متابعة مستنية رد">📋</i>
          </span>
        </button>
      </aside>

      <!-- conversation -->
      <section v-if="thread && m" class="conv">
        <header class="ch">
          <button type="button" class="back" aria-label="رجوع للقايمة" @click="openId = null; thread = null">‹</button>
          <span class="av">{{ m.name.charAt(0) }}</span>
          <div><b>{{ m.name }}</b><small class="muted en">{{ m.phone }}</small></div>
          <span class="tag">{{ m.plan || 'مجاني' }}</span>
          <RouterLink to="/admin/members" class="lnk">ملف العضو ‹</RouterLink>
        </header>
        <div ref="list" class="msgs">
          <p v-if="!thread.messages.length" class="muted pad">مفيش رسايل لسه — ابدأ انت.</p>
          <div v-for="x in thread.messages" :key="x.id" class="msg" :class="x.from">
            <p>{{ x.body }}</p>
            <span class="t">{{ x.from === 'coach' ? x.coach + ' · ' : '' }}{{ time(x.at) }}<template v-if="x.from === 'coach'"> · {{ x.read ? 'اتقرت ✓✓' : '✓' }}</template></span>
          </div>
        </div>
        <form class="composer" @submit.prevent="reply">
          <textarea v-model="draft" rows="2" maxlength="2000" placeholder="ردّك على المشترك… (Enter يبعت، Shift+Enter سطر جديد)" @keydown="onKey"></textarea>
          <button class="btn btn-primary btn-sm" :disabled="!draft.trim() || sending">ابعت</button>
        </form>
      </section>
      <section v-else class="conv empty"><p class="muted">اختار مشترك من القايمة.</p></section>

      <!-- member snapshot + check-ins -->
      <aside v-if="thread && m" class="snap">
        <div class="card">
          <h3>نظامه</h3>
          <template v-if="m.nutrition">
            <p><b>{{ diet.emoji }} {{ diet.name }}</b> · {{ goalLabel[m.nutrition.input.goal] }}</p>
            <div class="macro">
              <span><b class="num">{{ m.nutrition.kcal }}</b> سعرة</span>
              <span><b class="num">{{ m.nutrition.protein }}</b> بروتين</span>
              <span><b class="num">{{ m.nutrition.carbs }}</b> كارب</span>
              <span><b class="num">{{ m.nutrition.fat }}</b> دهون</span>
            </div>
            <small class="muted">بدأه <span class="num">{{ m.nutrition.since }}</span> · <span class="num">{{ m.nutrition.input.weight }}</span> كجم · <span class="num">{{ m.nutrition.input.height }}</span> سم · <span class="num">{{ m.nutrition.input.age }}</span> سنة</small>
          </template>
          <p v-else class="muted">لسه ماختارش نظام من الحاسبة.</p>
        </div>
        <div class="card">
          <h3>آخر 7 أيام</h3>
          <div class="wk">
            <div v-for="d in m.week" :key="d.date" class="wd">
              <i :class="{ on: d.workout }" title="تمرين">🏋️</i>
              <span class="num">{{ d.meals }}</span>
              <span class="num w">{{ d.water }}</span>
              <small>{{ wd(d.date) }}</small>
            </div>
          </div>
          <small class="muted">الصف التاني: وجبات علّم عليها · التالت: كوبايات مية</small>
        </div>
        <div class="card">
          <h3>الوزن <small v-if="wDiff !== null" class="muted">(<span class="num">{{ wDiff > 0 ? '+' : '' }}{{ wDiff }}</span> كجم)</small></h3>
          <p v-if="!m.weights.length" class="muted">مفيش تسجيلات.</p>
          <div v-else class="wl"><span v-for="w in m.weights.slice(-6)" :key="w.date"><b class="num">{{ w.kg }}</b><small class="num">{{ w.date.slice(5) }}</small></span></div>
        </div>
        <div class="card">
          <h3>المتابعات</h3>
          <p v-if="!thread.checkins.length" class="muted">مفيش متابعات.</p>
          <article v-for="c in thread.checkins" :key="c.id" class="ck" :class="{ pend: c.source === 'member' && !c.reviewed }">
            <header>
              <b class="num">{{ c.date }}</b>
              <span v-if="c.source === 'member' && !c.reviewed" class="wait">مستنية ردك</span>
              <span v-else class="muted sm">{{ c.source === 'member' ? 'من المشترك' : 'سجّلها الكوتش' }}</span>
            </header>
            <div class="nums">
              <span v-if="c.weight_kg">وزن <b class="num">{{ c.weight_kg }}</b></span>
              <span v-if="c.waist_cm">وسط <b class="num">{{ c.waist_cm }}</b></span>
              <span v-if="c.diet_adherence">أكل <b class="num">{{ c.diet_adherence }}/10</b></span>
              <span v-if="c.workout_adherence">تمرين <b class="num">{{ c.workout_adherence }}/10</b></span>
              <span v-if="c.sleep_hours">نوم <b class="num">{{ c.sleep_hours }}</b></span>
              <span v-if="c.energy_level">طاقة <b class="num">{{ c.energy_level }}/5</b></span>
            </div>
            <p v-if="c.member_feedback" class="fb">«{{ c.member_feedback }}»</p>
            <template v-if="!rev[c.id]">
              <p v-if="c.coach_notes" class="cn"><small>ملاحظاتك:</small> {{ c.coach_notes }}</p>
              <p v-if="c.plan_adjustments" class="cn adj"><small>التعديل:</small> {{ c.plan_adjustments }}</p>
              <button type="button" class="btn btn-sm" :class="c.reviewed ? 'btn-ghost' : 'btn-primary'" @click="startReview(c)">{{ c.reviewed ? 'عدّل الرد' : 'رد على المتابعة' }}</button>
            </template>
            <form v-else class="rf" @submit.prevent="saveReview(c)">
              <textarea v-model="rev[c.id].coach_notes" class="input" rows="3" placeholder="ملاحظاتك للمشترك (هيشوفها)"></textarea>
              <textarea v-model="rev[c.id].plan_adjustments" class="input" rows="2" placeholder="تعديل على النظام — مثلًا: قلّل الرز لنص كوباية، زوّد المية لـ 3 لتر"></textarea>
              <div class="r2">
                <select v-model="rev[c.id].status" class="input"><option v-for="s in STATUS" :key="s.id" :value="s.id">{{ s.label }}</option></select>
                <input v-model="rev[c.id].next_followup_date" type="date" class="input" aria-label="المتابعة الجاية" />
              </div>
              <small v-for="(e, k) in rev[c.id].errs" :key="k" class="err">{{ e }}</small>
              <div class="r2"><button class="btn btn-primary btn-sm">ابعت الرد</button><button type="button" class="btn btn-ghost btn-sm" @click="delete rev[c.id]">إلغاء</button></div>
            </form>
          </article>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.adm { padding-block: 24px 70px; display: grid; gap: 16px; max-width: 1360px; }
.top h1 { font-size: 2rem; }
.box { display: grid; gap: 12px; grid-template-columns: 1fr; }
@media (min-width: 1100px) { .box { grid-template-columns: 280px minmax(0, 1fr) 340px; align-items: start; } }
@media (min-width: 760px) and (max-width: 1099px) { .box { grid-template-columns: 260px minmax(0, 1fr); } .snap { grid-column: 1 / -1; } }
@media (max-width: 759px) { .threads.hide { display: none; } .conv.empty { display: none; } }
.pad { padding: 16px 4px; }
.threads { display: grid; gap: 6px; align-content: start; max-height: 76vh; overflow-y: auto; }
.th { display: flex; gap: 10px; align-items: center; padding: 10px; border-radius: 14px; background: #131313; border: 1px solid #222; text-align: start; }
.th.on { border-color: var(--orange); background: #1a1410; }
.av { width: 38px; height: 38px; flex: none; border-radius: 12px; background: #262626; display: grid; place-items: center; font-family: var(--f-head); font-size: 1.15rem; }
.tx { flex: 1; min-width: 0; display: grid; }
.tx b { font-size: .92rem; }
.tx small { font-size: .76rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bd { display: grid; gap: 3px; justify-items: end; }
.u { font-style: normal; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 99px; background: var(--orange); color: #0a0a0a; font-size: .9rem; line-height: 20px; text-align: center; }
.c { font-style: normal; }
.conv { display: grid; grid-template-rows: auto 1fr auto; height: 76vh; border-radius: 20px; background: #0f0f0f; border: 1px solid #222; overflow: hidden; }
.conv.empty { place-items: center; grid-template-rows: 1fr; }
.ch { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid #222; }
.ch > div { display: grid; flex: 1; min-width: 0; }
.ch small { justify-self: start; }
.back { font-size: 1.6rem; width: 32px; display: none; }
@media (max-width: 759px) { .back { display: block; } }
.tag { font-size: .72rem; font-weight: 900; padding: 3px 10px; border-radius: 99px; background: #222; }
.lnk { font-size: .8rem; font-weight: 800; color: var(--orange); }
.msgs { overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 6px; }
.msg { max-width: 76%; padding: 9px 12px 6px; border-radius: 16px; display: grid; gap: 2px; }
.msg p { white-space: pre-wrap; line-height: 1.6; overflow-wrap: anywhere; }
.msg .t { font-size: .66rem; color: var(--muted); justify-self: end; }
.msg.member { align-self: flex-start; background: #1c1c1c; border: 1px solid #2a2a2a; }
.msg.coach { align-self: flex-end; background: var(--orange); color: #0a0a0a; }
.msg.coach .t { color: rgba(0,0,0,.6); }
.composer { display: flex; gap: 8px; align-items: flex-end; padding: 10px; border-top: 1px solid #222; }
.composer textarea { flex: 1; resize: none; background: #161616; border: 1px solid #2a2a2a; border-radius: 12px; color: #fff; font: inherit; padding: 10px; }
.snap { display: grid; gap: 10px; align-content: start; max-height: 76vh; overflow-y: auto; }
.card { display: grid; gap: 8px; padding: 14px; border-radius: 16px; background: #131313; border: 1px solid #222; }
.card h3 { font-size: 1.1rem; }
.macro { display: flex; gap: 10px; flex-wrap: wrap; font-size: .76rem; color: var(--muted); font-weight: 700; }
.macro b { color: var(--amber); font-size: 1.15rem; }
.wk { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; }
.wd { display: grid; gap: 2px; justify-items: center; padding: 6px 0; border-radius: 10px; background: #181818; }
.wd i { font-style: normal; filter: grayscale(1); opacity: .25; }
.wd i.on { filter: none; opacity: 1; }
.wd .num { font-size: 1rem; color: var(--amber); }
.wd .w { color: #4fc3ff; }
.wd small { font-size: .7rem; color: var(--muted); font-weight: 800; }
.wl { display: flex; gap: 10px; flex-wrap: wrap; }
.wl span { display: grid; }
.wl b { font-size: 1.2rem; }
.wl small { font-size: .8rem; color: var(--muted); }
.ck { display: grid; gap: 6px; padding: 10px; border-radius: 12px; background: #181818; border: 1px solid #262626; }
.ck.pend { border-color: rgba(255,176,0,.5); }
.ck header { display: flex; justify-content: space-between; align-items: center; }
.sm { font-size: .72rem; }
.wait { font-size: .7rem; font-weight: 900; padding: 2px 8px; border-radius: 99px; background: rgba(255,176,0,.15); color: var(--amber); }
.nums { display: flex; gap: 10px; flex-wrap: wrap; font-size: .74rem; color: var(--muted); }
.nums b { color: #fff; font-size: 1rem; }
.fb { font-size: .84rem; }
.cn { font-size: .84rem; padding: 6px 10px; border-radius: 10px; background: #111; border-inline-start: 3px solid var(--orange); }
.cn.adj { border-color: var(--amber); }
.cn small { font-weight: 900; color: var(--muted); }
.rf { display: grid; gap: 6px; }
.rf textarea { min-height: 0; resize: vertical; line-height: 1.6; padding-top: 8px; }
.r2 { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.err { color: var(--danger); font-size: .76rem; }
</style>
