<script setup>
// Segmented control — swaps content in place instead of scrolling.
defineProps({ options: Array, modelValue: [String, Number] }) // options: [{ id, label }]
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="mseg" role="tablist">
    <button
      v-for="o in options" :key="o.id" role="tab" :aria-selected="modelValue === o.id"
      :class="{ on: modelValue === o.id }" @click="emit('update:modelValue', o.id)"
    >{{ o.label }}</button>
  </div>
</template>

<style scoped>
.mseg { display: flex; background: var(--surface); border: 1px solid #232323; border-radius: 14px; padding: 4px; gap: 2px; }
.mseg button { flex: 1; min-height: 38px; padding: 0 6px; border-radius: 10px; font-weight: 800; font-size: .84rem; color: var(--muted); white-space: nowrap; transition: background .15s, color .15s; }
.mseg button.on { background: #fff; color: #0a0a0a; }
</style>
