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
  <div class="sx" :class="{ mob: MOBILE }">
    <MAppBar v-if="MOBILE" title="أنظمة الأكل" fallback="/" />

    <header class="wrap sx-head">
      <h1>أنظمة الأكل</h1>
      <p>كل نظام معروف بصفحته: بيشتغل إزاي، تاكل إيه وتبعد عن إيه، وخطة <span class="num">30</span> يوم أكل جاهزة تمشي عليها يوم بيوم.</p>
      <div class="sx-filters">
        <div class="sx-tabs" role="tablist" aria-label="الهدف">
          <button v-for="g in GOALS" :key="g.id" role="tab" :aria-selected="goal === g.id" :class="{ on: goal === g.id }" @click="goal = g.id">{{ g.label }}</button>
        </div>
      </div>
    </header>

    <ul class="wrap sx-list">
      <li v-for="d in list" :key="d.slug">
        <RouterLink :to="`/diets/${d.slug}`" class="sx-row">
          <div>
            <div class="sx-name"><h2>{{ d.name }}</h2><span class="sx-en">{{ d.en }}</span></div>
            <p class="sx-tag">{{ d.tagline }}</p>
          </div>
          <div v-if="d.macros" class="sx-mac" :aria-label="`كارب ${d.macros.c}% بروتين ${d.macros.p}% دهون ${d.macros.f}%`">
            <div class="sx-bar"><i class="c" :style="{ flex: d.macros.c || 0.001 }"></i><i class="p" :style="{ flex: d.macros.p }"></i><i class="f" :style="{ flex: d.macros.f }"></i></div>
            <div class="sx-mac-l"><span><span class="num">{{ d.macros.c }}%</span>كارب</span><span><span class="num">{{ d.macros.p }}%</span>بروتين</span><span><span class="num">{{ d.macros.f }}%</span>دهون</span></div>
          </div>
          <div v-else class="sx-mac-none">من غير عدّ سعرات · <span class="num">{{ d.eat.length }}</span> مجموعات مسموحة</div>
          <span class="sx-level"><b>الصعوبة</b>{{ d.level }}</span>
          <SxIcon name="left" class="sx-go" />
        </RouterLink>
      </li>
    </ul>
    <p v-if="!list.length" class="wrap sx-empty">مفيش نظام للهدف ده.</p>

    <section class="wrap sx-help">
      <div>
        <h3>مش عارف تختار؟</h3>
        <p>احسب سعراتك الأول، والحاسبة هتقترحلك نظام على قد هدفك وتظبط الكميات عليك.</p>
      </div>
      <RouterLink to="/calculator" class="btn btn-primary btn-sm">احسب سعراتك</RouterLink>
    </section>
    <p class="wrap sx-fine">الأرقام تقريبية ومحسوبة على حوالي <span class="num">1800</span> سعرة في اليوم. لو عندك مرض مزمن أو حامل، اكشف عند دكتور قبل ما تبدأ أي نظام.</p>
  </div>
</template>
