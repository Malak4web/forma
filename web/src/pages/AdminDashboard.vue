<script setup>
import { ref, onMounted } from 'vue'
import AdminNav from '../components/AdminNav.vue'
import { api } from '../api.js'
import { egp } from '../data.js'
import { toast } from '../stores/app.js'

const data = ref(null)
const loading = ref(true)

const statusLabels = {
  needs_attention: 'محتاج متابعة',
  delayed: 'متأخر',
  on_track: 'ماشي تمام',
  completed: 'تمت',
}
const goalLabels = { loss: 'تخسيس', cut: 'تنشيف', gain: 'زيادة عضل' }
const wa = (phone) => `https://wa.me/2${phone}`

async function load() {
  loading.value = true
  try {
    data.value = await api('/admin/dashboard')
  } catch (e) {
    toast(e.message, 'err')
  } finally {
    loading.value = false
  }
}
onMounted(load)
</script>

<template>
  <div class="wrap adm">
    <AdminNav />
    <header class="top">
      <div>
        <h1>لوحة التحكم</h1>
        <p class="muted">ملخص سريع لكل حاجة في فورمة دلوقتي.</p>
      </div>
      <button class="btn btn-ghost btn-sm" :disabled="loading" @click="load">تحديث</button>
    </header>

    <div v-if="loading" class="loading"><span class="spin" aria-label="بنحمّل"></span></div>

    <template v-else-if="data">
      <!-- KPI CARDS -->
      <section class="kpis">
        <RouterLink to="/admin/members" class="kpi">
          <span class="kl">الأعضاء</span>
          <b class="num">{{ data.kpis.members_total }}</b>
          <small class="muted">+<span class="num">{{ data.kpis.members_new_week }}</span> آخر 7 أيام</small>
        </RouterLink>
        <RouterLink to="/admin/subscriptions" class="kpi">
          <span class="kl">اشتراكات شغالة</span>
          <b class="num ok">{{ data.kpis.subscriptions_active }}</b>
          <small v-if="data.kpis.subscriptions_expiring" class="amber"><span class="num">{{ data.kpis.subscriptions_expiring }}</span> قربت تخلص</small>
          <small v-else class="muted">كله مستقر</small>
        </RouterLink>
        <RouterLink to="/admin/orders" class="kpi" :class="{ warn: data.kpis.orders_pending > 0 }">
          <span class="kl">طلبات بتتراجع</span>
          <b class="num" :class="data.kpis.orders_pending ? 'amber' : ''">{{ data.kpis.orders_pending }}</b>
          <small class="muted">مستنية تفعيل</small>
        </RouterLink>
        <RouterLink to="/admin/orders" class="kpi">
          <span class="kl">الإيرادات</span>
          <b class="num amber">{{ egp(data.kpis.revenue_total) }}</b>
          <small class="muted">الشهر ده: <span class="num">{{ egp(data.kpis.revenue_month) }}</span> ج.م</small>
        </RouterLink>
        <RouterLink to="/admin/followups" class="kpi" :class="{ warn: data.kpis.followups_due + data.kpis.followups_attention > 0 }">
          <span class="kl">متابعات مطلوبة</span>
          <b class="num" :class="data.kpis.followups_due ? 'amber' : ''">{{ data.kpis.followups_due }}</b>
          <small class="muted"><span class="num">{{ data.kpis.followups_attention }}</span> محتاج اهتمام / متأخر</small>
        </RouterLink>
        <RouterLink to="/admin/followups?tab=leads" class="kpi">
          <span class="kl">Leads جداد</span>
          <b class="num">{{ data.kpis.leads_new }}</b>
          <small class="muted">من إجمالي <span class="num">{{ data.kpis.leads_total }}</span></small>
        </RouterLink>
        <RouterLink to="/admin/exercises" class="kpi">
          <span class="kl">الفيديوهات والتمارين</span>
          <b class="num">{{ data.kpis.exercises_total }}</b>
          <small class="muted"><span class="num">{{ data.kpis.exercises_with_video }}</span> بفيديو مرفوع/مربوط</small>
        </RouterLink>
        <RouterLink to="/admin/programs" class="kpi">
          <span class="kl">البرامج</span>
          <b class="num">{{ data.kpis.programs_active }}</b>
          <small class="muted">من إجمالي <span class="num">{{ data.kpis.programs_total }}</span> برنامج</small>
        </RouterLink>
      </section>

      <!-- QUICK ACTIONS -->
      <section class="qgrid">
        <!-- Pending orders -->
        <div class="panel">
          <header class="ph">
            <h2>طلبات مستنية مراجعة</h2>
            <RouterLink to="/admin/orders" class="more-l">الكل ←</RouterLink>
          </header>
          <p v-if="!data.pending_orders.length" class="muted empty">مفيش طلبات معلّقة دلوقتي ✓</p>
          <div v-else class="list">
            <RouterLink v-for="o in data.pending_orders" :key="o.id" to="/admin/orders" class="row">
              <div>
                <b>{{ o.user?.name }}</b>
                <small class="muted">{{ o.plan_name }} · <span class="en">{{ o.id }}</span></small>
              </div>
              <b class="num amber">{{ egp(o.amount) }} ج.م</b>
            </RouterLink>
          </div>
        </div>

        <!-- Due follow-ups -->
        <div class="panel">
          <header class="ph">
            <h2>متابعات محتاجة عينك</h2>
            <RouterLink to="/admin/followups" class="more-l">الكل ←</RouterLink>
          </header>
          <p v-if="!data.due_followups.length" class="muted empty">كل المتابعات تمام دلوقتي ✓</p>
          <div v-else class="list">
            <div v-for="f in data.due_followups" :key="f.id" class="row">
              <div>
                <b>{{ f.user?.name }}</b>
                <small class="muted">
                  {{ statusLabels[f.status] || f.status }}
                  <template v-if="f.next_followup_date"> · ميعاد: <span class="num">{{ f.next_followup_date }}</span></template>
                  <template v-if="f.weight_kg"> · <span class="num">{{ f.weight_kg }}</span> كجم</template>
                </small>
              </div>
              <a v-if="f.user?.phone" :href="wa(f.user.phone)" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">واتساب</a>
            </div>
          </div>
        </div>

        <!-- Recent leads -->
        <div class="panel">
          <header class="ph">
            <h2>أحدث Leads (نظام الأكل)</h2>
            <RouterLink to="/admin/followups?tab=leads" class="more-l">الكل ←</RouterLink>
          </header>
          <p v-if="!data.recent_leads.length" class="muted empty">مفيش Leads جديدة دلوقتي.</p>
          <div v-else class="list">
            <div v-for="l in data.recent_leads" :key="l.id" class="row">
              <div>
                <b>{{ l.name }}</b>
                <small class="muted">
                  {{ goalLabels[l.goal] || 'عام' }}
                  <template v-if="l.kcal"> · <span class="num">{{ l.kcal }}</span> سعرة</template>
                </small>
              </div>
              <a :href="wa(l.phone)" target="_blank" rel="noopener" class="en wa-l">{{ l.phone }}</a>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.adm { padding-block: 24px 70px; display: grid; gap: 18px; max-width: 1120px; }
.top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.top h1 { font-size: 2.3rem; }
.kpis { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
@media (min-width: 760px) { .kpis { grid-template-columns: repeat(4, 1fr); gap: 12px; } }
.kpi {
  background: #141414;
  border: 1px solid #242424;
  border-radius: 16px;
  padding: 14px 16px;
  display: grid;
  gap: 4px;
  transition: border-color .15s, transform .15s;
}
.kpi:hover { border-color: var(--orange); transform: translateY(-1px); }
.kpi.warn { border-color: rgba(255, 176, 0, .45); }
.kl { font-size: .82rem; font-weight: 800; color: var(--muted); }
.kpi b { font-size: 2.2rem; line-height: 1.05; }
.kpi small { font-size: .78rem; }
.ok { color: var(--ok); }
.amber { color: var(--amber); }

.qgrid { display: grid; gap: 14px; }
@media (min-width: 900px) { .qgrid { grid-template-columns: repeat(3, 1fr); } }
.panel { background: #141414; border: 1px solid #242424; border-radius: 16px; padding: 16px; display: grid; gap: 10px; align-content: start; }
.ph { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.ph h2 { font-size: 1.25rem; }
.more-l { font-size: .85rem; font-weight: 800; color: var(--orange); }
.empty { font-size: .88rem; padding: 8px 0; }
.list { display: grid; gap: 8px; }
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  background: #0e0e0e;
  border: 1px solid #1f1f1f;
}
.row div { display: grid; gap: 2px; min-width: 0; }
.row b { font-size: .92rem; }
.row small { font-size: .78rem; }
.wa-l { color: var(--orange); font-size: .9rem; }

.loading { display: grid; place-items: center; padding: 48px; }
.spin { width: 32px; height: 32px; border: 3px solid #262626; border-top-color: var(--orange); border-radius: 50%; animation: sp .7s linear infinite; }
@keyframes sp { to { transform: rotate(360deg); } }
</style>
