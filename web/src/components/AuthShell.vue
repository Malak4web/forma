<script setup>
defineProps({ title: String, sub: String, step: String })
</script>

<template>
  <div class="auth">
    <aside class="art" aria-hidden="true">
      <img src="/img/coach-arms-1200.webp" alt="" />
      <div class="art-copy">
        <b>اتمرن صح،<br /><span class="hl">وخلّي جسمك فورمة.</span></b>
      </div>
    </aside>
    <section class="panel">
      <div class="panel-in">
        <span v-if="step" class="step">{{ step }}</span>
        <h1>{{ title }}</h1>
        <p v-if="sub" class="muted">{{ sub }}</p>
        <slot />
      </div>
    </section>
  </div>
</template>

<style scoped>
.auth { display: grid; min-height: calc(100svh - var(--header)); }
.art { display: none; position: relative; overflow: hidden; }
.art img { width: 100%; height: 100%; object-fit: cover; }
.art::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, #0a0a0a 8%, transparent 50%); }
.art-copy { position: absolute; z-index: 1; bottom: 40px; inset-inline: 40px; font-family: var(--f-head); font-size: 2.6rem; line-height: 1.15; }
.panel { display: flex; justify-content: center; padding: 28px var(--gutter) 60px; }
.panel-in { width: 100%; max-width: 440px; display: grid; gap: 14px; align-content: start; }
.step { justify-self: start; font-size: .8rem; font-weight: 800; color: var(--amber); border: 1px solid rgba(255,176,0,.4); padding: 2px 10px; border-radius: 99px; }
h1 { font-size: clamp(2.2rem, 8vw, 3rem); }
@media (min-width: 960px) { .auth { grid-template-columns: 1fr 1fr; } .art { display: block; } .panel { align-items: center; } }
</style>
