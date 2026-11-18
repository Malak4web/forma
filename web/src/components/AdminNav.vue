<template>
  <nav class="anav" aria-label="لوحة الأدمن">
    <RouterLink to="/admin/orders">الطلبات</RouterLink>
    <RouterLink to="/admin/settings">الأسعار والدفع</RouterLink>
    <RouterLink to="/admin/transformations">التحولات</RouterLink>
  </nav>
</template>

<style scoped>
.anav { display: flex; gap: 6px; padding: 4px; border-radius: 12px; background: #141414; border: 1px solid #222; justify-self: start; }
.anav a { padding: 8px 14px; border-radius: 9px; font-weight: 800; font-size: .9rem; color: var(--muted); }
.anav a.router-link-active { background: #fff; color: #0a0a0a; }
</style>
