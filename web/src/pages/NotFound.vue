<template>
  <section class="wrap nf">
    <span class="num big">404</span>
    <h1>الصفحة دي مش موجودة</h1>
    <p class="muted">يمكن اللينك اتغيّر. ارجع للرئيسية أو شوف المكتبة.</p>
    <div class="btns">
      <RouterLink to="/" class="btn btn-primary">الرئيسية</RouterLink>
      <RouterLink to="/library" class="btn btn-ghost">المكتبة</RouterLink>
    </div>
  </section>
</template>

<style scoped>
.nf { padding-block: 60px; display: grid; gap: 12px; justify-items: start; }
.big { font-size: clamp(7rem, 30vw, 12rem); line-height: .8; color: transparent; -webkit-text-stroke: 3px var(--orange); }
h1 { font-size: clamp(2rem, 7vw, 3rem); }
.btns { display: flex; gap: 10px; flex-wrap: wrap; }
</style>
