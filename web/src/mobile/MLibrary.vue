<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MAppBar from './MAppBar.vue'
import MRail from './MRail.vue'
import { videos, categories, fmt } from '../data.js'

const route = useRoute()
const router = useRouter()
const cat = ref(typeof route.query.cat === 'string' ? route.query.cat : 'all')
const q = ref('')
watch(cat, (c) => router.replace({ query: c === 'all' ? {} : { cat: c } }))

const list = computed(() => {
  const s = q.value.trim().toLowerCase()
  return videos.filter((v) => (cat.value === 'all' || v.cat === cat.value) && (!s || v.title.toLowerCase().includes(s) || v.ar.includes(s)))
})
// 6 per page (3×2) — swipe pages sideways instead of scrolling a long grid
const pages = computed(() => {
  const out = []
  for (let i = 0; i < list.value.length; i += 6) out.push(list.value.slice(i, i + 6))
  return out
})
const short = (t) => t.replace(/^(Mountain|Tabletop|Russian|Jumping)\s/, '')
</script>

<template>
  <div class="m-screen with-tabs">
    <MAppBar title="المكتبة" :back="false" />
    <div class="m-pad">
      <label class="search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
        <input v-model="q" class="m-input" type="search" placeholder="دوّر: Squat، كرش، كشري…" aria-label="دوّر على تمرين" />
      </label>
    </div>
    <div class="m-chips cats" role="tablist">
      <button v-for="c in categories" :key="c.id" role="tab" :aria-selected="cat === c.id" :class="{ on: cat === c.id }" @click="cat = c.id"><span>{{ c.label }}</span></button>
    </div>

    <MRail v-if="pages.length" :title="`${list.length} فيديو`" :total="pages.length" arrows dots :gap="16">
      <div v-for="(pg, i) in pages" :key="cat + q + i" class="page">
        <RouterLink v-for="v in pg" :key="v.id" :to="`/library/${v.id}`" class="m-reel">
          <img :src="v.img" :alt="v.title" loading="lazy" />
          <span class="d">{{ fmt(v.sec) }}</span>
          <span class="t"><b>{{ short(v.title) }}</b></span>
        </RouterLink>
      </div>
    </MRail>
    <div v-else class="m-empty">
      <p>مفيش حاجة بالاسم ده.</p>
      <button class="btn btn-ghost btn-sm" @click="q = ''; cat = 'all'">اعرض الكل</button>
    </div>
  </div>
</template>

<style scoped>
.search { position: relative; display: block; }
.search svg { position: absolute; inset-inline-start: 14px; top: 50%; translate: 0 -50%; width: 20px; height: 20px; fill: none; stroke: var(--muted); stroke-width: 2.2; }
.search .m-input { padding-inline-start: 44px; }
.cats { margin-top: 12px; }
.page { width: calc(100vw - 32px); display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; align-content: start; }
.page .m-reel { width: auto; }
</style>
