<script setup>
defineProps({ size: { type: Number, default: 40 }, stacked: { type: Boolean, default: true } })
</script>

<template>
  <RouterLink to="/" class="logo" aria-label="فورمة FORMA — الرئيسية">
    <img src="/img/logo-icon.webp" alt="" class="logo-icon" :style="{ width: size + 'px', height: size + 'px' }" />
    <span class="logo-text" :class="{ stacked }">
      <span class="logo-ar">فورمة</span>
      <span class="logo-en en">FORMA</span>
    </span>
  </RouterLink>
</template>

<style scoped>
.logo { display: inline-flex; align-items: center; gap: 10px; flex: none; }
.logo-icon { border-radius: 22%; }
.logo-text { display: flex; align-items: baseline; gap: 8px; }
.logo-text.stacked { flex-direction: column; align-items: flex-start; gap: 0; line-height: 1; }
.logo-ar { font-family: var(--f-head); font-size: 1.55rem; line-height: 1; }
.logo-en { color: var(--orange); font-size: .78rem; letter-spacing: .38em; margin-top: 2px; }
</style>
