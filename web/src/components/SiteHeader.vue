<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import Logo from './Logo.vue'
import { store } from '../stores/app.js'

const props = defineProps({ overlay: Boolean, bare: Boolean })
const route = useRoute()
const open = ref(false)
const scrolled = ref(false)
const links = [
  { to: '/library', label: 'المكتبة' },
  { to: '/programs', label: 'البرامج' },
  { to: '/calculator', label: 'احسب سعراتك' },
  { to: '/pricing', label: 'الباقات' },
]
const onScroll = () => (scrolled.value = scrollY > 24)
onMounted(() => { onScroll(); addEventListener('scroll', onScroll, { passive: true }) })
onBeforeUnmount(() => removeEventListener('scroll', onScroll))
watch(() => route.fullPath, () => (open.value = false))
watch(open, (v) => (document.documentElement.style.overflow = v ? 'hidden' : ''))
</script>

<template>
  <header class="hdr" :class="{ solid: !overlay || scrolled || open, bare }">
    <div class="wrap hdr-in">
      <Logo :size="38" />
      <template v-if="!bare">
        <nav class="links" aria-label="القائمة الرئيسية">
          <RouterLink v-for="l in links" :key="l.to" :to="l.to">{{ l.label }}</RouterLink>
        </nav>
        <div class="acts">
          <RouterLink v-if="store.user" to="/account" class="acc" aria-label="حسابي">
            <span class="avatar">{{ store.user.name.charAt(0) }}</span><span class="acc-l">حسابي</span>
          </RouterLink>
          <RouterLink v-else to="/login" class="login">دخول</RouterLink>
          <RouterLink v-if="!store.subscription" to="/calculator" class="btn btn-primary btn-sm cta">نظامك ببلاش</RouterLink>
          <button class="burger" :aria-expanded="open" aria-controls="drawer" aria-label="القائمة" @click="open = !open">
            <span :class="{ x: open }"></span>
          </button>
        </div>
      </template>
      <RouterLink v-else :to="store.user ? '/account' : '/'" class="back">رجوع ←</RouterLink>
    </div>

    <Transition name="drawer">
      <nav v-if="open" id="drawer" class="drawer" aria-label="القائمة">
        <RouterLink to="/">الرئيسية</RouterLink>
        <RouterLink v-for="l in links" :key="l.to" :to="l.to">{{ l.label }}</RouterLink>
        <RouterLink v-if="store.user" to="/account">حسابي</RouterLink>
        <RouterLink v-if="store.user?.is_admin" to="/admin/orders">لوحة الأدمن</RouterLink>
        <template v-else>
          <RouterLink to="/login">دخول</RouterLink>
          <RouterLink to="/register" class="btn btn-primary">اعمل حساب</RouterLink>
        </template>
      </nav>
    </Transition>
  </header>
  <div v-if="!overlay" class="hdr-space" aria-hidden="true"></div>
</template>

<style scoped>
.hdr { position: fixed; top: 0; inset-inline: 0; z-index: 50; transition: background-color .2s, border-color .2s; border-bottom: 1px solid transparent; }
.hdr.solid { background: rgba(10,10,10,.92); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-color: #1c1c1c; }
.hdr-space { height: var(--header); }
.hdr-in { height: var(--header); display: flex; align-items: center; gap: 20px; }
.links { display: none; gap: 24px; margin-inline-start: 12px; font-weight: 700; }
.links a { color: rgba(255,255,255,.8); padding-block: 6px; border-bottom: 2px solid transparent; }
.links a:hover, .links a.router-link-active { color: #fff; border-color: var(--orange); }
.acts { margin-inline-start: auto; display: flex; align-items: center; gap: 10px; }
.login { font-weight: 800; font-size: .92rem; padding: 6px 4px; }
.login:hover { color: var(--orange); }
.acc { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: .92rem; }
.avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-family: var(--f-head); font-size: 1.1rem; }
.acc-l { display: none; }
.cta { display: none; }
.burger { width: 44px; height: 44px; display: grid; place-items: center; margin-inline-end: -8px; }
.burger span, .burger span::before, .burger span::after { display: block; width: 22px; height: 2.5px; background: #fff; border-radius: 2px; position: relative; transition: transform .2s, background .2s; }
.burger span::before, .burger span::after { content: ''; position: absolute; inset-inline: 0; }
.burger span::before { top: -7px; } .burger span::after { top: 7px; }
.burger span.x { background: transparent; }
.burger span.x::before { transform: translateY(7px) rotate(45deg); }
.burger span.x::after { transform: translateY(-7px) rotate(-45deg); }
.back { margin-inline-start: auto; font-weight: 800; color: var(--muted); }
.back:hover { color: #fff; }
@media (min-width: 420px) { .cta { display: inline-flex; } }
@media (min-width: 960px) { .links { display: flex; } .burger { display: none; } .acc-l { display: inline; } }

.drawer { position: fixed; top: var(--header); inset-inline: 0; bottom: 0; background: #0a0a0a; padding: 20px var(--gutter); display: flex; flex-direction: column; gap: 4px; overflow-y: auto; }
.drawer a:not(.btn) { font-family: var(--f-head); font-size: 1.8rem; padding: 10px 0; border-bottom: 1px solid #1a1a1a; }
.drawer a.router-link-exact-active:not(.btn) { color: var(--orange); }
.drawer .btn { margin-top: 18px; }
.drawer-enter-active, .drawer-leave-active { transition: opacity .2s, transform .2s; }
.drawer-enter-from, .drawer-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
