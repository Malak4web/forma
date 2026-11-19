import { createRouter, createWebHistory } from 'vue-router'
import { store, ui } from './stores/app.js'
import { MOBILE } from './mobile/device.js'

const p = (name) => () => import(`./pages/${name}.vue`)
// phones get the app screen when one exists (meta.m), otherwise the website page inside the app shell
const pick = (desk, mob) => (MOBILE && mob ? () => import(`./mobile/${mob}.vue`) : p(desk))

const routes = [
  { path: '/', component: pick('Home', 'MHome'), meta: { m: true, title: 'اتمرن صح من البيت', overlay: true, tab: true } },
  { path: '/library', component: pick('Library', 'MLibrary'), meta: { m: true, title: 'مكتبة التمارين', short: 'المكتبة', tab: true } },
  { path: '/library/:id', component: pick('Exercise', 'MExercise'), meta: { m: true, title: 'تمرين' } },
  { path: '/programs', component: pick('Programs', 'MPrograms'), meta: { m: true, title: 'البرامج', tab: true } },
  { path: '/programs/:id', component: pick('Program', 'MProgram'), meta: { m: true, title: 'برنامج' } },
  { path: '/calculator', component: pick('CalculatorPage', 'MCalculator'), meta: { m: true, title: 'احسب سعراتك ببلاش', short: 'الحاسبة' } },
  { path: '/free-plan', component: pick('FreePlan', 'MFreePlan'), meta: { m: true, title: 'نظامك المبدئي' } },
  { path: '/pricing', component: pick('Pricing', 'MPricing'), meta: { m: true, title: 'الباقات' } },
  { path: '/register', component: pick('Register', 'MAuth'), meta: { m: true, title: 'حساب جديد', guest: true, bare: true } },
  { path: '/login', component: pick('Login', 'MAuth'), meta: { m: true, title: 'دخول', guest: true, bare: true } },
  { path: '/checkout/:plan', component: pick('Checkout', 'MCheckout'), meta: { m: true, title: 'الدفع', auth: true, bare: true } },
  { path: '/checkout/result/:order', component: pick('CheckoutResult', 'MOrder'), meta: { m: true, title: 'حالة الطلب', auth: true, bare: true } },
  { path: '/account', component: pick('Account', 'MAccount'), meta: { m: true, title: 'حسابي', auth: true, tab: true } },
  { path: '/workout/:program/:day', component: p('Workout'), meta: { m: true, title: 'تمرين اليوم', bare: true } },
  { path: '/admin/claim', component: p('AdminClaim'), meta: { title: 'تفعيل الأدمن', auth: true } },
  { path: '/admin', component: p('AdminDashboard'), meta: { title: 'لوحة التحكم (أدمن)', auth: true, admin: true } },
  { path: '/admin/exercises', component: p('AdminExercises'), meta: { title: 'الفيديوهات والتمارين (أدمن)', auth: true, admin: true } },
  { path: '/admin/programs', component: p('AdminPrograms'), meta: { title: 'البرامج (أدمن)', auth: true, admin: true } },
  { path: '/admin/members', component: p('AdminMembers'), meta: { title: 'الأعضاء (أدمن)', auth: true, admin: true } },
  { path: '/admin/subscriptions', component: p('AdminSubscriptions'), meta: { title: 'الاشتراكات (أدمن)', auth: true, admin: true } },
  { path: '/admin/followups', component: p('AdminFollowUps'), meta: { title: 'المتابعات (أدمن)', auth: true, admin: true } },
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
