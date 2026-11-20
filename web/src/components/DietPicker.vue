<script setup>
// Pick the eating style the free plan is built on: FORMA balanced (default) or any diet page.
import { computed } from 'vue'
import { DIETS, BALANCED } from '../diets/data.js'

const props = defineProps({ modelValue: { type: String, default: 'balanced' }, goal: String, compact: Boolean })
const emit = defineEmits(['update:modelValue'])

const SUGGEST = { loss: ['intermittent-fasting', 'low-carb'], cut: ['high-protein', 'carb-cycling'], gain: ['high-protein', 'carb-cycling'] }

const list = computed(() => {
  const s = SUGGEST[props.goal] || []
  const sorted = [...DIETS].sort((a, b) => (s.includes(b.slug) ? 1 : 0) - (s.includes(a.slug) ? 1 : 0))
  return [BALANCED, ...sorted].map((d) => ({ ...d, sug: s.includes(d.slug) }))
})
</script>

<template>
  <div class="dp" :class="{ compact }" role="radiogroup" aria-label="النظام الغذائي">
    <button
      v-for="d in list" :key="d.slug" type="button" role="radio" :aria-checked="modelValue === d.slug"
      class="o" :class="{ on: modelValue === d.slug }" :style="{ '--da': d.theme.a }"
      @click="emit('update:modelValue', d.slug)"
    >
      <span class="e" aria-hidden="true">{{ d.emoji }}</span>
      <span class="x">
        <b>{{ d.name }} <i v-if="d.sug" class="sug">مقترح لهدفك</i></b>
        <small>{{ d.tagline }}</small>
      </span>
      <span class="r"></span>
    </button>
  </div>
</template>

<style scoped>
.dp { display: grid; gap: 8px; }
.o { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 16px; background: var(--surface, #141414); border: 1.5px solid #262626; text-align: start; transition: border-color .15s, background .15s; }
.o.on { border-color: var(--da); background: color-mix(in srgb, var(--da) 10%, #111); }
.e { width: 44px; height: 44px; flex: none; border-radius: 13px; display: grid; place-items: center; font-size: 1.35rem; background: color-mix(in srgb, var(--da) 16%, #1a1a1a); }
.x { flex: 1; display: grid; min-width: 0; }
.x b { font-size: 1rem; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.x small { color: var(--muted); font-size: .76rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.sug { font-style: normal; font-size: .62rem; font-weight: 900; padding: 2px 7px; border-radius: 99px; background: var(--orange); color: #0a0a0a; }
.r { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #444; flex: none; }
.o.on .r { border: 7px solid var(--da); }
.compact { grid-template-columns: 1fr; max-height: 340px; overflow-y: auto; padding-inline-end: 4px; }
@media (min-width: 700px) { .dp:not(.compact) { grid-template-columns: 1fr 1fr; } }
</style>
