<script setup>
import { ref, computed } from 'vue'
import MAppBar from '../mobile/MAppBar.vue'
import SxIcon from '../components/SxIcon.vue'
import { MOBILE } from '../mobile/device.js'
import { DIETS, GOALS } from '../diets/data.js'

const goal = ref('all')
const list = computed(() => (goal.value === 'all' ? DIETS : DIETS.filter((d) => d.goal.includes(goal.value))))
</script>

<template>
  <div class="sx diet-world" :class="{ mob: MOBILE }">
    <MAppBar v-if="MOBILE" title="أنظمة الأكل" fallback="/" />

    <header class="sx-hero">
      <picture>
        <source media="(min-width: 700px)" srcset="/img/banners/calculator-960.webp" />
        <img class="sx-hero-img" src="/img/banners/calculator-640.webp" alt="الكوتش المقنّع شايل طبق أكل صحي" fetchpriority="high" />
      </picture>
      <div class="wrap sx-hero-in">
        <h1>أنظمة <span class="hl">الأكل</span><br />كلها في مكان واحد</h1>
        <p class="sx-lead">كيتو، صيام متقطع، الطيبات، لو كارب… كل نظام بيشرحلك بيشتغل إزاي، تاكل إيه وتبعد عن إيه، و<b>خطة 30 يوم</b> جاهزة تمشي عليها.</p>
        <div class="sx-count"><span class="num">{{ DIETS.length }}</span><span>نظام أكل<br />بخطة كاملة</span></div>
      </div>
    </header>

    <div class="sx-ticker" aria-hidden="true">
      <div class="sx-ticker-track"><span v-for="n in 2" :key="n"><b v-for="d in DIETS" :key="d.slug">{{ d.name }}</b></span></div>
    </div>

    <section class="wrap sx-toolbar">
      <h2>اختار <span class="hl">نظامك</span></h2>
      <div class="sx-filters">
        <div class="sx-chips" role="tablist" aria-label="الهدف">
          <button v-for="g in GOALS" :key="g.id" role="tab" :aria-selected="goal === g.id" :class="{ on: goal === g.id }" @click="goal = g.id"><span>{{ g.label }}</span></button>
        </div>
      </div>
    </section>

    <section class="wrap sx-grid">
      <RouterLink v-for="d in list" :key="d.slug" :to="`/diets/${d.slug}`" class="sx-card">
        <div class="sx-card-top">
          <span class="sx-en">{{ d.en }}</span>
          <span class="sx-tag"><span>{{ d.level }}</span></span>
        </div>
        <h3>{{ d.name }}</h3>
        <p>{{ d.tagline }}</p>
        <template v-if="d.macros">
          <div class="sx-bar" aria-hidden="true"><i class="c" :style="{ flex: d.macros.c || 0.001 }"></i><i class="p" :style="{ flex: d.macros.p }"></i><i class="f" :style="{ flex: d.macros.f }"></i></div>
          <div class="sx-bar-l">
            <span><i style="background: #fff"></i><span class="num">{{ d.macros.c }}%</span>كارب</span>
            <span><i style="background: var(--amber)"></i><span class="num">{{ d.macros.p }}%</span>بروتين</span>
            <span><i style="background: var(--orange)"></i><span class="num">{{ d.macros.f }}%</span>دهون</span>
          </div>
        </template>
        <div class="sx-card-data">
          <span class="sx-big"><span class="num">30</span><small>يوم أكل جاهز</small></span>
          <span class="sx-open">افتح النظام <SxIcon name="left" /></span>
        </div>
      </RouterLink>
    </section>
    <p v-if="!list.length" class="wrap sx-empty">مفيش نظام للهدف ده.</p>

    <section class="sx-band sx-help">
      <div class="wrap sx-band-in">
        <img src="/img/coach-arms-640.webp" alt="الكوتش المقنّع" loading="lazy" />
        <div class="sx-help-copy">
          <h2>مش عارف تختار؟</h2>
          <p>احسب سعراتك الأول، والحاسبة هتقترحلك نظام على قد هدفك وتظبط الكميات على جسمك.</p>
          <RouterLink to="/calculator" class="btn btn-dark">احسب سعراتك ببلاش</RouterLink>
          <small>الأرقام تقريبية ومحسوبة على حوالي <span class="num">1800</span> سعرة. لو عندك مرض مزمن أو حامل، اكشف عند دكتور قبل ما تبدأ أي نظام.</small>
        </div>
      </div>
    </section>
  </div>
</template>
