<script setup>
// Horizontal snap rail with an optional section header (title · "i / n" · arrows · "الكل")
// and dots — so every section shows where it ends without vertical scrolling.
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'

const props = defineProps({
  title: String,
  total: Number,          // items count for the counter (defaults to children)
  all: [String, Object],  // "الكل" link
  allLabel: { type: String, default: 'الكل' },
  arrows: Boolean,
  dots: Boolean,
  counter: { type: Boolean, default: true },
  gap: { type: Number, default: 10 },
})
const emit = defineEmits(['change'])
const el = ref(null)
const idx = ref(0)
const count = ref(0)

function measure() {
  const r = el.value
  if (!r) return
  const kids = r.children
  count.value = kids.length
  if (!kids.length) return
  const step = kids[0].getBoundingClientRect().width + props.gap
  // RTL: scrollLeft is 0 at the start and goes negative
  const i = Math.round(Math.abs(r.scrollLeft) / step)
  const atEnd = Math.abs(r.scrollLeft) + r.clientWidth >= r.scrollWidth - 4
  idx.value = atEnd ? kids.length - 1 : Math.min(kids.length - 1, i)
  emit('change', idx.value)
}
function go(dir) {
  const r = el.value
  const kid = r?.children[0]
  if (!kid) return
  // dir 1 = next (towards the left in RTL)
  r.scrollBy({ left: -dir * (kid.getBoundingClientRect().width + props.gap), behavior: 'smooth' })
}
function to(i) {
  const kid = el.value?.children[i]
  kid?.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' })
}
defineExpose({ go, to })

let ro
onMounted(() => {
  measure()
  el.value.addEventListener('scroll', measure, { passive: true })
  ro = new ResizeObserver(measure)
  ro.observe(el.value)
})
onBeforeUnmount(() => ro?.disconnect())
watch(() => props.total, () => nextTick(() => { el.value?.scrollTo({ left: 0 }); measure() }))
</script>

<template>
  <section class="mrail">
    <header v-if="title" class="mrail-h">
      <h2>{{ title }}</h2>
      <span v-if="counter && (total ?? count) > 1" class="num cnt">{{ idx + 1 }} / {{ total ?? count }}</span>
      <div v-if="arrows" class="arr" dir="ltr">
        <button aria-label="اللي فات" :disabled="idx === 0" @click="go(-1)">‹</button>
        <button aria-label="اللي بعده" :disabled="idx >= count - 1" @click="go(1)">›</button>
      </div>
      <RouterLink v-if="all" :to="all" class="all">{{ allLabel }} ‹</RouterLink>
    </header>
    <div ref="el" class="mrail-r" :style="{ gap: gap + 'px' }"><slot /></div>
    <div v-if="dots && count > 1" class="mdots" aria-hidden="true">
      <i v-for="i in count" :key="i" :class="{ on: i - 1 === idx }" @click="to(i - 1)"></i>
    </div>
  </section>
</template>

<style scoped>
.mrail-h { display: flex; align-items: center; gap: 8px; padding: 20px 16px 10px; }
.mrail-h h2 { font-size: 1.3rem; flex: 1; }
.cnt { color: var(--muted); font-size: 1rem; }
.arr { display: flex; gap: 4px; }
.arr button { width: 30px; height: 30px; border-radius: 9px; background: var(--surface); border: 1px solid #262626; font-weight: 900; }
.arr button:disabled { opacity: .3; }
.all { font-size: .8rem; font-weight: 800; color: var(--orange); white-space: nowrap; }
.mrail-r { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; padding-inline: 16px; scroll-padding-inline: 16px; overscroll-behavior-x: contain; }
.mrail-r::-webkit-scrollbar { display: none; }
.mrail-r > :deep(*) { flex: none; scroll-snap-align: start; }
.mdots { display: flex; justify-content: center; gap: 5px; padding-top: 10px; }
.mdots i { width: 6px; height: 6px; border-radius: 9px; background: #333; transition: width .2s, background .2s; cursor: pointer; }
.mdots i.on { width: 18px; background: var(--orange); }
</style>
