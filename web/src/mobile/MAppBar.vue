<script setup>
import { useRoute, useRouter } from 'vue-router'
import { canGoBack } from '../router.js'

const props = defineProps({
  title: String,
  back: { type: Boolean, default: true },
  close: Boolean,       // ✕ instead of ‹ (focused flows)
  fallback: { type: String, default: '/' },
  transparent: Boolean,
})
const router = useRouter()
const route = useRoute()

function goBack() {
  if (canGoBack()) return router.back()
  router.push(props.fallback || '/')
}
</script>

<template>
  <header class="mab" :class="{ transparent }">
    <button v-if="back" class="mib" :aria-label="close ? 'قفل' : 'رجوع'" @click="goBack">
      <svg v-if="close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" /></svg>
      <svg v-else viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" /></svg>
    </button>
    <slot name="start" />
    <h1 v-if="title" class="mab-t">{{ title }}</h1>
    <span v-else class="mab-sp"></span>
    <slot />
  </header>
</template>

<style scoped>
.mab { position: sticky; top: 0; z-index: 40; display: flex; align-items: center; gap: 10px; min-height: calc(60px + env(safe-area-inset-top)); padding: calc(env(safe-area-inset-top) + 6px) 12px 6px 12px; padding-inline-start: 16px; background: rgba(10,10,10,.94); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); }
.mab.transparent { position: absolute; inset-inline: 0; background: transparent; backdrop-filter: none; -webkit-backdrop-filter: none; }
.mab-t { flex: 1; font-size: 1.45rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
.mab-sp { flex: 1; }
</style>
