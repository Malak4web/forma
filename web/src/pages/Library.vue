<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ReelCard from '../components/ReelCard.vue'
import { videos, categories } from '../data.js'

const route = useRoute()
const router = useRouter()
const cat = ref(route.query.cat || 'all')
const q = ref('')
watch(cat, (c) => router.replace({ query: c === 'all' ? {} : { cat: c } }))

const list = computed(() => {
  const s = q.value.trim().toLowerCase()
  return videos.filter((v) => (cat.value === 'all' || v.cat === cat.value) && (!s || v.title.toLowerCase().includes(s) || v.ar.includes(s)))
})
</script>

<template>
  <div>
    <header class="page-head">
      <div class="wrap">
        <nav class="crumbs"><RouterLink to="/">الرئيسية</RouterLink><span>/</span><span>المكتبة</span></nav>
        <h1>مكتبة <span class="hl">الكوتش</span></h1>
        <p>كل تمرين في فيديو أقل من دقيقة. اختار العضلة، دوس، واتمرن.</p>
        <label class="search">
          <span class="sr-only">دوّر على تمرين</span>
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
          <input v-model="q" class="input" type="search" placeholder="دوّر: Squat، كرش، كشري…" />
        </label>
      </div>
    </header>

    <section class="wrap body">
      <div class="filters no-scrollbar" role="tablist">
        <button v-for="c in categories" :key="c.id" role="tab" :aria-selected="cat === c.id" :class="{ on: cat === c.id }" @click="cat = c.id"><span>{{ c.label }}</span></button>
      </div>
      <p class="count muted"><span class="num">{{ list.length }}</span> فيديو</p>
      <div v-if="list.length" class="lib">
        <ReelCard v-for="v in list" :key="v.id" :v="v" />
      </div>
      <div v-else class="empty surface">
        <h3>مفيش حاجة بالاسم ده</h3>
        <p class="muted">جرّب كلمة تانية، أو شوف الكل.</p>
        <button class="btn btn-ghost btn-sm" @click="q = ''; cat = 'all'">اعرض الكل</button>
      </div>
    </section>
  </div>
</template>

<style scoped>
.search { position: relative; max-width: 460px; margin-top: 10px; }
.search svg { position: absolute; inset-inline-start: 14px; top: 50%; translate: 0 -50%; color: var(--muted); }
.search .input { padding-inline-start: 44px; }
.body { padding-top: 22px; }
.count { font-size: .85rem; margin-bottom: 12px; }
.lib { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
@media (min-width: 700px) { .lib { grid-template-columns: repeat(3, 1fr); gap: 14px; } }
@media (min-width: 1000px) { .lib { grid-template-columns: repeat(5, 1fr); } }
.empty { padding: 32px; display: grid; gap: 8px; justify-items: start; }
</style>
