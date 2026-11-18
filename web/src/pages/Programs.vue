<script setup>
import { programs, planById } from '../data.js'
import { hasAccess, doneDays } from '../stores/app.js'
</script>

<template>
  <div>
    <header class="page-head">
      <div class="wrap">
        <nav class="crumbs"><RouterLink to="/">الرئيسية</RouterLink><span>/</span><span>البرامج</span></nav>
        <h1>برامج <span class="hl">جاهزة</span></h1>
        <p>كل برنامج يوم بيوم: 3 تمارين، مؤقّت، وتعلّم على اليوم لما تخلّصه. أول <span class="num amber">3</span> أيام من أي برنامج ببلاش.</p>
      </div>
    </header>
    <section class="wrap list">
      <RouterLink v-for="p in programs" :key="p.id" :to="'/programs/' + p.id" class="card">
        <div class="img"><img :src="p.img" alt="" loading="lazy" /><span v-if="p.tag" class="badge">{{ p.tag }}</span></div>
        <div class="body">
          <h2>{{ p.title }}</h2>
          <p class="sub">{{ p.sub }}</p>
          <p class="pitch">{{ p.pitch }}</p>
          <div class="meta">
            <span><b class="num">{{ p.days }}</b> يوم</span>
            <span><b class="num">{{ p.mins }}</b> دقيقة/يوم</span>
            <span>{{ p.level }}</span>
          </div>
          <div class="foot">
            <span v-if="hasAccess(p.id)" class="own">✓ معاك · خلّصت <span class="num">{{ doneDays(p.id).length }}/{{ p.days }}</span></span>
            <span v-else class="muted">ضمن باقة «{{ planById(p.plan).name }}»</span>
            <span class="go">افتح ←</span>
          </div>
        </div>
      </RouterLink>
    </section>
  </div>
</template>

<style scoped>
.list { display: grid; gap: 16px; padding-top: 28px; }
@media (min-width: 900px) { .list { grid-template-columns: 1fr 1fr; } }
.card { display: grid; grid-template-columns: 120px 1fr; background: #121212; border: 1px solid #222; border-radius: 18px; overflow: hidden; transition: border-color .2s, transform .2s; }
.card:hover { border-color: var(--orange); transform: translateY(-2px); }
.img { position: relative; }
.img img { width: 100%; height: 100%; object-fit: cover; }
.img .badge { position: absolute; top: 10px; inset-inline-start: 10px; }
.body { padding: 16px; display: grid; gap: 6px; align-content: start; }
.body h2 { font-size: 1.6rem; }
.sub { color: var(--amber); font-weight: 800; font-size: .9rem; }
.pitch { color: var(--muted); font-size: .9rem; }
.meta { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: .85rem; font-weight: 700; margin-top: 4px; }
.meta b { color: var(--orange); font-size: 1.2rem; }
.foot { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 8px; padding-top: 10px; border-top: 1px solid #222; font-size: .85rem; }
.own { color: var(--ok); font-weight: 800; }
.go { color: var(--orange); font-family: var(--f-head); font-size: 1.1rem; }
@media (min-width: 560px) { .card { grid-template-columns: 180px 1fr; } }
</style>
