<script setup>
import SocialLinks from './SocialLinks.vue'
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Logo from './Logo.vue'
import BottomSheet from './BottomSheet.vue'
import { store, ui } from '../stores/app.js'
import { canGoBack } from '../router.js'

const props = defineProps({ overlay: Boolean, bare: Boolean })
const route = useRoute()
const router = useRouter()
const more = ref(false)
const scrolled = ref(false)
const guestLinks = [
  { to: '/library', label: 'المكتبة' },
  { to: '/programs', label: 'البرامج' },
  { to: '/training', label: 'أنظمة التدريب' },
  { to: '/diets', label: 'أنظمة الأكل' },
  { to: '/calculator', label: 'احسب سعراتك' },
  { to: '/pricing', label: 'الباقات' },
]
// signed-in members: their day first, discovery after
const memberLinks = [
  { to: '/', label: 'النهارده' },
  { to: '/progress', label: 'تقدّمي' },
  { to: '/coach', label: 'الكوتش', coach: true },
  { to: '/training', label: 'التدريب' },
  { to: '/diets', label: 'الأكل' },
  { to: '/programs', label: 'البرامج' },
  { to: '/library', label: 'المكتبة' },
]
const links = computed(() => (store.user ? memberLinks : guestLinks))
const isTab = computed(() => !!route.meta.tab)
const onScroll = () => (scrolled.value = scrollY > 24)
onMounted(() => { onScroll(); addEventListener('scroll', onScroll, { passive: true }) })
onBeforeUnmount(() => removeEventListener('scroll', onScroll))
watch(() => route.fullPath, () => (more.value = false))

function back() {
  if (canGoBack()) return router.back()
  const p = route.path
  router.push(p.startsWith('/library/') ? '/library' : p.startsWith('/programs/') || p.startsWith('/workout/') ? '/programs' : p.startsWith('/checkout') ? '/pricing' : p.startsWith('/admin') ? '/account' : '/')
}
</script>

<template>
  <header class="hdr" :class="{ solid: !overlay || scrolled, bare, inner: !isTab }">
    <div class="wrap hdr-in">
      <!-- mobile inner page: back + title -->
      <button v-if="!isTab" class="m-back" aria-label="رجوع" @click="back">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
      </button>
      <span v-if="!isTab" class="m-title">{{ ui.title }}</span>

      <Logo :size="36" class="logo" />

      <template v-if="!bare">
        <nav class="links" aria-label="القائمة الرئيسية">
          <RouterLink v-for="l in links" :key="l.to" :to="l.to">
            {{ l.label }}<i v-if="l.coach && store.coach.unread" class="unread num">{{ store.coach.unread }}</i>
          </RouterLink>
        </nav>
        <div class="acts">
          <RouterLink v-if="store.user" to="/account" class="acc" aria-label="حسابي">
            <span class="avatar">{{ store.user.name.charAt(0) }}</span><span class="acc-l">حسابي</span>
          </RouterLink>
          <RouterLink v-else to="/login" class="login">دخول</RouterLink>
          <RouterLink v-if="!store.user" to="/calculator" class="btn btn-primary btn-sm cta">نظامك ببلاش</RouterLink>
          <RouterLink v-else-if="!store.subscription" to="/pricing" class="btn btn-primary btn-sm cta">اشترك</RouterLink>
          <button class="more" aria-label="المزيد" @click="more = true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1.8" /><circle cx="12" cy="12" r="1.8" /><circle cx="19" cy="12" r="1.8" /></svg>
          </button>
        </div>
      </template>
      <button v-else class="back d-only" @click="back">رجوع ←</button>
    </div>
  </header>
  <div v-if="!overlay" class="hdr-space" aria-hidden="true"></div>

  <BottomSheet :open="more" title="المزيد" @close="more = false">
    <nav class="sheet-list">
      <RouterLink to="/training"><span>🏋️</span>أنظمة التدريب · برامج 4 أسابيع</RouterLink>
      <RouterLink to="/diets"><span>🥑</span>أنظمة الأكل · خطط 30 يوم</RouterLink>
      <RouterLink to="/calculator"><span>🔥</span>احسب سعراتك وخد نظامك ببلاش</RouterLink>
      <RouterLink to="/pricing"><span>⭐</span>الباقات والأسعار</RouterLink>
      <RouterLink to="/programs"><span>📅</span>كل البرامج</RouterLink>
      <RouterLink v-if="store.user" to="/library"><span>🎬</span>مكتبة التمارين</RouterLink>
      <RouterLink v-if="store.user?.is_admin" to="/admin"><span>🛠️</span>لوحة الأدمن</RouterLink>
      <SocialLinks title="تابعنا" class="sheet-soc" />
      <template v-if="!store.user">
        <RouterLink to="/login"><span>👋</span>دخول</RouterLink>
        <RouterLink to="/register" class="btn btn-primary">اعمل حساب ببلاش</RouterLink>
      </template>
    </nav>
  </BottomSheet>
</template>

<style scoped>
.sheet-soc { padding: 8px 0 4px; }
.hdr { position: fixed; top: 0; inset-inline: 0; z-index: 50; padding-top: env(safe-area-inset-top); transition: background-color .2s, border-color .2s; border-bottom: 1px solid transparent; }
.hdr.solid { background: rgba(10,10,10,.92); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-color: #1c1c1c; }
.hdr-space { height: calc(var(--header) + env(safe-area-inset-top)); }
.hdr-in { height: var(--header); display: flex; align-items: center; gap: 16px; }
.links { display: none; gap: 24px; margin-inline-start: 12px; font-weight: 700; }
.links a { color: rgba(255,255,255,.8); padding-block: 6px; border-bottom: 2px solid transparent; }
.links a:hover, .links a.router-link-active { color: #fff; border-color: var(--orange); }
.unread { display: inline-block; margin-inline-start: 5px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 99px; background: var(--orange); color: #0a0a0a; font-style: normal; font-size: .85rem; line-height: 18px; text-align: center; vertical-align: 2px; }
.acts { margin-inline-start: auto; display: flex; align-items: center; gap: 10px; }
.login { font-weight: 800; font-size: .92rem; padding: 6px 4px; }
.acc { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: .92rem; }
.avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--orange); color: #0a0a0a; display: grid; place-items: center; font-family: var(--f-head); font-size: 1.1rem; }
.acc-l { display: none; }
.cta { display: none; }
.more { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 50%; margin-inline-end: -6px; }
.more svg { width: 22px; fill: #fff; }
.more:active { background: #1d1d1d; }
.back { margin-inline-start: auto; font-weight: 800; color: var(--muted); }
.m-back, .m-title { display: none; }

/* ---------- mobile app chrome ---------- */
@media (max-width: 959px) {
  .acc, .login { display: none; } /* account lives in the tab bar */
  .inner .logo { display: none; }
  .inner .m-back { display: grid; place-items: center; width: 40px; height: 40px; margin-inline-start: -8px; border-radius: 50%; }
  .inner .m-back:active { background: #1d1d1d; }
  .m-back svg { width: 24px; height: 24px; fill: none; stroke: #fff; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
  .inner .m-title { display: block; font-family: var(--f-head); font-size: 1.3rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex: 1; }
  .inner .acts { margin-inline-start: 0; }
  .inner .cta { display: none !important; }
  .d-only { display: none; }
}
@media (min-width: 420px) { .cta { display: inline-flex; } }
@media (min-width: 960px) { .links { display: flex; } .more { display: none; } .acc-l { display: inline; } }

.sheet-list { display: grid; gap: 4px; padding-bottom: 6px; }
.sheet-list a:not(.btn) { display: flex; align-items: center; gap: 14px; padding: 14px 6px; border-radius: 12px; font-weight: 800; font-size: 1.05rem; }
.sheet-list a:not(.btn):active { background: #1d1d1d; }
.sheet-list a span { width: 36px; height: 36px; border-radius: 10px; background: #1f1f1f; display: grid; place-items: center; font-size: 1.1rem; }
.sheet-list .btn { margin-top: 10px; }
</style>
