<script setup>
import { ref, computed } from 'vue'
import MAppBar from '../mobile/MAppBar.vue'
import SxIcon from '../components/SxIcon.vue'
import { MOBILE } from '../mobile/device.js'
import { TRAININGS, T_GOALS, PLACE } from '../training/data.js'

const goal = ref('all')
const place = ref('all')
const list = computed(() => TRAININGS.filter((t) => (goal.value === 'all' || t.goal.includes(goal.value)) && (place.value === 'all' || t.place === place.value)))
const week = (t) => t.split.map((k) => k !== 'rest')
</script>

<template>
  <div class="sx" :class="{ mob: MOBILE }">
    <MAppBar v-if="MOBILE" title="أنظمة التدريب" fallback="/" />

    <header class="wrap sx-head">
      <h1>أنظمة التدريب</h1>
      <p>الأنظمة المشهورة، كل واحد برنامج <span class="num">4</span> أسابيع مكتوب يوم بيوم: التمارين والمجموعات والعدّات والراحة.</p>
      <div class="sx-filters">
        <div class="sx-tabs" role="tablist" aria-label="الهدف">
          <button v-for="g in T_GOALS" :key="g.id" role="tab" :aria-selected="goal === g.id" :class="{ on: goal === g.id }" @click="goal = g.id">{{ g.label }}</button>
        </div>
        <div class="sx-tabs small" role="tablist" aria-label="المكان">
          <button :class="{ on: place === 'all' }" :aria-selected="place === 'all'" role="tab" @click="place = 'all'">أي مكان</button>
          <button :class="{ on: place === 'gym' }" :aria-selected="place === 'gym'" role="tab" @click="place = 'gym'">جيم</button>
          <button :class="{ on: place === 'home' }" :aria-selected="place === 'home'" role="tab" @click="place = 'home'">من البيت</button>
        </div>
      </div>
    </header>

    <ul class="wrap sx-list">
      <li v-for="t in list" :key="t.slug">
        <RouterLink :to="`/training/${t.slug}`" class="sx-row">
          <div>
            <div class="sx-name"><h2>{{ t.name }}</h2><span class="sx-en">{{ t.en }}</span></div>
            <p class="sx-tag">{{ t.tagline }}</p>
          </div>
          <div class="sx-week" :aria-label="`${t.daysPerWeek} أيام تمرين في الأسبوع`">
            <div class="sx-week-c"><i v-for="(on, i) in week(t)" :key="i" :class="{ on }"></i></div>
            <div class="sx-meta"><span><span class="num">{{ t.daysPerWeek }}</span> أيام/أسبوع</span><span><span class="num">{{ t.minutes }}</span> دقيقة</span><span>{{ PLACE[t.place] }}</span></div>
          </div>
          <span class="sx-level"><b>المستوى</b>{{ t.level }}</span>
          <SxIcon name="left" class="sx-go" />
        </RouterLink>
      </li>
    </ul>
    <p v-if="!list.length" class="wrap sx-empty">مفيش نظام بالفلتر ده. جرّب «أي مكان».</p>

    <section class="wrap sx-help">
      <div>
        <h3>لسه بادئ؟</h3>
        <p>ابدأ بالفول بادي لو بتروح جيم، أو الكاليسثنكس لو هتتمرن من البيت.</p>
      </div>
      <RouterLink to="/training/full-body" class="btn btn-primary btn-sm">افتح الفول بادي</RouterLink>
    </section>
    <p class="wrap sx-fine">البرامج إرشادية. سخّن قبل أي تمرين، ولو عندك إصابة أو مرض مزمن اكشف عند دكتور قبل ما تبدأ.</p>
  </div>
</template>
