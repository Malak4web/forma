<script setup>
// Small stroke icon set (one weight) for the systems pages.
defineProps({ name: { type: String, required: true } })
</script>

<template>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">
    <path v-if="name === 'check'" d="M4 12.5l5 5L20 6.5" />
    <path v-else-if="name === 'x'" d="M6 6l12 12M18 6L6 18" />
    <path v-else-if="name === 'left'" d="M15 5l-7 7 7 7" />
    <path v-else-if="name === 'right'" d="M9 5l7 7-7 7" />
    <path v-else-if="name === 'play'" d="M8 5v14l11-7z" fill="currentColor" stroke="none" />
  </svg>
</template>
