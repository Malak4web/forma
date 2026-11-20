// Approximate calorie + starter meal plan (Mifflin-St Jeor). Numbers are rough guidance, not medical advice.
export const goals = [
  { id: 'loss', label: 'تخسيس', d: -500 },
  { id: 'cut', label: 'تنشيف', d: -300 },
  { id: 'gain', label: 'عضل', d: +300 },
]
export const activities = [
  { v: 1.2, label: 'قاعد معظم اليوم' },
  { v: 1.375, label: 'بتتحرك شوية' },
  { v: 1.55, label: 'بتتمرن 3–5 مرات' },
  { v: 1.725, label: 'بتتمرن كل يوم' },
]

export function calcCalories({ sex, age, weight, height, act, goal }, split = null) {
  if (!age || !weight || !height) return null
  const bmr = 10 * weight + 6.25 * height - 5 * age + (sex === 'm' ? 5 : -161)
  const floor = sex === 'm' ? 1500 : 1200
  const g = goals.find((x) => x.id === goal) || goals[1]
  const kcal = Math.round(Math.max(floor, bmr * act + g.d) / 50) * 50
  if (split) {
    // the chosen diet decides the macro split (percent of calories)
    return { kcal, protein: Math.round((kcal * split.p) / 400), carbs: Math.round((kcal * split.c) / 400), fat: Math.round((kcal * split.f) / 900) }
  }
  const protein = Math.round(weight * (goal === 'gain' ? 1.8 : 1.6))
  const fat = Math.round((kcal * 0.27) / 9)
  const carbs = Math.max(0, Math.round((kcal - protein * 4 - fat * 9) / 4))
  return { kcal, protein, fat, carbs }
}

// Simple Egyptian-friendly starter day, scaled by calorie share.
const MEALS = [
  { id: 'b', name: 'الفطار', share: 0.25, items: ['بيضتين مسلوقين', 'رغيف بلدي صغير أو نص رغيف', 'خيار وطماطم'] },
  { id: 'l', name: 'الغدا', share: 0.35, items: ['صدر فراخ مشوي أو سمك', 'رز أو مكرونة (كوباية مطبوخة تقريبًا)', 'سلطة خضرا كبيرة'] },
  { id: 's', name: 'سناك', share: 0.15, items: ['زبادي', 'فاكهة (تفاحة أو موزة)'] },
  { id: 'd', name: 'العشا', share: 0.25, items: ['فول أو جبنة قريش', 'رغيف بلدي صغير', 'خضار'] },
]
export function starterDay(kcal) {
  return MEALS.map((m) => ({ ...m, kcal: Math.round((kcal * m.share) / 10) * 10 }))
}
