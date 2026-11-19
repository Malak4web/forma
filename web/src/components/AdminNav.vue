<template>
  <nav class="anav no-scrollbar" aria-label="لوحة الأدمن">
    <RouterLink to="/admin" exact-active-class="router-link-active">نظرة عامة</RouterLink>
    <RouterLink to="/admin/exercises">الفيديوهات والتمارين</RouterLink>
    <RouterLink to="/admin/programs">البرامج</RouterLink>
    <RouterLink to="/admin/members">الأعضاء</RouterLink>
    <RouterLink to="/admin/subscriptions">الاشتراكات</RouterLink>
    <RouterLink to="/admin/followups">المتابعات</RouterLink>
    <RouterLink to="/admin/orders">الطلبات</RouterLink>
    <RouterLink to="/admin/transformations">التحولات</RouterLink>
    <RouterLink to="/admin/settings">الأسعار والدفع</RouterLink>
  </nav>
</template>

<style scoped>
.anav {
  display: flex;
  gap: 6px;
  padding: 5px;
  border-radius: 14px;
  background: #141414;
  border: 1px solid #222;
  overflow-x: auto;
  max-width: 100%;
}
.anav a {
  padding: 8px 14px;
  border-radius: 10px;
  font-weight: 800;
  font-size: .88rem;
  color: var(--muted);
  white-space: nowrap;
  flex-shrink: 0;
  transition: background .15s, color .15s;
}
.anav a:hover { color: #fff; background: #1e1e1e; }
.anav a.router-link-active { background: var(--orange); color: #0a0a0a; }
</style>
