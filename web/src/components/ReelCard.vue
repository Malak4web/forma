<script setup>
import { fmt } from '../data.js'
defineProps({ v: Object, n: Number })
</script>

<template>
  <RouterLink :to="'/library/' + v.id" class="reel">
    <img :src="v.img" :alt="v.title" loading="lazy" width="360" height="640" />
    <span v-if="n" class="reel-n num">{{ String(n).padStart(2, '0') }}</span>
    <span class="reel-d num">{{ fmt(v.sec) }}</span>
    <span class="reel-t">
      <b class="en">{{ v.title }}</b>
      <small>{{ v.ar }}</small>
    </span>
  </RouterLink>
</template>

<style scoped>
.reel { position: relative; display: block; aspect-ratio: 9/16; border-radius: 14px; overflow: hidden; border: 2px solid #1d1d1d; background: #000; transition: transform .2s, border-color .2s; }
.reel:hover { transform: translateY(-4px) rotate(-.6deg); border-color: var(--orange); }
.reel img { width: 100%; height: 100%; object-fit: cover; }
.reel::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.94) 0%, rgba(0,0,0,.2) 45%, transparent 60%); }
.reel-n { position: absolute; top: 8px; inset-inline-start: 8px; z-index: 1; background: var(--orange); color: #0a0a0a; font-size: 1.15rem; padding: 0 8px; border-radius: 6px; transform: skewX(-8deg); }
.reel-d { position: absolute; top: 8px; inset-inline-end: 8px; z-index: 1; background: rgba(0,0,0,.75); padding: 0 7px; border-radius: 6px; font-size: 1rem; }
.reel-t { position: absolute; bottom: 10px; inset-inline: 10px; z-index: 1; display: grid; gap: 2px; }
.reel-t b { font-size: clamp(1.3rem, 4.6vw, 1.75rem); line-height: 1; justify-self: start; }
.reel-t small { font-size: .78rem; color: rgba(255,255,255,.82); line-height: 1.4; }
</style>
