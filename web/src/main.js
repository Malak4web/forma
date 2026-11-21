import { createApp } from 'vue'
import '@fontsource/lalezar/400.css'
import '@fontsource-variable/cairo'
import '@fontsource/bebas-neue/400.css'
import './styles/base.css'
import './mobile/m.css'
import './styles/systems.css'
import App from './App.vue'
import router from './router.js'
import { loadContent } from './data.js'
import { loadMe } from './stores/app.js'

async function boot() {
  try {
    await Promise.all([loadContent(), loadMe()])
  } catch {
    document.getElementById('app').innerHTML =
      '<div style="padding:40px 16px;text-align:center;font-family:Cairo Variable,sans-serif"><h1 style="font-family:Lalezar">حصلت مشكلة في الاتصال</h1><p style="opacity:.7">جرّب تعمل ريفرش كمان شوية.</p><button onclick="location.reload()" style="margin-top:16px;padding:12px 24px;border-radius:12px;background:#ff6a00;border:0;font-weight:800">ريفرش</button></div>'
    return
  }
  createApp(App).use(router).mount('#app')
}

// installable app: service worker only in production builds (keeps Vite HMR clean in dev)
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
  addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}))
}
boot()
