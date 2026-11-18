<script setup>
import { computed, ref, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import ReelCard from '../components/ReelCard.vue'
import NotFound from './NotFound.vue'
import { videoById, videos, programs, categories, fmt } from '../data.js'
import { ui } from '../stores/app.js'

const route = useRoute()
const v = computed(() => videoById(route.params.id))
watchEffect(() => { if (v.value) { document.title = `${v.value.title} — ${v.value.ar} | فورمة`; ui.title = v.value.title } })
const related = computed(() => videos.filter((x) => x.cat === v.value?.cat && x.id !== v.value?.id).slice(0, 4))
const inPrograms = computed(() => programs.filter((p) => p.weeks.some((w) => w.pool.includes(v.value?.id))))
const catLabel = computed(() => categories.find((c) => c.id === v.value?.cat)?.label)
const playing = ref(false)
</script>

<template>
  <NotFound v-if="!v" />
  <div v-else>
    <section class="wrap ex">
      <nav class="crumbs"><RouterLink to="/library">المكتبة</RouterLink><span>/</span><RouterLink :to="{ path: '/library', query: { cat: v.cat } }">{{ catLabel }}</RouterLink></nav>
      <div class="grid">
        <div class="player">
          <img :src="v.img" :alt="v.title" />
          <button v-if="!playing" class="play" aria-label="شغّل الفيديو" @click="playing = true">▶</button>
          <span v-else class="ph">مكان مشغّل الفيديو — بيشتغل من غير صوت أوتوماتيك</span>
        </div>
        <div class="info">
          <span class="kicker">{{ catLabel }}</span>
          <h1 class="en">{{ v.title }}</h1>
          <p class="hook">{{ v.ar }}</p>
          <div class="meta">
            <span><b class="num">{{ fmt(v.sec) }}</b> دقيقة</span>
            <span v-if="v.level">{{ v.level }}</span>
            <span v-if="v.muscles">{{ v.muscles }}</span>
          </div>

          <div v-if="v.home" class="set">
            <div><b class="num">3</b><span>جولات</span></div>
            <div><b class="num">40</b><span>ثانية شغل</span></div>
            <div><b class="num">20</b><span>ثانية راحة</span></div>
          </div>

          <div v-if="v.tips" class="tips">
            <h2>خلي بالك من</h2>
            <ul><li v-for="t in v.tips" :key="t">{{ t }}</li></ul>
          </div>
          <p class="note">لو حسّيت وجع (مش مجرد تعب)، وقّف. ولو الوجع شديد أو مستمر، اكشف عند دكتور.</p>

          <div v-if="inPrograms.length" class="inprog">
            <h2>التمرين ده موجود في</h2>
            <RouterLink v-for="p in inPrograms" :key="p.id" :to="'/programs/' + p.id" class="pchip">{{ p.title }} ←</RouterLink>
          </div>
        </div>
      </div>
    </section>

    <section v-if="related.length" class="sec wrap">
      <div class="sec-head"><h2>تمارين <span class="hl">شبهه</span></h2></div>
      <div class="rel"><ReelCard v-for="r in related" :key="r.id" :v="r" /></div>
    </section>
  </div>
</template>

<style scoped>
.ex { padding-top: 20px; }
.grid { display: grid; gap: 24px; margin-top: 14px; }
@media (min-width: 900px) { .grid { grid-template-columns: 380px 1fr; gap: 48px; align-items: start; } .player { position: sticky; top: calc(var(--header) + 16px); } }
.player { position: relative; aspect-ratio: 9/16; max-height: 78svh; width: 100%; margin-inline: auto; border-radius: 18px; overflow: hidden; border: 2px solid #222; background: #000; }
.player img { width: 100%; height: 100%; object-fit: cover; }
.play { position: absolute; inset: 0; margin: auto; width: 80px; height: 80px; border-radius: 50%; background: var(--orange); color: #0a0a0a; font-size: 1.8rem; padding-inline-start: 5px; box-shadow: 0 0 0 10px rgba(255,106,0,.25); }
.ph { position: absolute; inset: auto 12px 12px; background: rgba(0,0,0,.75); border-radius: 10px; padding: 8px; text-align: center; font-size: .85rem; font-weight: 700; }
.info { display: grid; gap: 16px; align-content: start; }
.info h1 { font-size: clamp(3rem, 12vw, 4.6rem); line-height: .95; justify-self: start; }
.hook { font-family: var(--f-head); font-size: 1.5rem; }
.meta { display: flex; flex-wrap: wrap; gap: 8px; }
.meta span { background: #1a1a1a; border-radius: 999px; padding: 4px 12px; font-size: .85rem; font-weight: 700; }
.meta b { color: var(--amber); font-size: 1.1rem; }
.set { display: grid; grid-template-columns: repeat(3, 1fr); border: 2px solid #fff; border-radius: 4px; box-shadow: 6px 6px 0 var(--orange); background: #0f0f0f; }
.set div { display: grid; justify-items: center; padding: 14px 6px; }
.set div + div { border-inline-start: 1px solid #333; }
.set b { font-size: 2.6rem; line-height: 1; color: var(--orange); }
.set span { font-size: .8rem; font-weight: 700; color: var(--muted); }
.tips h2, .inprog h2 { font-size: 1.4rem; margin-bottom: 8px; }
.tips ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 8px; }
.tips li { display: flex; gap: 10px; align-items: baseline; }
.tips li::before { content: '▼'; color: var(--orange); font-size: .65rem; transform: rotate(90deg); }
.inprog { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.inprog h2 { width: 100%; }
.pchip { border: 1.5px solid var(--orange); color: var(--orange); border-radius: 10px; padding: 6px 12px; font-weight: 800; }
.pchip:hover { background: var(--orange); color: #0a0a0a; }
.rel { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
@media (min-width: 900px) { .rel { grid-template-columns: repeat(4, 1fr); gap: 14px; } }
</style>
