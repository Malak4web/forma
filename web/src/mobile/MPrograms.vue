<script setup>
import { ref, computed } from 'vue'
import MAppBar from './MAppBar.vue'
import MRail from './MRail.vue'
import { programs, meta } from '../data.js'
import { hasAccess, doneDays } from '../stores/app.js'

const filters = [
  { id: 'all', label: 'الكل' },
  { id: 'short', label: 'وقت قليل', test: (p) => parseInt(p.mins) <= 10 },
  { id: 'nojump', label: 'من غير نط', test: (p) => p.id === 'nojump' || p.id === 'back' },
  { id: 'pain', label: 'وجع', test: (p) => p.id === 'back' },
]
const f = ref('all')
const list = computed(() => programs.filter(filters.find((x) => x.id === f.value).test || (() => true)))
const state = (p) => {
  const d = doneDays(p.id).length
  if (hasAccess(p.id)) return d ? `كمّل · ${d}/${p.days}` : 'معاك — ابدأ'
  return d ? `جرّبت ${d} أيام` : `أول ${meta.freeDays} أيام ببلاش`
}
</script>

<template>
  <div class="m-screen with-tabs">
    <MAppBar title="البرامج" :back="false" />
    <div class="m-chips">
      <button v-for="x in filters" :key="x.id" :class="{ on: f === x.id }" @click="f = x.id"><span>{{ x.label }}</span></button>
    </div>
    <MRail :title="`${list.length} برامج`" :total="list.length" dots :gap="12" class="rail">
      <RouterLink v-for="p in list" :key="p.id" :to="`/programs/${p.id}`" class="pc">
        <img :src="p.img" alt="" />
        <div class="pc-b">
          <span v-if="p.tag" class="m-tag">{{ p.tag }}</span>
          <h2>{{ p.title }}</h2>
          <small class="amber sub">{{ p.sub }}</small>
          <div class="st">
            <span><b class="num">{{ p.days }}</b> يوم</span>
            <span><b class="num">{{ p.mins }}</b> د/يوم</span>
            <span><b class="num">0</b> أدوات</span>
          </div>
          <span class="btn" :class="hasAccess(p.id) ? 'btn-primary' : 'btn-primary'">{{ state(p) }} ‹</span>
        </div>
      </RouterLink>
    </MRail>
  </div>
</template>

<style scoped>
.rail :deep(.mrail-h) { padding-top: 14px; }
.pc { position: relative; width: calc(100vw - 64px); max-width: 340px; height: min(470px, calc(100dvh - 300px)); min-height: 380px; border-radius: 26px; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; isolation: isolate; border: 1px solid #2a2a2a; }
.pc > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 25%; z-index: -2; }
.pc::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(to top, #000 38%, transparent 78%); }
.pc-b { display: grid; gap: 6px; }
.pc-b .m-tag { justify-self: start; }
.pc-b h2 { font-size: 2.1rem; line-height: 1.1; }
.sub { font-weight: 800; }
.st { display: flex; gap: 16px; font-size: .8rem; color: var(--muted); }
.st b { font-size: 1.8rem; color: var(--orange); }
.pc-b .btn { margin-top: 6px; width: 100%; }
</style>
