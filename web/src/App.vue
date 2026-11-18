<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SiteHeader from './components/SiteHeader.vue'
import SiteFooter from './components/SiteFooter.vue'
import TabBar from './components/TabBar.vue'
import Toast from './components/Toast.vue'

const route = useRoute()
// focused flows (auth, checkout, workout player) hide the tab bar like a native app
const tabs = computed(() => !route.meta.bare)
</script>

<template>
  <SiteHeader :overlay="!!route.meta.overlay" :bare="!!route.meta.bare" />
  <main id="main" :class="{ 'with-tabs': tabs }">
    <RouterView v-slot="{ Component, route: r }">
      <Transition name="page" mode="out-in">
        <component :is="Component" :key="r.path" />
      </Transition>
    </RouterView>
  </main>
  <SiteFooter v-if="!route.meta.bare" class="footer" />
  <TabBar v-if="tabs" />
  <Toast :lifted="tabs" />
</template>

<style>
#main { min-height: 70vh; }
@media (max-width: 959px) {
  #main.with-tabs { padding-bottom: calc(84px + env(safe-area-inset-bottom)); }
  .footer { display: none; }
}
/* opacity only — a transform here would re-anchor position:fixed children (sticky CTAs) mid-transition */
.page-enter-active, .page-leave-active { transition: opacity .14s ease; }
.page-enter-from, .page-leave-to { opacity: 0; }
</style>
