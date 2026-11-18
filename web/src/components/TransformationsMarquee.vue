<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue'
import { transformations } from '../data.js'

const row1 = computed(() => transformations)
const row2 = computed(() => [...transformations].reverse())
const duration = computed(() => `${Math.max(48, transformations.length * 3.2)}s`)

const active = ref(null)
const onKey = (e) => { if (e.key === 'Escape') active.value = null }
watch(active, (v) => {
  document.documentElement.style.overflow = v ? 'hidden' : ''
  v ? addEventListener('keydown', onKey) : removeEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  document.documentElement.style.overflow = ''
  removeEventListener('keydown', onKey)
})
</script>

<template>
  <section v-if="transformations.length" class="tr-sec">
    <div class="wrap tr-head">
      <div>
        <span class="kicker">نتائج حقيقية</span>
        <h2>مئات <span class="hl">التحولات</span> ومكمّلين</h2>
        <p>ناس عادية قررت تبدأ وتلتزم — والنتيجة بتتكلم عن نفسها.</p>
      </div>
    </div>

    <div class="tr-rows" :style="{ '--dur': duration }">
      <!-- Row 1: moves right -->
      <div class="tr-viewport">
        <div class="tr-track to-right">
          <div v-for="half in 2" :key="`r1-${half}`" class="tr-set" :aria-hidden="half === 2">
            <button
              v-for="item in row1"
              :key="`r1-${half}-${item.id}`"
              type="button"
              class="tr-card"
              :tabindex="half === 2 ? -1 : 0"
              @click="active = item"
            >
              <img :src="item.image_url" :alt="item.title || 'تحول فورمة'" loading="lazy" decoding="async" />
              <div v-if="item.title || item.subtitle" class="tr-cap">
                <b v-if="item.title">{{ item.title }}</b>
                <span v-if="item.subtitle">{{ item.subtitle }}</span>
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Row 2: reversed order, moves left -->
      <div class="tr-viewport">
        <div class="tr-track to-left">
          <div v-for="half in 2" :key="`r2-${half}`" class="tr-set" :aria-hidden="half === 2">
            <button
              v-for="item in row2"
              :key="`r2-${half}-${item.id}`"
              type="button"
              class="tr-card"
              :tabindex="half === 2 ? -1 : 0"
              @click="active = item"
            >
              <img :src="item.image_url" :alt="item.title || 'تحول فورمة'" loading="lazy" decoding="async" />
              <div v-if="item.title || item.subtitle" class="tr-cap">
                <b v-if="item.title">{{ item.title }}</b>
                <span v-if="item.subtitle">{{ item.subtitle }}</span>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="active" class="tr-modal" role="dialog" aria-modal="true" @click.self="active = null">
        <div class="tr-box">
          <button type="button" class="tr-close" aria-label="إغلاق" @click="active = null">✕</button>
          <img :src="active.image_url" :alt="active.title || 'تحول فورمة'" />
          <div v-if="active.title || active.subtitle" class="tr-box-cap">
            <b v-if="active.title">{{ active.title }}</b>
            <p v-if="active.subtitle">{{ active.subtitle }}</p>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<style scoped>
.tr-sec { padding-block: 64px 20px; overflow: hidden; }
.tr-head { margin-bottom: 24px; }
.tr-head h2 { font-size: clamp(2rem, 7vw, 3.1rem); line-height: 1.1; margin-top: 4px; }
.tr-head p { color: var(--muted); margin-top: 6px; max-width: 50ch; }

.tr-rows { display: grid; gap: 14px; }
.tr-viewport {
  direction: ltr;
  overflow: hidden;
  -webkit-mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
  mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
}
.tr-track {
  display: flex;
  width: max-content;
  will-change: transform;
}
.tr-viewport:hover .tr-track { animation-play-state: paused; }

.tr-track.to-right { animation: tr-right var(--dur, 75s) linear infinite; }
.tr-track.to-left { animation: tr-left var(--dur, 75s) linear infinite; }

@keyframes tr-right {
  from { transform: translateX(-50%); }
  to { transform: translateX(0); }
}
@keyframes tr-left {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}

.tr-set { display: flex; gap: 12px; padding-inline-end: 12px; }
.tr-card {
  position: relative;
  flex: 0 0 auto;
  width: 175px;
  aspect-ratio: 4 / 5;
  border-radius: 14px;
  overflow: hidden;
  background: #141414;
  border: 1px solid #242424;
  padding: 0;
  cursor: pointer;
  transition: border-color .2s, transform .2s;
}
.tr-card:hover { border-color: var(--orange); transform: translateY(-2px); }
.tr-card img { width: 100%; height: 100%; object-fit: cover; display: block; }
.tr-cap {
  position: absolute;
  inset-inline: 0;
  bottom: 0;
  padding: 24px 10px 8px;
  background: linear-gradient(to top, rgba(0, 0, 0, .88), transparent);
  display: grid;
  gap: 2px;
  text-align: start;
  direction: rtl;
}
.tr-cap b { font-size: .88rem; color: #fff; }
.tr-cap span { font-size: .76rem; color: var(--amber); font-weight: 700; }

@media (min-width: 700px) {
  .tr-rows { gap: 16px; }
  .tr-set { gap: 16px; padding-inline-end: 16px; }
  .tr-card { width: 220px; border-radius: 16px; }
}

@media (prefers-reduced-motion: reduce) {
  .tr-track { animation: none !important; }
  .tr-viewport { overflow-x: auto; scrollbar-width: none; }
  .tr-viewport::-webkit-scrollbar { display: none; }
}

.tr-modal {
  position: fixed;
  inset: 0;
  z-index: 300;
  background: rgba(0, 0, 0, .86);
  display: grid;
  place-items: center;
  padding: 18px;
}
.tr-box {
  position: relative;
  max-width: min(460px, 94vw);
  max-height: 90svh;
  background: #121212;
  border: 1px solid #2a2a2a;
  border-radius: 16px;
  overflow: hidden;
  display: grid;
}
.tr-box img { width: 100%; max-height: 78svh; object-fit: contain; display: block; background: #0a0a0a; }
.tr-box-cap { padding: 12px 16px; display: grid; gap: 4px; }
.tr-box-cap p { color: var(--amber); font-size: .9rem; font-weight: 700; }
.tr-close {
  position: absolute;
  top: 10px;
  inset-inline-end: 10px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(10, 10, 10, .85);
  color: #fff;
  font-size: 1rem;
  display: grid;
  place-items: center;
  border: 1px solid #333;
}
</style>
