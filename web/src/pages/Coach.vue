<script setup>
// Member ↔ coach: chat, weekly check-in, and the coach's notes/adjustments history.
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MAppBar from '../mobile/MAppBar.vue'
import MSeg from '../mobile/MSeg.vue'
import { MOBILE } from '../mobile/device.js'
import { store, coachApi, loadMe, toast } from '../stores/app.js'
import { fieldErrors } from '../api.js'
import { ago, daysAgo } from '../member.js'

const route = useRoute()
const router = useRouter()
const tab = ref(['chat', 'checkin', 'notes'].includes(route.query.tab) ? route.query.tab : 'chat')
watch(tab, (t) => router.replace({ query: t === 'chat' ? {} : { tab: t } }))
const tabs = [{ id: 'chat', label: 'المحادثة' }, { id: 'checkin', label: 'متابعة الأسبوع' }, { id: 'notes', label: 'ملاحظات الكوتش' }]

const ready = ref(false)
const access = ref(store.coach.access)
const messages = ref([])
const checkins = ref([])

async function load(quiet = false) {
  try {
    const r = await coachApi.load()
    access.value = r.access
    if (r.access) {
      const grew = r.messages.length !== messages.value.length
      messages.value = r.messages
      checkins.value = r.checkins
      if (grew) scrollDown()
      if (store.coach.unread) store.coach.unread = 0
    }
  } catch (e) { if (!quiet) toast(e.message, 'err') }
  ready.value = true
}

/* chat */
const list = ref(null)
const draft = ref('')
const sending = ref(false)
function scrollDown() { nextTick(() => { if (list.value) list.value.scrollTop = list.value.scrollHeight }) }
async function send(text = draft.value) {
  const body = text.trim()
  if (!body || sending.value) return
  sending.value = true
  const tmp = { id: 'tmp' + Date.now(), from: 'member', body, at: new Date().toISOString(), pending: true }
  messages.value.push(tmp)
  draft.value = ''
  scrollDown()
  try { messages.value = (await coachApi.send(body)).messages; scrollDown() } catch (e) {
    messages.value = messages.value.filter((m) => m !== tmp)
    draft.value = body
    toast(e.message, 'err')
  } finally { sending.value = false }
}
function onKey(e) { if (e.key === 'Enter' && !e.shiftKey && !MOBILE) { e.preventDefault(); send() } }
const quick = ['ينفع أبدّل وجبة بحاجة تانية؟', 'مش قادر ألتزم بالأكل، أعمل إيه؟', 'التمرين ده بيوجعني في مكان معيّن', 'الوزن واقف بقاله أسبوع']
const grouped = computed(() => {
  const out = []
  let last = ''
  for (const m of messages.value) {
    const d = m.at.slice(0, 10)
    if (d !== last) { out.push({ sep: true, id: 's' + d, label: daysAgo(m.at) > 10 ? d : ago(m.at) }); last = d }
    out.push(m)
  }
  return out
})
const time = (iso) => new Date(iso).toLocaleTimeString('ar-EG', { hour: 'numeric', minute: '2-digit' })

/* check-in */
const lastW = computed(() => store.weights.at(-1)?.kg)
const f = reactive({ weight_kg: '', waist_cm: '', chest_cm: '', arm_cm: '', hips_cm: '', diet_adherence: 7, workout_adherence: 7, sleep_hours: '', energy_level: 3, member_feedback: '' })
const showMeas = ref(false)
const errs = ref({})
const saving = ref(false)
const lastCheckin = computed(() => checkins.value.find((c) => c.source === 'member'))
const energy = ['😵', '😮‍💨', '😐', '🙂', '💪']
async function submitCheckin() {
  errs.value = {}
  const body = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '' && v !== null))
  if (!body.weight_kg) return (errs.value = { weight_kg: 'اكتب وزنك النهارده' })
  saving.value = true
  try {
    await coachApi.checkin(body)
    toast('اتبعتت للكوتش ✓ هيرد عليك قريب')
    Object.assign(f, { member_feedback: '' })
    await Promise.all([load(true), loadMe()])
    tab.value = 'notes'
  } catch (e) { errs.value = fieldErrors(e); toast(e.message, 'err') } finally { saving.value = false }
}

let timer
onMounted(async () => {
  await load()
  f.weight_kg = lastW.value ?? ''
  timer = setInterval(() => { if (document.visibilityState === 'visible' && access.value) load(true) }, 20000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="coach" :class="{ mob: MOBILE, 'm-screen': MOBILE, 'with-tabs': MOBILE }">
    <MAppBar v-if="MOBILE" title="الكوتش" :back="false" />

    <div :class="MOBILE ? 'm-pad' : 'wrap'" class="in">
      <header v-if="!MOBILE" class="ph">
        <div>
          <span class="kicker">متابعة شخصية</span>
          <h1>الكوتش <span class="hl">معاك</span></h1>
        </div>
      </header>

      <div v-if="!ready" class="loading">بنجيب الرسايل…</div>

      <!-- locked -->
      <section v-else-if="!access" class="locked">
        <img src="/img/coach-arms-640.webp" alt="الكوتش المقنّع" loading="lazy" />
        <div>
          <h2>كوتش حقيقي <span class="hl">معاك</span> خطوة بخطوة</h2>
          <ul>
            <li>اسأل عن أي حاجة في أكلك أو تمرينك وخد رد</li>
            <li>متابعة كل أسبوع: وزن، قياسات، التزام، نوم</li>
            <li>الكوتش يظبطلك النظام على حسب نتيجتك</li>
          </ul>
          <RouterLink to="/pricing" class="btn btn-primary">اشترك وافتح الكوتش</RouterLink>
          <small class="muted">متاحة مع الباقات المدفوعة.</small>
        </div>
      </section>

      <template v-else>
        <MSeg v-if="MOBILE" v-model="tab" :options="tabs" class="seg" />

        <div class="cols">
          <!-- chat -->
          <section v-show="!MOBILE || tab === 'chat'" class="chat">
            <div ref="list" class="msgs" aria-live="polite">
              <div v-if="!messages.length" class="hello">
                <img src="/img/logo-icon.webp" alt="" />
                <b>أهلًا بيك 👊</b>
                <p>أنا الكوتش. اسألني في أي حاجة تخص أكلك أو تمرينك، وهرد عليك في أقرب وقت.</p>
                <small class="muted">الكوتش مش دكتور — لو عندك وجع شديد أو مستمر، اكشف عند دكتور.</small>
                <div class="quick">
                  <button v-for="q in quick" :key="q" type="button" @click="send(q)">{{ q }}</button>
                </div>
              </div>
              <template v-for="m in grouped" :key="m.id">
                <div v-if="m.sep" class="sep"><span>{{ m.label }}</span></div>
                <div v-else class="msg" :class="[m.from, { pending: m.pending }]">
                  <small v-if="m.from === 'coach'" class="by">{{ m.coach }}</small>
                  <p>{{ m.body }}</p>
                  <span class="t">{{ time(m.at) }}<template v-if="m.from === 'member'"> · {{ m.pending ? '…' : '✓' }}</template></span>
                </div>
              </template>
            </div>
            <form class="composer" @submit.prevent="send()">
              <textarea v-model="draft" rows="1" maxlength="2000" placeholder="اكتب رسالتك للكوتش…" aria-label="رسالتك" @keydown="onKey"></textarea>
              <button class="send" :disabled="!draft.trim() || sending" aria-label="ابعت">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 12 4 4l3 8-3 8z" /></svg>
              </button>
            </form>
            <small v-if="!MOBILE" class="muted disc">الكوتش مش دكتور — لو عندك وجع شديد أو مستمر، اكشف عند دكتور.</small>
          </section>

          <aside class="side">
            <!-- weekly check-in -->
            <section v-show="!MOBILE || tab === 'checkin'" class="card ci">
              <div class="c-h">
                <h2>متابعة الأسبوع</h2>
                <small v-if="lastCheckin" class="muted">آخر واحدة {{ ago(lastCheckin.date) }}</small>
              </div>
              <p class="muted lead">دقيقة واحدة كل أسبوع — والكوتش يشوف أرقامك ويظبطلك النظام.</p>
              <form class="ci-f" @submit.prevent="submitCheckin">
                <label class="fld">
                  <span>وزنك النهارده (كجم)</span>
                  <input v-model="f.weight_kg" class="input ltr" type="number" step="0.1" inputmode="decimal" :class="{ invalid: errs.weight_kg }" />
                  <small v-if="errs.weight_kg" class="err">{{ errs.weight_kg }}</small>
                </label>

                <button type="button" class="more" :aria-expanded="showMeas" @click="showMeas = !showMeas">{{ showMeas ? '−' : '+' }} قياسات بالشريط (اختياري)</button>
                <div v-if="showMeas" class="meas">
                  <label class="fld"><span>الوسط</span><input v-model="f.waist_cm" class="input ltr" type="number" step="0.5" inputmode="decimal" placeholder="سم" /></label>
                  <label class="fld"><span>الصدر</span><input v-model="f.chest_cm" class="input ltr" type="number" step="0.5" inputmode="decimal" placeholder="سم" /></label>
                  <label class="fld"><span>الدراع</span><input v-model="f.arm_cm" class="input ltr" type="number" step="0.5" inputmode="decimal" placeholder="سم" /></label>
                  <label class="fld"><span>الأرداف</span><input v-model="f.hips_cm" class="input ltr" type="number" step="0.5" inputmode="decimal" placeholder="سم" /></label>
                </div>

                <div class="fld">
                  <span>التزمت بالأكل قد إيه؟ <b class="num">{{ f.diet_adherence }}/10</b></span>
                  <input v-model.number="f.diet_adherence" type="range" min="1" max="10" class="rng" aria-label="التزام الأكل من 10" />
                </div>
                <div class="fld">
                  <span>التزمت بالتمرين قد إيه؟ <b class="num">{{ f.workout_adherence }}/10</b></span>
                  <input v-model.number="f.workout_adherence" type="range" min="1" max="10" class="rng" aria-label="التزام التمرين من 10" />
                </div>
                <div class="row2">
                  <label class="fld"><span>بتنام كام ساعة؟</span><input v-model="f.sleep_hours" class="input ltr" type="number" step="0.5" inputmode="decimal" placeholder="7" /></label>
                  <div class="fld">
                    <span>طاقتك</span>
                    <div class="en5" role="radiogroup" aria-label="طاقتك">
                      <button v-for="(e, i) in energy" :key="i" type="button" role="radio" :aria-checked="f.energy_level === i + 1" :class="{ on: f.energy_level === i + 1 }" @click="f.energy_level = i + 1">{{ e }}</button>
                    </div>
                  </div>
                </div>
                <label class="fld">
                  <span>عايز تقول للكوتش حاجة؟</span>
                  <textarea v-model="f.member_feedback" class="input" rows="3" maxlength="2000" placeholder="الأسبوع كان عامل إزاي؟ فيه حاجة صعبة؟"></textarea>
                </label>
                <button class="btn btn-primary btn-block" :disabled="saving">{{ saving ? 'بتتبعت…' : 'ابعت المتابعة' }}</button>
              </form>
            </section>

            <!-- coach notes history -->
            <section v-show="!MOBILE || tab === 'notes'" class="card hist">
              <div class="c-h"><h2>ملاحظات الكوتش</h2></div>
              <p v-if="!checkins.length" class="muted">لسه مفيش متابعات. ابعت أول متابعة والكوتش هيرد عليك هنا.</p>
              <article v-for="c in checkins" :key="c.id" class="ck">
                <header>
                  <b class="num">{{ c.date }}</b>
                  <span v-if="!c.reviewed" class="wait">مستني رد الكوتش</span>
                  <span v-else class="okb">اتراجعت ✓</span>
                </header>
                <div class="ck-n">
                  <span v-if="c.weight_kg"><b class="num">{{ c.weight_kg }}</b> كجم</span>
                  <span v-if="c.waist_cm">وسط <b class="num">{{ c.waist_cm }}</b></span>
                  <span v-if="c.diet_adherence">أكل <b class="num">{{ c.diet_adherence }}/10</b></span>
                  <span v-if="c.workout_adherence">تمرين <b class="num">{{ c.workout_adherence }}/10</b></span>
                </div>
                <p v-if="c.member_feedback" class="mine">«{{ c.member_feedback }}»</p>
                <div v-if="c.coach_notes" class="cn"><small>الكوتش</small><p>{{ c.coach_notes }}</p></div>
                <div v-if="c.plan_adjustments" class="cn adj"><small>تعديل على نظامك</small><p>{{ c.plan_adjustments }}</p></div>
                <small v-if="c.next" class="muted">المتابعة الجاية: <span class="num">{{ c.next }}</span></small>
              </article>
            </section>
          </aside>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.coach { padding-top: calc(var(--header) + 24px); padding-bottom: 40px; }
.coach.mob { padding-top: 0; padding-bottom: 0; }
.in { display: grid; gap: 14px; }
.ph h1 { font-size: clamp(2rem, 5vw, 3rem); line-height: 1.1; }
.loading { color: var(--muted); padding: 40px 0; text-align: center; }
.seg { position: sticky; top: calc(56px + env(safe-area-inset-top)); z-index: 5; }

.cols { display: grid; gap: 14px; }
@media (min-width: 960px) { .cols { grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); align-items: start; } }
.side { display: grid; gap: 14px; }
.card { display: grid; gap: 12px; padding: 16px; border-radius: 20px; background: #121212; border: 1px solid #232323; }
.c-h { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
.c-h h2 { font-size: 1.35rem; }

/* chat */
.chat { display: grid; grid-template-rows: 1fr auto auto; gap: 8px; height: min(72vh, 720px); background: #0e0e0e; border: 1px solid #222; border-radius: 22px; padding: 10px; position: sticky; top: calc(var(--header) + 16px); }
.mob .chat { grid-template-rows: 1fr auto; height: calc(100dvh - 56px - 70px - var(--m-tab) - env(safe-area-inset-top)); border: 0; padding: 0; background: none; position: static; margin-top: 4px; }
.msgs { overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding: 6px 4px; overscroll-behavior: contain; }
.hello { margin: auto 0; display: grid; justify-items: center; text-align: center; gap: 6px; padding: 20px 8px; }
.hello img { width: 64px; height: 64px; border-radius: 18px; }
.hello b { font-family: var(--f-head); font-weight: 400; font-size: 1.4rem; }
.hello p { color: var(--muted); font-size: .9rem; max-width: 34ch; }
.quick { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-top: 6px; }
.quick button { font-size: .8rem; font-weight: 700; padding: 8px 12px; border-radius: 99px; border: 1px solid #333; background: #161616; }
.quick button:hover { border-color: var(--orange); }
.sep { text-align: center; margin: 8px 0 2px; }
.sep span { font-size: .7rem; font-weight: 800; color: var(--muted); background: #1a1a1a; padding: 3px 10px; border-radius: 99px; }
.msg { max-width: 82%; padding: 9px 12px 6px; border-radius: 16px; display: grid; gap: 2px; }
.msg p { white-space: pre-wrap; font-size: .94rem; line-height: 1.6; overflow-wrap: anywhere; }
.msg .t { font-size: .66rem; color: var(--muted); justify-self: end; }
.msg .by { font-size: .7rem; font-weight: 900; color: var(--orange); }
.msg.coach { align-self: flex-start; background: #1c1c1c; border: 1px solid #2a2a2a; border-start-start-radius: 4px; }
.msg.member { align-self: flex-end; background: var(--orange); color: #0a0a0a; border-start-end-radius: 4px; }
.msg.member .t { color: rgba(0,0,0,.6); }
.msg.pending { opacity: .65; }
.composer { display: flex; gap: 8px; align-items: flex-end; padding: 6px; border-radius: 18px; background: #161616; border: 1px solid #2a2a2a; }
.composer textarea { flex: 1; resize: none; background: none; border: 0; color: #fff; font: inherit; font-size: .95rem; padding: 8px; max-height: 120px; field-sizing: content; }
.composer textarea:focus { outline: none; }
.send { width: 44px; height: 44px; border-radius: 14px; background: var(--orange); display: grid; place-items: center; flex: none; }
.send svg { width: 20px; height: 20px; fill: #0a0a0a; transform: scaleX(-1); }
.send:disabled { opacity: .4; }
.disc { font-size: .7rem; text-align: center; }

/* check-in */
.lead { font-size: .84rem; margin-top: -6px; }
.ci-f { display: grid; gap: 12px; }
.fld { display: grid; gap: 6px; }
.fld > span { font-size: .84rem; font-weight: 800; }
.fld b.num { color: var(--orange); font-size: 1.2rem; }
.err { color: var(--danger); font-size: .78rem; }
.more { justify-self: start; font-weight: 800; font-size: .84rem; color: var(--amber); }
.meas { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.row2 { display: grid; grid-template-columns: 1fr 1.3fr; gap: 10px; }
.rng { width: 100%; accent-color: var(--orange); height: 28px; }
.en5 { display: flex; gap: 4px; }
.en5 button { flex: 1; min-height: 44px; border-radius: 12px; border: 1.5px solid #2c2c2c; font-size: 1.15rem; filter: grayscale(1); opacity: .6; }
.en5 button.on { filter: none; opacity: 1; border-color: var(--orange); background: rgba(255,106,0,.12); }
textarea.input { min-height: 84px; resize: vertical; line-height: 1.6; padding-top: 10px; }

/* history */
.ck { display: grid; gap: 8px; padding: 12px; border-radius: 14px; background: #171717; border: 1px solid #252525; }
.ck header { display: flex; justify-content: space-between; align-items: center; }
.ck header .num { font-size: 1.15rem; }
.wait { font-size: .7rem; font-weight: 900; padding: 2px 9px; border-radius: 99px; background: rgba(255,176,0,.15); color: var(--amber); }
.okb { font-size: .7rem; font-weight: 900; color: var(--ok); }
.ck-n { display: flex; gap: 12px; flex-wrap: wrap; font-size: .78rem; color: var(--muted); font-weight: 700; }
.ck-n b { color: #fff; font-size: 1.05rem; }
.mine { font-size: .84rem; color: var(--muted); }
.cn { padding: 10px 12px; border-radius: 12px; background: #101010; border-inline-start: 3px solid var(--orange); }
.cn.adj { border-color: var(--amber); }
.cn small { font-size: .72rem; font-weight: 900; color: var(--orange); }
.cn.adj small { color: var(--amber); }
.cn p { font-size: .9rem; white-space: pre-line; }

/* locked */
.locked { display: grid; gap: 18px; align-items: center; padding: 20px; border-radius: 24px; background: linear-gradient(150deg, rgba(255,106,0,.16), #111 60%); border: 1px solid rgba(255,106,0,.35); }
.locked img { width: 100%; max-width: 280px; justify-self: center; }
.locked > div { display: grid; gap: 12px; justify-items: start; }
.locked h2 { font-size: clamp(1.6rem, 5vw, 2.4rem); line-height: 1.15; }
.locked ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; font-weight: 700; }
.locked li::before { content: '✓ '; color: var(--orange); font-weight: 900; }
@media (min-width: 800px) { .locked { grid-template-columns: 300px 1fr; padding: 32px; } }
</style>
