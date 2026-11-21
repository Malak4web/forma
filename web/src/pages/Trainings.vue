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
const places = [{ id: 'all', label: 'أي مكان' }, { id: 'gym', label: 'جيم' }, { id: 'home', label: 'من البيت' }]
</script>

<template>
  <div class="sx" :class="{ mob: MOBILE }">
    <MAppBar v-if="MOBILE" title="أنظمة التدريب" fallback="/" />

    <header class="sx-hero">
      <picture>
        <source media="(min-width: 700px)" srcset="/img/banners/challenge-960.webp" />
        <img class="sx-hero-img" src="/img/banners/challenge-640.webp" alt="الكوتش المقنّع قدام الأهرامات" fetchpriority="high" />
      </picture>
      <div class="wrap sx-hero-in">
        <h1>أنظمة <span class="hl">التدريب</span><br />اختار اللي على مقاسك</h1>
        <p class="sx-lead">فول بادي، بوش بول ليجز، 5×5، HIIT… كل نظام برنامج <b>4 أسابيع</b> مكتوب يوم بيوم: التمارين والمجموعات والعدّات والراحة.</p>
        <div class="sx-count"><span class="num">{{ TRAININGS.length }}</span><span>نظام تدريب<br />جيم ومن البيت</span></div>
      </div>
    </header>

    <div class="sx-ticker" aria-hidden="true">
      <div class="sx-ticker-track"><span v-for="n in 2" :key="n"><b v-for="t in TRAININGS" :key="t.slug">{{ t.name }}</b></span></div>
    </div>

    <section class="wrap sx-toolbar">
      <h2>اختار <span class="hl">نظامك</span></h2>
      <div class="sx-filters">
        <div class="sx-chips" role="tablist" aria-label="الهدف">
          <button v-for="g in T_GOALS" :key="g.id" role="tab" :aria-selected="goal === g.id" :class="{ on: goal === g.id }" @click="goal = g.id"><span>{{ g.label }}</span></button>
        </div>
        <div class="sx-chips place" role="tablist" aria-label="المكان">
          <button v-for="p in places" :key="p.id" role="tab" :aria-selected="place === p.id" :class="{ on: place === p.id }" @click="place = p.id"><span>{{ p.label }}</span></button>
        </div>
      </div>
    </section>

    <section class="wrap sx-grid">
      <RouterLink v-for="t in list" :key="t.slug" :to="`/training/${t.slug}`" class="sx-card">
        <div class="sx-card-top">
          <span class="sx-en">{{ t.en }}</span>
          <span class="sx-tag"><span>{{ t.level }}</span></span>
        </div>
        <h3>{{ t.name }}</h3>
        <p>{{ t.tagline }}</p>
        <div class="sx-week" :aria-label="`${t.daysPerWeek} أيام تمرين في الأسبوع`"><i v-for="(on, i) in week(t)" :key="i" :class="{ on }"></i></div>
        <div class="sx-bar-l"><span><span class="num">{{ t.minutes }}</span>دقيقة</span><span>{{ PLACE[t.place] }}</span></div>
        <div class="sx-card-data">
          <span class="sx-big"><span class="num">{{ t.daysPerWeek }}</span><small>أيام في الأسبوع</small></span>
          <span class="sx-open">افتح النظام <SxIcon name="left" /></span>
        </div>
      </RouterLink>
      <p v-if="!list.length" class="sx-empty">مفيش نظام بالفلتر ده. جرّب «أي مكان».</p>
    </section>

    <section class="sx-band sx-help">
      <div class="wrap sx-band-in">
        <img src="/img/coach-arms-640.webp" alt="الكوتش المقنّع" loading="lazy" />
        <div class="sx-help-copy">
          <h2>لسه بادئ؟</h2>
          <p>ابدأ بالفول بادي لو بتروح جيم، أو الكاليسثنكس لو هتتمرن من البيت. 3 أيام في الأسبوع كفاية في الأول.</p>
          <RouterLink to="/training/full-body" class="btn btn-dark">افتح الفول بادي</RouterLink>
          <small>البرامج إرشادية. سخّن قبل أي تمرين، ولو عندك إصابة أو مرض مزمن اكشف عند دكتور قبل ما تبدأ.</small>
        </div>
      </div>
    </section>
  </div>
</template>
