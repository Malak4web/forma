// Thin fetch wrapper for the Laravel API.
// Same-origin through the Vite proxy (/api → Laravel), so Laravel's own session cookie + CSRF just work.
function xsrf() {
  const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  return m ? decodeURIComponent(m[1]) : ''
}

export class ApiError extends Error {
  constructor(status, data) {
    super(data?.message || 'حصلت مشكلة، جرّب تاني')
    this.status = status
    this.errors = data?.errors || {}
    this.data = data
  }
}

let csrfReady = null
async function ensureCsrf() {
  if (xsrf()) return
  csrfReady ||= fetch('/api/csrf', { credentials: 'same-origin' }).finally(() => (csrfReady = null))
  await csrfReady
}

export async function api(path, { method = 'GET', body, form } = {}) {
  const write = method !== 'GET'
  if (write) await ensureCsrf()
  const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  if (write) headers['X-XSRF-TOKEN'] = xsrf()
  let payload
  if (form) payload = form
  else if (body !== undefined) { headers['Content-Type'] = 'application/json'; payload = JSON.stringify(body) }

  let res = await fetch('/api' + path, { method, headers, body: payload, credentials: 'same-origin' })
  if (res.status === 419 && write) { // CSRF token expired → refresh once
    await fetch('/api/csrf', { credentials: 'same-origin' })
    headers['X-XSRF-TOKEN'] = xsrf()
    res = await fetch('/api' + path, { method, headers, body: payload, credentials: 'same-origin' })
  }
  const data = res.status === 204 ? null : await res.json().catch(() => null)
  if (!res.ok) throw new ApiError(res.status, data)
  return data
}

// first error message per field → { field: 'msg' }
export const fieldErrors = (e) => Object.fromEntries(Object.entries(e?.errors || {}).map(([k, v]) => [k, v[0]]))
