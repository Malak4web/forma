<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SiteHeader from './components/SiteHeader.vue'
import SiteFooter from './components/SiteFooter.vue'
import TabBar from './components/TabBar.vue'
import Toast from './components/Toast.vue'
import MAppBar from './mobile/MAppBar.vue'
import { MOBILE } from './mobile/device.js'
import { ui } from './stores/app.js'

const route = useRoute()
const tabs = computed(() => (MOBILE ? !!route.meta.tab : !route.meta.bare))
</script>

<template>
  <!-- ===== phones: app shell — each screen owns its app bar; tab bar only on the 4 root tabs ===== -->
  <template v-if="MOBILE">
    <MAppBar v-if="!route.meta.m" :title="ui.title" fallback="/account" />
    <RouterView v-slot="{ Component, route: r }">
      <Transition name="page" mode="out-in">
        <component :is="Component" :key="r.path" />
      </Transition>
    </RouterView>
    <TabBar v-if="tabs" />
    <Toast lifted />
  </template>

  <!-- ===== tablets / desktop: website ===== -->
  <template v-else>
    <SiteHeader :overlay="!!route.meta.overlay" :bare="!!route.meta.bare" />
    <main id="main" :class="{ 'with-tabs': tabs }">
      <RouterView v-slot="{ Component, route: r }">
        <Transition name="page" mode="out-in">
          <component :is="Component" :key="r.path" />
        </Transition>
      </RouterView>
    </main>
    <SiteFooter v-if="!route.meta.bare" class="footer" />
    <Toast />
  </template>
</template>

<style>
#main { min-height: 70vh; }
/* opacity only — a transform here would re-anchor position:fixed children (sticky CTAs) mid-transition */
.page-enter-active, .page-leave-active { transition: opacity .14s ease; }
.page-enter-from, .page-leave-to { opacity: 0; }
</style>
