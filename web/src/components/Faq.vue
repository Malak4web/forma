<script setup>
import { faqs } from '../data.js'
defineProps({ items: { type: Array, default: () => faqs } })
</script>

<template>
  <div class="faq">
    <details v-for="f in items" :key="f.q"><summary>{{ f.q }}</summary><p>{{ f.a }}</p></details>
  </div>
</template>

<style scoped>
.faq details { border-bottom: 1px solid #222; padding-block: 16px; }
.faq summary { font-family: var(--f-head); font-size: 1.3rem; cursor: pointer; list-style: none; display: flex; justify-content: space-between; gap: 12px; }
.faq summary::-webkit-details-marker { display: none; }
.faq summary::after { content: '+'; color: var(--orange); font-family: var(--f-num); font-size: 1.8rem; line-height: 1; }
.faq details[open] summary::after { content: '−'; }
.faq details p { color: var(--muted); padding-top: 8px; }
</style>
