<script setup>
import { watch, onBeforeUnmount, ref } from 'vue'

const props = defineProps({ open: Boolean, title: String })
const emit = defineEmits(['close'])
const startY = ref(null)
const dy = ref(0)

const onKey = (e) => e.key === 'Escape' && emit('close')
watch(() => props.open, (v) => {
  document.documentElement.style.overflow = v ? 'hidden' : ''
  v ? addEventListener('keydown', onKey) : removeEventListener('keydown', onKey)
  dy.value = 0
})
onBeforeUnmount(() => { document.documentElement.style.overflow = ''; removeEventListener('keydown', onKey) })

// drag the handle down to dismiss
const down = (e) => (startY.value = e.touches[0].clientY)
const move = (e) => { if (startY.value !== null) dy.value = Math.max(0, e.touches[0].clientY - startY.value) }
const up = () => { if (dy.value > 90) emit('close'); dy.value = 0; startY.value = null }
</script>

<template>
  <Teleport to="body">
    <Transition name="sheet">
      <div v-if="open" class="scrim" @click.self="emit('close')">
        <section class="sheet" role="dialog" aria-modal="true" :aria-label="title" :style="dy ? { transform: `translateY(${dy}px)`, transition: 'none' } : null">
          <div class="grab" @touchstart.passive="down" @touchmove.passive="move" @touchend="up"><span></span></div>
          <h2 v-if="title">{{ title }}</h2>
          <slot />
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.scrim { position: fixed; inset: 0; z-index: 250; background: rgba(0,0,0,.6); display: flex; align-items: flex-end; }
.sheet { width: 100%; max-height: 86svh; overflow: auto; background: #141414; border-radius: 22px 22px 0 0; border-top: 1px solid #262626; padding: 0 16px max(18px, env(safe-area-inset-bottom)); transition: transform .25s cubic-bezier(.2,.8,.2,1); }
.grab { display: grid; place-items: center; padding: 10px 0 6px; position: sticky; top: 0; background: #141414; touch-action: none; }
.grab span { width: 42px; height: 5px; border-radius: 99px; background: #3a3a3a; }
h2 { font-size: 1.5rem; margin: 4px 0 10px; }
.sheet-enter-active, .sheet-leave-active { transition: background-color .25s; }
.sheet-enter-active .sheet, .sheet-leave-active .sheet { transition: transform .28s cubic-bezier(.2,.8,.2,1); }
.sheet-enter-from, .sheet-leave-to { background-color: transparent; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
@media (min-width: 960px) { .scrim { align-items: center; justify-content: center; } .sheet { max-width: 440px; border-radius: 22px; border: 1px solid #262626; } }
</style>
