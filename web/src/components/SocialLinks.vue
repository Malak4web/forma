<script setup>
// FORMA's official social accounts — single source for footer, mobile home, "more" sheet and account.
defineProps({ title: { type: String, default: '' }, compact: Boolean })

const SOCIAL = [
  {
    id: 'tiktok', label: 'تيك توك', handle: '@forma2a', url: 'https://www.tiktok.com/@forma2a',
    d: 'M16.6 2h-3.3v13.4a2.9 2.9 0 1 1-2.9-2.9c.3 0 .6 0 .9.1V9.2a6.3 6.3 0 1 0 5.3 6.2V8.6a7.9 7.9 0 0 0 4.4 1.4V6.7a4.5 4.5 0 0 1-4.4-4.7z',
  },
  {
    id: 'youtube', label: 'يوتيوب', handle: '@forma22', url: 'https://www.youtube.com/@forma22',
    d: 'M21.6 7.2a2.6 2.6 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.6 2.6 0 0 0 2.4 7.2 27 27 0 0 0 2 12a27 27 0 0 0 .4 4.8 2.6 2.6 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.6 2.6 0 0 0 1.8-1.8A27 27 0 0 0 22 12a27 27 0 0 0-.4-4.8zM10 15V9l5.2 3z',
  },
  {
    id: 'instagram', label: 'إنستجرام', handle: '@form_aar', url: 'https://www.instagram.com/form_aar/',
    d: 'M12 7.3A4.7 4.7 0 1 0 12 16.7 4.7 4.7 0 0 0 12 7.3zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm4.9-8.9a1.1 1.1 0 1 0 0 2.2 1.1 1.1 0 0 0 0-2.2zM12 3.6c2.7 0 3 0 4.1.1 2.7.1 4 1.4 4.1 4.1.1 1.1.1 1.4.1 4.1s0 3-.1 4.1c-.1 2.7-1.4 4-4.1 4.1-1.1.1-1.4.1-4.1.1s-3 0-4.1-.1c-2.7-.1-4-1.4-4.1-4.1C3.7 15 3.6 14.7 3.6 12s0-3 .1-4.1c.1-2.7 1.4-4 4.1-4.1C9 3.6 9.3 3.6 12 3.6zM12 2C9.3 2 8.9 2 7.9 2.1 4.2 2.2 2.2 4.2 2.1 7.9 2 8.9 2 9.3 2 12s0 3.1.1 4.1c.1 3.7 2.1 5.7 5.8 5.8 1 .1 1.4.1 4.1.1s3.1 0 4.1-.1c3.7-.1 5.7-2.1 5.8-5.8.1-1 .1-1.4.1-4.1s0-3.1-.1-4.1c-.1-3.7-2.1-5.7-5.8-5.8C15.1 2 14.7 2 12 2z',
  },
  {
    id: 'facebook', label: 'فيسبوك', handle: 'فورمه', url: 'https://www.facebook.com/profile.php?id=61595898096528',
    d: 'M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z',
  },
]
</script>

<template>
  <div class="soc" :class="{ compact }">
    <b v-if="title" class="st">{{ title }}</b>
    <div class="row">
      <a v-for="s in SOCIAL" :key="s.id" :href="s.url" target="_blank" rel="noopener" class="sl" :class="s.id" :aria-label="`${s.label} ${s.handle}`">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="s.d" /></svg>
        <span v-if="!compact" class="tx"><small>{{ s.label }}</small><i :class="{ en: s.handle.startsWith('@') }">{{ s.handle }}</i></span>
      </a>
    </div>
  </div>
</template>

<style scoped>
.soc { display: grid; gap: 10px; }
.st { font-family: var(--f-head); font-weight: 400; font-size: 1.2rem; }
.row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
.compact .row { display: flex; gap: 10px; }
.sl { display: flex; align-items: center; gap: 10px; min-height: 48px; padding: 8px 12px; border-radius: 14px; background: #141414; border: 1px solid #262626; transition: border-color .15s, transform .15s; }
.sl:hover { border-color: var(--orange); }
.sl:active { transform: scale(.97); }
.compact .sl { width: 44px; height: 44px; min-height: 0; padding: 0; justify-content: center; border-radius: 12px; }
.sl svg { width: 22px; height: 22px; flex: none; fill: var(--orange); }
.tx { display: grid; line-height: 1.25; min-width: 0; }
.tx small { font-size: .7rem; font-weight: 800; color: var(--muted); }
.tx i { font-style: normal; font-weight: 800; font-size: .9rem; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tx i.en { font-family: var(--f-num); font-weight: 400; font-size: 1.05rem; letter-spacing: .03em; direction: ltr; text-align: right; }
</style>
