<script setup>
import Logo from './Logo.vue'
import SocialLinks from './SocialLinks.vue'
</script>

<template>
  <footer class="foot">
    <div class="wrap foot-in">
      <div class="brand">
        <Logo :size="38" />
        <p>تمارين قصيرة من البيت مع الكوتش المقنّع. اتمرن صح، وخلّي جسمك فورمة.</p>
      </div>
      <nav class="col" aria-label="الموقع">
        <b>الموقع</b>
        <RouterLink to="/library">المكتبة</RouterLink>
        <RouterLink to="/programs">البرامج</RouterLink>
        <RouterLink to="/training">أنظمة التدريب</RouterLink>
        <RouterLink to="/diets">أنظمة الأكل</RouterLink>
        <RouterLink to="/calculator">احسب سعراتك</RouterLink>
        <RouterLink to="/pricing">الباقات</RouterLink>
      </nav>
      <SocialLinks title="تابعنا" class="col-s" />
    </div>
    <div class="wrap legal">
      <small>المعلومات هنا للتوعية ومش بديل عن الدكتور. لو عندك وجع شديد أو مستمر، اكشف.</small>
      <small>© <span class="num">2026</span> فورمة</small>
    </div>
  </footer>
</template>

<style scoped>
.foot { margin-top: 72px; border-top: 1px solid #1a1a1a; background: #0d0d0d; padding-bottom: 24px; }
.foot-in { display: grid; gap: 28px; padding-block: 44px 28px; }
.brand { display: grid; gap: 12px; max-width: 340px; }
.brand p { color: var(--muted); font-size: .92rem; }
.col { display: grid; gap: 8px; align-content: start; font-size: .95rem; }
.col b { font-family: var(--f-head); font-weight: 400; font-size: 1.2rem; }
.col a { color: var(--muted); }
.col a:hover { color: var(--orange); }
.legal { display: flex; flex-wrap: wrap; gap: 8px 20px; justify-content: space-between; border-top: 1px solid #1a1a1a; padding-top: 18px; color: var(--muted); }
@media (min-width: 760px) { .foot-in { grid-template-columns: 1.6fr 1fr 1.4fr; } }
</style>
