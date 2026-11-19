// Background motifs per diet identity, drawn as tiny SVG tiles tinted with the diet's accent.
const enc = (svg) => `url("data:image/svg+xml,${encodeURIComponent(svg.replace(/\s+/g, ' '))}")`

const tiles = {
  // keto: fat-molecule hexagons
  hex: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='56' height='48' viewBox='0 0 56 48'><path d='M14 2l12 7v14l-12 7L2 23V9z M42 26l12 7v14' fill='none' stroke='${c}' stroke-width='1.4'/></svg>`,
  // fasting: night sky
  stars: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='90' height='90'><g fill='${c}'><circle cx='10' cy='14' r='1.2'/><circle cx='52' cy='8' r='.8'/><circle cx='74' cy='40' r='1.4'/><circle cx='30' cy='60' r='.9'/><circle cx='64' cy='78' r='1.1'/><circle cx='12' cy='84' r='.7'/></g></svg>`,
  // low carb: crossed wheat
  wheat: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><g fill='none' stroke='${c}' stroke-width='1.3' stroke-linecap='round'><path d='M32 12v40M32 20c-5-2-7-6-7-6M32 28c-5-2-7-6-7-6M32 20c5-2 7-6 7-6M32 28c5-2 7-6 7-6'/><path d='M14 50L50 14' stroke-width='1.6'/></g></svg>`,
  // carnivore: butcher-paper diagonal cuts
  butcher: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='40' height='40'><path d='M0 40L40 0M-10 10L10 -10M30 50L50 30' stroke='${c}' stroke-width='1' /></svg>`,
  // mediterranean: sea waves
  waves: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='80' height='24'><path d='M0 12c10 0 10-8 20-8s10 8 20 8 10-8 20-8 10 8 20 8' fill='none' stroke='${c}' stroke-width='1.4'/></svg>`,
  // plant based: leaves
  leaves: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='70' height='70'><g fill='none' stroke='${c}' stroke-width='1.3'><path d='M14 40c0-14 12-24 26-24 0 14-12 24-26 24z M14 40l20-18'/><path d='M46 66c0-9 7-15 16-15 0 9-7 15-16 15z'/></g></svg>`,
  // high protein: weight plates
  plates: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='60' height='60'><g fill='none' stroke='${c}' stroke-width='1.3'><circle cx='30' cy='30' r='14'/><circle cx='30' cy='30' r='4'/></g></svg>`,
  // dash: heartbeat
  pulse: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='120' height='40'><path d='M0 20h40l6-12 8 26 6-14h60' fill='none' stroke='${c}' stroke-width='1.4'/></svg>`,
  // carb cycling: alternating bars
  cycle: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='48' height='48'><g fill='${c}'><rect x='6' y='10' width='6' height='30' rx='3'/><rect x='21' y='26' width='6' height='14' rx='3'/><rect x='36' y='18' width='6' height='22' rx='3'/></g></svg>`,
  // tayyibat: 8-point arabesque star
  arabesque: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='72' height='72' viewBox='0 0 72 72'><g fill='none' stroke='${c}' stroke-width='1.2'><path d='M36 14l6 9 10-2-2 10 9 6-9 6 2 10-10-2-6 9-6-9-10 2 2-10-9-6 9-6-2-10 10 2z'/><circle cx='36' cy='36' r='6'/></g></svg>`,
  // paleo: stones
  stone: (c) => `<svg xmlns='http://www.w3.org/2000/svg' width='80' height='80'><g fill='none' stroke='${c}' stroke-width='1.2'><path d='M10 30c4-10 18-12 24-4s-2 18-12 16-14-4-12-12z'/><path d='M50 60c2-8 14-10 20-4s0 14-8 14-14-2-12-10z'/></g></svg>`,
}

export function motifBg(motif, color, alpha = 0.18) {
  const hex = color.replace('#', '')
  const r = parseInt(hex.slice(0, 2), 16), g = parseInt(hex.slice(2, 4), 16), b = parseInt(hex.slice(4, 6), 16)
  const c = `rgba(${r},${g},${b},${alpha})`
  return tiles[motif] ? enc(tiles[motif](c)) : 'none'
}
