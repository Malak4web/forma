<script setup>
import { store } from '../stores/app.js'
defineProps({ lifted: Boolean })
</script>

<template>
  <div class="toast-host" :class="{ lifted }" aria-live="polite">
    <Transition name="toast">
      <div v-if="store.toast" class="toast" :class="store.toast.kind">{{ store.toast.msg }}</div>
    </Transition>
  </div>
</template>

<style scoped>
.toast-host { position: fixed; z-index: 300; inset-inline: 0; bottom: max(16px, env(safe-area-inset-bottom)); display: flex; justify-content: center; pointer-events: none; padding-inline: 16px; }
@media (max-width: 959px) { .toast-host.lifted { bottom: calc(90px + env(safe-area-inset-bottom)); } }
.toast { background: #fff; color: #0a0a0a; font-weight: 800; padding: 12px 18px; border-radius: 14px; box-shadow: 0 12px 40px rgba(0,0,0,.5); border-inline-start: 5px solid var(--orange); }
.toast.err { border-color: var(--danger); }
.toast-enter-active, .toast-leave-active { transition: opacity .2s, transform .2s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px) scale(.96); }
</style>
