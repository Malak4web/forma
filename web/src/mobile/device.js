// Phones get the app UI (src/mobile/*), wider screens the website (src/pages/*).
// Decided once at boot so route components resolve to one set; crossing the breakpoint reloads.
const query = typeof matchMedia === 'function' ? matchMedia('(max-width: 959px)') : null

export const MOBILE = !!query?.matches

query?.addEventListener?.('change', (e) => {
  if (e.matches !== MOBILE) location.reload()
})
