<script setup>
import SiteHeader from './components/SiteHeader.vue'
import SiteFooter from './components/SiteFooter.vue'
import Toast from './components/Toast.vue'
</script>

<template>
  <SiteHeader :overlay="!!$route.meta.overlay" :bare="!!$route.meta.bare" />
  <main id="main" :class="{ 'has-overlay': $route.meta.overlay }">
    <RouterView v-slot="{ Component, route }">
      <component :is="Component" :key="route.path" />
    </RouterView>
  </main>
  <SiteFooter v-if="!$route.meta.bare" />
  <Toast />
</template>

<style>
#main { min-height: 70vh; }
</style>
