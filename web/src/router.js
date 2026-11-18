import { createRouter, createWebHistory } from 'vue-router'
import { store, ui } from './stores/app.js'

const p = (name) => () => import(`./pages/${name}.vue`)

const routes = [
  { path: '/', component: p('Home'), meta: { title: 'اتمرن صح من البيت', overlay: true, tab: true } },
  { path: '/library', component: p('Library'), meta: { title: 'مكتبة التمارين', short: 'المكتبة', tab: true } },
  { path: '/library/:id', component: p('Exercise'), meta: { title: 'تمرين' } },
  { path: '/programs', component: p('Programs'), meta: { title: 'البرامج', tab: true } },
  { path: '/programs/:id', component: p('Program'), meta: { title: 'برنامج' } },
  { path: '/calculator', component: p('CalculatorPage'), meta: { title: 'احسب سعراتك ببلاش', short: 'الحاسبة' } },
  { path: '/free-plan', component: p('FreePlan'), meta: { title: 'نظامك المبدئي' } },
  { path: '/pricing', component: p('Pricing'), meta: { title: 'الباقات' } },
  { path: '/register', component: p('Register'), meta: { title: 'حساب جديد', guest: true, bare: true } },
  { path: '/login', component: p('Login'), meta: { title: 'دخول', guest: true, bare: true } },
  { path: '/checkout/:plan', component: p('Checkout'), meta: { title: 'الدفع', auth: true, bare: true } },
  { path: '/checkout/result/:order', component: p('CheckoutResult'), meta: { title: 'حالة الطلب', auth: true, bare: true } },
  { path: '/account', component: p('Account'), meta: { title: 'حسابي', auth: true, tab: true } },
  { path: '/workout/:program/:day', component: p('Workout'), meta: { title: 'تمرين اليوم', bare: true } },
  { path: '/admin/orders', component: p('AdminOrders'), meta: { title: 'الطلبات (أدمن)', auth: true, admin: true } },
  { path: '/admin/settings', component: p('AdminSettings'), meta: { title: 'الأسعار والدفع (أدمن)', auth: true, admin: true } },
  { path: '/admin/transformations', component: p('AdminTransformations'), meta: { title: 'التحولات (أدمن)', auth: true, admin: true } },
  { path: '/:pathMatch(.*)*', component: p('NotFound'), meta: { title: 'الصفحة مش موجودة' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, saved) {
    if (saved) return saved
    if (to.hash) return { el: to.hash, top: 80 }
    return { top: 0 }
  },
})

router.beforeEach((to) => {
  if (to.meta.auth && !store.user) return { path: to.meta.admin ? '/login' : '/register', query: { next: to.fullPath } }
  if (to.meta.admin && !store.user?.is_admin) return '/account'
  if (to.meta.guest && store.user) return to.query.next || '/account'
})

// history depth lets the app header decide between "back" and a sensible parent link
let depth = 0
router.afterEach((to, from, failure) => {
  if (!failure) depth = window.history.state?.position ?? depth
  document.title = `${to.meta.title} | فورمة`
  ui.title = to.meta.short || to.meta.title
})
export const canGoBack = () => depth > 0

export default router
