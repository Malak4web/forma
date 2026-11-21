<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { store, nextWorkout } from '../stores/app.js'

const route = useRoute()
const next = computed(() => nextWorkout())
const nextLink = computed(() => (next.value ? `/workout/${next.value.program.id}/${next.value.day}` : '/programs'))
const is = (prefix) => (prefix === '/' ? route.path === '/' : route.path.startsWith(prefix))
const meLink = computed(() => (store.user ? '/account' : '/login'))
</script>

<template>
  <nav class="tabbar" aria-label="التنقّل الرئيسي">
    <RouterLink to="/" class="tab" :class="{ on: is('/') }" :aria-current="is('/') ? 'page' : null">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" /></svg>
      <span>{{ store.user ? 'النهارده' : 'الرئيسية' }}</span>
    </RouterLink>
    <RouterLink v-if="store.user" to="/progress" class="tab" :class="{ on: is('/progress') }" :aria-current="is('/progress') ? 'page' : null">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16M6 15l4-4 3 3 5-6" /><path d="M15 8h3v3" /></svg>
      <span>تقدّمي</span>
    </RouterLink>
    <RouterLink v-else to="/library" class="tab" :class="{ on: is('/library') }" :aria-current="is('/library') ? 'page' : null">
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7.5" height="10" rx="2" /><rect x="13.5" y="3" width="7.5" height="10" rx="2" /><rect x="3" y="15.5" width="7.5" height="5.5" rx="2" /><rect x="13.5" y="15.5" width="7.5" height="5.5" rx="2" /></svg>
      <span>المكتبة</span>
    </RouterLink>

    <RouterLink :to="nextLink" class="tab go" :class="{ on: is('/workout') }" aria-label="تمرين النهارده">
      <span class="go-btn" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6" /></svg>
      </span>
      <span>{{ next?.started ? `اليوم ${next.day}` : 'اتمرن' }}</span>
    </RouterLink>

    <RouterLink v-if="store.user" to="/coach" class="tab" :class="{ on: is('/coach') }" :aria-current="is('/coach') ? 'page' : null">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z" /><path d="M8 10h8M8 13h5" /></svg>
      <span>الكوتش</span>
      <i v-if="store.coach.unread" class="dot num">{{ store.coach.unread }}</i>
    </RouterLink>
    <RouterLink v-else to="/programs" class="tab" :class="{ on: is('/programs') }" :aria-current="is('/programs') ? 'page' : null">
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="16" rx="2.5" /><path d="M4 10h16M9 3v4M15 3v4" /></svg>
      <span>البرامج</span>
    </RouterLink>
    <RouterLink :to="meLink" class="tab" :class="{ on: is('/account') || is('/login') }" :aria-current="is('/account') ? 'page' : null">
      <span v-if="store.user" class="ava" aria-hidden="true">{{ store.user.name.charAt(0) }}</span>
      <svg v-else viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 21c1.2-4 4.2-6 8-6s6.8 2 8 6" /></svg>
      <span>{{ store.user ? 'حسابي' : 'دخول' }}</span>
    </RouterLink>
  </nav>
</template>

<style scoped>
.tabbar {
  position: fixed; z-index: 60; inset-inline: 0; bottom: 0;
  display: grid; grid-template-columns: repeat(5, 1fr); align-items: end;
  padding: 6px 6px max(8px, env(safe-area-inset-bottom));
  background: rgba(12,12,12,.94); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
  border-top: 1px solid #1f1f1f;
}
.tab { display: grid; justify-items: center; gap: 3px; padding: 6px 0 2px; font-size: .68rem; font-weight: 800; color: rgba(255,255,255,.5); -webkit-tap-highlight-color: transparent; transition: color .15s; }
.tab svg { width: 24px; height: 24px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; transition: transform .2s; }
.tab.on { color: #fff; }
.tab.on svg { stroke: var(--orange); transform: translateY(-1px); }
.tab:active svg, .tab:active .go-btn { transform: scale(.88); }
.ava { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; background: #2a2a2a; color: #fff; font-family: var(--f-head); font-size: .95rem; line-height: 1; }
.tab.on .ava { background: var(--orange); color: #0a0a0a; }

.tab { position: relative; }
.dot { position: absolute; top: 0; inset-inline-start: calc(50% + 6px); min-width: 18px; height: 18px; padding: 0 5px; border-radius: 99px; background: var(--orange); color: #0a0a0a; font-size: .8rem; font-style: normal; line-height: 18px; text-align: center; }
.go { position: relative; }
.go-btn {
  width: 56px; height: 56px; margin-top: -26px; border-radius: 18px; display: grid; place-items: center;
  background: var(--orange); box-shadow: 0 10px 24px -8px rgba(255,106,0,.8), 0 0 0 5px #0c0c0c;
  transform: rotate(-8deg); transition: transform .2s;
}
.go-btn svg { width: 30px; height: 30px; stroke: #0a0a0a; stroke-width: 3.2; transform: rotate(8deg); }
.go span:last-child { color: var(--orange); }

@media (min-width: 960px) { .tabbar { display: none; } }
</style>
