// أنظمة التدريب — famous training systems, each with a 4-week detailed program.
// Exercise names stay in English (brand rule). `vid` links to a FORMA library video when we have one.
// Every program: 7-day split → day templates → weekly progression (week 4 = lighter/deload where it applies).

const ex = (n, sets, reps, rest, note, vid) => ({ n, sets, reps, rest, note, vid })

const HYPER_WEEKS = [
  { t: 'تأسيس', d: 'وزن مريح — آخر عدّة تبقى فاضل فيك 3 عدّات.', sets: 0 },
  { t: 'زيادة حجم', d: 'زوّد مجموعة على أول تمرينين.', sets: 1, first: 2 },
  { t: 'ذروة', d: 'زوّد الوزن شوية — آخر عدّة تبقى صعبة بجد.', sets: 1, first: 2 },
  { t: 'ديلود', d: 'نفس التمارين بمجموعات أقل ووزن أخف عشان جسمك يرتاح.', sets: -1 },
]

export const TRAININGS = [
  /* ─────────────── FULL BODY ─────────────── */
  {
    slug: 'full-body', name: 'فول بادي', en: 'FULL BODY', emoji: '🔥',
    tagline: 'الجسم كله في كل تمرينة، 3 أيام في الأسبوع — أحسن بداية لأي حد.',
    theme: { bg: '#0f0d08', surface: '#1b1710', ink: '#fff4e0', a: '#ff6a00', b: '#ffb000', motif: 'hex', mode: 'dark' },
    visual: 'split', place: 'gym', goal: ['muscle', 'start'], daysPerWeek: 3, minutes: '50–60', level: 'مبتدئ', levelN: 1,
    tags: ['مبتدئ', 'جيم'],
    how: [
      { t: 'تمرينتين A و B بالتبادل', d: 'A الاتنين، B الأربع، A الجمعة — والأسبوع اللي بعده بالعكس.' },
      { t: 'تمرين مركّب لكل عضلة كبيرة', d: 'Squat، Bench، Row، Deadlift — دول اللي بيبنوا الأساس.' },
      { t: 'راحة يوم بين كل تمرينة', d: 'العضلة بتكبر في الراحة مش في الجيم.' },
    ],
    goodFor: ['المبتدئين في الجيم', 'اللي وقته 3 أيام بس', 'اللي راجع بعد انقطاع'],
    notFor: ['المتقدم اللي محتاج حجم أكبر لكل عضلة'],
    split: ['A', 'rest', 'B', 'rest', 'A', 'rest', 'rest'],
    splitAlt: ['B', 'rest', 'A', 'rest', 'B', 'rest', 'rest'],
    days: {
      A: { title: 'فول بادي A', items: [ex('Squat', 3, '8', 120, 'الركبة في اتجاه الصوابع', 'squat'), ex('Bench Press', 3, '8', 120), ex('Barbell Row', 3, '8', 90), ex('Overhead Press', 2, '10', 90), ex('Plank', 3, '40 ث', 45, '', 'crunches')] },
      B: { title: 'فول بادي B', items: [ex('Deadlift', 3, '5', 150, 'ضهرك مفرود طول الحركة'), ex('Incline Dumbbell Press', 3, '10', 90), ex('Lat Pulldown', 3, '10', 90), ex('Walking Lunges', 2, '12 لكل رجل', 75), ex('Hanging Knee Raise', 3, '12', 60)] },
    },
    weeks: HYPER_WEEKS,
    warning: 'سخّن 5–10 دقايق قبل أي تمرينة. لو حسّيت وجع في مفصل (مش تعب عضلة)، وقّف واسأل مدرب أو دكتور.',
  },

  /* ─────────────── UPPER / LOWER ─────────────── */
  {
    slug: 'upper-lower', name: 'أبر / لوور', en: 'UPPER / LOWER', emoji: '⚖️',
    tagline: 'يوم للنص الفوقاني ويوم للتحتاني — 4 أيام بتوازن مثالي بين الحجم والراحة.',
    theme: { bg: '#081320', surface: '#0f1f33', ink: '#e3f0ff', a: '#3b9eff', b: '#ff6a00', motif: 'plates', mode: 'dark' },
    visual: 'split', place: 'gym', goal: ['muscle', 'strength'], daysPerWeek: 4, minutes: '60', level: 'متوسط', levelN: 2,
    tags: ['متوسط', 'جيم'],
    how: [
      { t: '4 أيام: فوق، تحت، راحة، فوق، تحت', d: 'كل عضلة بتتمرّن مرتين في الأسبوع.' },
      { t: 'يوم تقيل ويوم حجم', d: 'أول نص الأسبوع عدّات قليلة ووزن تقيل، والتاني عدّات أكتر.' },
      { t: 'تمرين أساسي في الأول', d: 'Bench أو Squat الأول وانت فايق، والعزل في الآخر.' },
    ],
    goodFor: ['اللي عدّى أول 3–6 شهور', 'اللي عايز قوة وعضل مع بعض'],
    notFor: ['اللي مش هيلتزم 4 أيام'],
    split: ['U1', 'L1', 'rest', 'U2', 'L2', 'rest', 'rest'],
    days: {
      U1: { title: 'فوق — تقيل', items: [ex('Bench Press', 4, '6', 150), ex('Barbell Row', 4, '6', 120), ex('Overhead Press', 3, '8', 90), ex('Pull-Ups', 3, 'أقصى عدد', 90), ex('Barbell Curl', 2, '10', 60), ex('Triceps Pushdown', 2, '12', 60)] },
      L1: { title: 'تحت — تقيل', items: [ex('Squat', 4, '6', 150, '', 'squat'), ex('Romanian Deadlift', 3, '8', 120), ex('Leg Press', 3, '10', 90), ex('Leg Curl', 3, '12', 60), ex('Calf Raises', 4, '15', 45)] },
      U2: { title: 'فوق — حجم', items: [ex('Incline Dumbbell Press', 4, '10', 90), ex('Seated Cable Row', 4, '10', 90), ex('Lateral Raises', 4, '15', 45), ex('Lat Pulldown', 3, '12', 75), ex('Hammer Curl', 3, '12', 60), ex('Overhead Triceps Extension', 3, '12', 60)] },
      L2: { title: 'تحت — حجم', items: [ex('Bulgarian Split Squat', 3, '10 لكل رجل', 90), ex('Hip Thrust', 4, '12', 90, '', 'bridge'), ex('Leg Extension', 3, '15', 60), ex('Leg Curl', 3, '15', 60), ex('Hanging Knee Raise', 3, '15', 60)] },
    },
    weeks: HYPER_WEEKS,
    warning: 'زوّد الأوزان تدريجيًا. التكنيك أهم من الرقم على البار.',
  },

  /* ─────────────── PUSH PULL LEGS ─────────────── */
  {
    slug: 'push-pull-legs', name: 'بوش بول ليجز', en: 'PUSH · PULL · LEGS', emoji: '🔁',
    tagline: 'زقّ، شدّ، رجل — وكرّرهم. أشهر تقسيمة في الجيم لبناء العضل.',
    theme: { bg: '#130816', surface: '#211027', ink: '#fbe9ff', a: '#d946ef', b: '#22d3ee', motif: 'cycle', mode: 'dark' },
    visual: 'split', place: 'gym', goal: ['muscle'], daysPerWeek: 6, minutes: '60–75', level: 'متقدم', levelN: 3,
    tags: ['عضل', '6 أيام'],
    how: [
      { t: 'Push: صدر وكتف وتراي', d: 'كل عضلات «الزقّ» في يوم واحد.' },
      { t: 'Pull: ضهر وباي', d: 'كل عضلات «الشدّ» في يوم.' },
      { t: 'Legs: الرجل كلها', d: 'وبعدها تعيد الدورة، ويوم راحة في الآخر.' },
    ],
    goodFor: ['المتقدم اللي عايز حجم', 'اللي فاضي 6 أيام'],
    notFor: ['المبتدئ', 'اللي نومه وأكله مش منتظمين'],
    split: ['PUSH', 'PULL', 'LEGS', 'PUSH', 'PULL', 'LEGS', 'rest'],
    days: {
      PUSH: { title: 'Push — زقّ', items: [ex('Bench Press', 4, '6–8', 150), ex('Overhead Press', 3, '8–10', 120), ex('Incline Dumbbell Press', 3, '10', 90), ex('Lateral Raises', 4, '15', 45), ex('Dips', 3, '10', 90), ex('Overhead Triceps Extension', 3, '12', 60)] },
      PULL: { title: 'Pull — شدّ', items: [ex('Deadlift', 3, '5', 180), ex('Pull-Ups', 4, 'أقصى عدد', 120), ex('Seated Cable Row', 3, '10', 90), ex('Face Pulls', 3, '15', 60), ex('Barbell Curl', 3, '10', 60), ex('Hammer Curl', 3, '12', 60)] },
      LEGS: { title: 'Legs — رجل', items: [ex('Squat', 4, '6–8', 180, '', 'squat'), ex('Romanian Deadlift', 3, '10', 120), ex('Bulgarian Split Squat', 3, '10 لكل رجل', 90), ex('Leg Extension', 3, '12', 60), ex('Leg Curl', 3, '12', 60), ex('Calf Raises', 4, '15', 45)] },
    },
    weeks: HYPER_WEEKS,
    warning: '6 أيام حمل كبير — النوم 7–8 ساعات والأكل الكافي جزء من البرنامج. لو حسّيت إرهاق مستمر، خد يوم راحة زيادة.',
  },

  /* ─────────────── BRO SPLIT ─────────────── */
  {
    slug: 'bro-split', name: 'برو سبليت', en: 'BRO SPLIT', emoji: '💪',
    tagline: 'كل عضلة ليها يومها — صدر، ضهر، كتف، رجل، دراع. الكلاسيك بتاع كمال الأجسام.',
    theme: { bg: '#170b05', surface: '#26140a', ink: '#ffeede', a: '#ff7a1a', b: '#ffd166', motif: 'butcher', mode: 'dark' },
    visual: 'split', place: 'gym', goal: ['muscle'], daysPerWeek: 5, minutes: '60', level: 'متوسط', levelN: 2,
    tags: ['كمال أجسام', '5 أيام'],
    how: [
      { t: 'عضلة واحدة في اليوم', d: 'بتديها حجم كبير وتسيبها ترتاح أسبوع.' },
      { t: '4–5 تمارين للعضلة', d: 'من زوايا مختلفة عشان تكبر من كل ناحية.' },
      { t: 'ضغط عالي وبامب', d: 'عدّات 8–12 وراحة 60–90 ثانية.' },
    ],
    goodFor: ['اللي بيحب يركّز على عضلة في اليوم', 'المتوسط اللي عايز بامب'],
    notFor: ['المبتدئ الخالص', 'اللي ممكن يفوّت أيام (العضلة هتتنسي أسبوع)'],
    split: ['CHEST', 'BACK', 'SHOULDERS', 'LEGS', 'ARMS', 'rest', 'rest'],
    days: {
      CHEST: { title: 'صدر', items: [ex('Bench Press', 4, '8', 120), ex('Incline Dumbbell Press', 4, '10', 90), ex('Cable Fly', 3, '12', 60), ex('Dips', 3, '10', 90), ex('Push-Ups', 2, 'أقصى عدد', 60, '', 'pushups')] },
      BACK: { title: 'ضهر', items: [ex('Pull-Ups', 4, 'أقصى عدد', 120), ex('Barbell Row', 4, '8', 120), ex('Lat Pulldown', 3, '12', 75), ex('Seated Cable Row', 3, '12', 75), ex('Back Extension', 3, '15', 60)] },
      SHOULDERS: { title: 'كتف', items: [ex('Overhead Press', 4, '8', 120), ex('Lateral Raises', 4, '15', 45), ex('Rear Delt Fly', 3, '15', 45), ex('Face Pulls', 3, '15', 60), ex('Shrugs', 3, '12', 60)] },
      LEGS: { title: 'رجل', items: [ex('Squat', 4, '8', 150, '', 'squat'), ex('Leg Press', 4, '12', 90), ex('Romanian Deadlift', 3, '10', 120), ex('Leg Curl', 3, '12', 60), ex('Calf Raises', 4, '15', 45)] },
      ARMS: { title: 'دراع', items: [ex('Barbell Curl', 4, '10', 60), ex('Close-Grip Bench Press', 4, '8', 90), ex('Hammer Curl', 3, '12', 60), ex('Triceps Pushdown', 3, '12', 60), ex('Concentration Curl', 2, '15', 45)] },
    },
    weeks: HYPER_WEEKS,
    warning: 'ابدأ بأوزان تسيبك تعمل العدد بتكنيك نضيف. الوجع العضلي بعد يومين طبيعي — وجع المفصل لأ.',
  },

  /* ─────────────── 5×5 STRENGTH ─────────────── */
  {
    slug: 'five-by-five', name: 'قوة 5×5', en: '5 × 5 STRENGTH', emoji: '🏋️',
    tagline: '3 تمارين بس، 5 مجموعات × 5 عدّات، وتزوّد الوزن كل مرة. أبسط طريق للقوة.',
    theme: { bg: '#0d0d0d', surface: '#191919', ink: '#f2f2f2', a: '#e11d48', b: '#f5f5f5', motif: 'plates', mode: 'dark' },
    visual: 'grid', gridN: 5, place: 'gym', goal: ['strength', 'start'], daysPerWeek: 3, minutes: '45', level: 'مبتدئ', levelN: 1,
    tags: ['قوة', 'بار'],
    how: [
      { t: 'تمرينة A و B بالتبادل', d: 'A: Squat + Bench + Row — B: Squat + Overhead Press + Deadlift.' },
      { t: 'زوّد 2.5 كيلو كل تمرينة', d: 'لو خلّصت الـ 5×5 كاملين، المرة الجاية زوّد. لو لأ، كرّر نفس الوزن.' },
      { t: 'راحة طويلة', d: '2–3 دقايق بين المجموعات. القوة محتاجة نَفَس.' },
    ],
    goodFor: ['المبتدئ اللي عايز يبقى قوي', 'اللي بيحب الأرقام والتقدّم الواضح'],
    notFor: ['اللي عنده إصابة ضهر أو ركبة من غير متابعة'],
    split: ['A', 'rest', 'B', 'rest', 'A', 'rest', 'rest'],
    splitAlt: ['B', 'rest', 'A', 'rest', 'B', 'rest', 'rest'],
    days: {
      A: { title: '5×5 — A', items: [ex('Squat', 5, '5', 180, '', 'squat'), ex('Bench Press', 5, '5', 180), ex('Barbell Row', 5, '5', 150)] },
      B: { title: '5×5 — B', items: [ex('Squat', 5, '5', 180, '', 'squat'), ex('Overhead Press', 5, '5', 180), ex('Deadlift', 1, '5', 240, 'مجموعة واحدة تقيلة بعد التسخين')] },
    },
    weeks: [
      { t: 'بداية خفيفة', d: 'ابدأ بنص الوزن اللي تقدر عليه وركّز على التكنيك.', sets: 0 },
      { t: 'زوّد كل مرة', d: '+2.5 كجم كل تمرينة (و+5 للـ Deadlift).', sets: 0 },
      { t: 'كمّل الزيادة', d: 'لو فشلت مرتين في نفس الوزن، نزّل 10% وابدأ تطلع تاني.', sets: 0 },
      { t: 'ثبّت', d: 'كمّل الزيادة لو قادر — النوم والأكل هما اللي بيحددوا.', sets: 0 },
    ],
    warning: 'التمارين دي تقيلة على الضهر والركبة. اتعلّم التكنيك من مدرب الأول، وابدأ بوزن خفيف.',
  },

  /* ─────────────── GERMAN VOLUME TRAINING ─────────────── */
  {
    slug: 'german-volume', name: 'جيرمان فوليوم', en: 'GERMAN VOLUME 10×10', emoji: '🧱',
    tagline: '10 مجموعات × 10 عدّات من نفس التمرين. حجم ضخم لكسر الثبات.',
    theme: { bg: '#121206', surface: '#1f1f0c', ink: '#fbfbe4', a: '#facc15', b: '#a3e635', motif: 'stone', mode: 'dark' },
    visual: 'grid', gridN: 10, place: 'gym', goal: ['muscle'], daysPerWeek: 4, minutes: '60', level: 'متقدم', levelN: 4,
    tags: ['متقدم', 'كسر الثبات'],
    how: [
      { t: '10×10 بـ 60% من أقصى وزنك', d: 'وزن تقدر تعمله 20 مرة — الصعوبة بتيجي من العدد.' },
      { t: 'تمرينين بالتبادل (سوبر سِت)', d: 'A1 و A2 لعضلتين عكس بعض، وراحة 90 ثانية بينهم.' },
      { t: 'تمارين تكميلية خفيفة', d: '3×10–12 لعضلتين صغيرين في الآخر.' },
    ],
    goodFor: ['المتقدم اللي وقف عنده الحجم', 'فترة 4–6 أسابيع بس'],
    notFor: ['المبتدئ', 'اللي في تنشيف قاسي'],
    split: ['CB', 'LEGS', 'rest', 'AS', 'rest', 'LEGS2', 'rest'],
    days: {
      CB: { title: 'صدر وضهر', items: [ex('Incline Dumbbell Press', 10, '10', 90, 'A1 — بالتبادل مع A2'), ex('Chin-Ups', 10, '10', 90, 'A2'), ex('Dumbbell Fly', 3, '12', 60), ex('One-Arm Dumbbell Row', 3, '12', 60)] },
      LEGS: { title: 'رجل وبطن', items: [ex('Squat', 10, '10', 90, 'A1', 'squat'), ex('Leg Curl', 10, '10', 90, 'A2'), ex('Calf Raises', 3, '15', 60), ex('Hanging Knee Raise', 3, '15', 60)] },
      AS: { title: 'دراع وكتف', items: [ex('Dips', 10, '10', 90, 'A1'), ex('Hammer Curl', 10, '10', 90, 'A2'), ex('Lateral Raises', 3, '12', 60), ex('Rear Delt Fly', 3, '12', 60)] },
      LEGS2: { title: 'رجل 2', items: [ex('Leg Press', 10, '10', 90, 'A1'), ex('Romanian Deadlift', 10, '10', 90, 'A2'), ex('Walking Lunges', 3, '12', 60), ex('Plank', 3, '45 ث', 45, '', 'crunches')] },
    },
    weeks: [
      { t: '10×10 بـ 60%', d: 'لو خلّصت الـ 100 عدّة، زوّد 2.5 كجم المرة الجاية.', sets: 0 },
      { t: 'نفس الوزن أو أتقل', d: 'لو وقعت تحت 10 في أي مجموعة، ثبّت الوزن.', sets: 0 },
      { t: 'آخر أسبوع تقيل', d: 'ركّز على النوم والأكل — الحمل عالي.', sets: 0 },
      { t: 'خروج', d: 'نزّل لـ 5×10 بنفس الوزن، وبعدها ارجع لبرنامجك العادي.', sets: -5, first: 2 },
    ],
    warning: 'GVT مرهق جدًا للجهاز العصبي. متكمّلوش أكتر من 6 أسابيع، وخد ديلود بعده.',
  },

  /* ─────────────── HIIT ─────────────── */
  {
    slug: 'hiit', name: 'هيت HIIT', en: 'HIIT', emoji: '⚡',
    tagline: 'شغل عالي قصير وراحة قصيرة — حرق دهون ونَفَس في 20 دقيقة من البيت.',
    theme: { bg: '#1a0a00', surface: '#2a1205', ink: '#fff1e6', a: '#ff6a00', b: '#ffe14d', motif: 'pulse', mode: 'dark' },
    visual: 'interval', work: 40, rest: 20, place: 'home', goal: ['burn'], daysPerWeek: 4, minutes: '20–25', level: 'متوسط', levelN: 2,
    tags: ['حرق', 'من البيت'],
    how: [
      { t: '40 ثانية شغل + 20 راحة', d: 'كل تمرين بأقصى مجهود آمن، وبعدها نَفَس.' },
      { t: '4 تمارين = دايرة', d: 'كرّر الدايرة 4 مرات، وراحة دقيقة بين الدواير.' },
      { t: 'من غير أدوات', d: 'وزن جسمك يكفي — وفيه نسخة من غير نط للجيران.' },
    ],
    goodFor: ['اللي عايز يحرق ووقته قليل', 'تحسين النَفَس واللياقة'],
    notFor: ['اللي عنده مشاكل قلب أو ضغط من غير إذن دكتور', 'الركب الحساسة (استخدم النسخة من غير نط)'],
    split: ['LOWER', 'rest', 'UPPER', 'rest', 'FULL', 'CORE', 'rest'],
    days: {
      LOWER: { title: 'HIIT رجل', format: 'interval', rounds: 4, items: [ex('Squat', null, '40 ث', 20, '', 'squat'), ex('Step Back', null, '40 ث', 20, '', 'stepback'), ex('Jumping Jacks', null, '40 ث', 20, '', 'jacks'), ex('Tabletop Bridge', null, '40 ث', 20, '', 'bridge')] },
      UPPER: { title: 'HIIT فوق', format: 'interval', rounds: 4, items: [ex('Push-Ups', null, '40 ث', 20, '', 'pushups'), ex('Mountain Climbers', null, '40 ث', 20, '', 'climbers'), ex('Pike Push-Ups', null, '40 ث', 20, '', 'pike'), ex('Jumping Jacks', null, '40 ث', 20, '', 'jacks')] },
      FULL: { title: 'HIIT جسم كامل', format: 'interval', rounds: 4, items: [ex('Burpees', null, '40 ث', 20, '', 'burpees'), ex('Squat', null, '40 ث', 20, '', 'squat'), ex('Mountain Climbers', null, '40 ث', 20, '', 'climbers'), ex('Push-Ups', null, '40 ث', 20, '', 'pushups')] },
      CORE: { title: 'HIIT بطن', format: 'interval', rounds: 3, items: [ex('Crunches', null, '40 ث', 20, '', 'crunches'), ex('Russian Twist', null, '40 ث', 20, '', 'twist'), ex('Mountain Climbers', null, '40 ث', 20, '', 'climbers'), ex('Plank', null, '40 ث', 20)] },
    },
    weeks: [
      { t: 'تعوّد', d: '30 ث شغل / 30 راحة، و3 دواير بس.', rounds: -1, work: 30, restS: 30 },
      { t: 'الأساس', d: '40 / 20 و4 دواير.', rounds: 0 },
      { t: 'زوّد', d: '45 / 15 — نفس الدواير.', rounds: 0, work: 45, restS: 15 },
      { t: 'التحدّي', d: '45 / 15 ودايرة زيادة.', rounds: 1, work: 45, restS: 15 },
    ],
    warning: 'HIIT مجهوده عالي. لو حسّيت دوخة أو ألم في الصدر، وقّف فورًا. ولو عندك قلب أو ضغط، اكشف الأول.',
  },

  /* ─────────────── TABATA ─────────────── */
  {
    slug: 'tabata', name: 'تاباتا', en: 'TABATA', emoji: '⏱️',
    tagline: '20 ثانية بأقصى ما عندك و10 راحة × 8 — أربع دقايق بتفرق.',
    theme: { bg: '#04131a', surface: '#0a2330', ink: '#e0fbff', a: '#22d3ee', b: '#ff4d6d', motif: 'pulse', mode: 'dark' },
    visual: 'interval', work: 20, rest: 10, place: 'home', goal: ['burn'], daysPerWeek: 3, minutes: '16–20', level: 'متقدم', levelN: 3,
    tags: ['حرق', 'سريع'],
    how: [
      { t: 'بلوك = 4 دقايق', d: '8 جولات: 20 ثانية شغل بأقصى سرعة + 10 ثواني راحة.' },
      { t: '4 بلوكات في التمرينة', d: 'وراحة دقيقة بين كل بلوك والتاني.' },
      { t: 'تمرين واحد في البلوك', d: 'عشان تركّز على السرعة من غير تفكير.' },
    ],
    goodFor: ['اللي وقته ضيق جدًا', 'المتقدم في اللياقة'],
    notFor: ['المبتدئ الخالص', 'أي حد عنده مشاكل قلب من غير إذن'],
    split: ['T1', 'rest', 'T2', 'rest', 'T3', 'rest', 'rest'],
    days: {
      T1: { title: 'تاباتا 1', format: 'interval', rounds: 8, block: true, items: [ex('Squat', null, '20 ث', 10, 'بلوك 1', 'squat'), ex('Push-Ups', null, '20 ث', 10, 'بلوك 2', 'pushups'), ex('Mountain Climbers', null, '20 ث', 10, 'بلوك 3', 'climbers'), ex('Jumping Jacks', null, '20 ث', 10, 'بلوك 4', 'jacks')] },
      T2: { title: 'تاباتا 2', format: 'interval', rounds: 8, block: true, items: [ex('Burpees', null, '20 ث', 10, 'بلوك 1', 'burpees'), ex('Russian Twist', null, '20 ث', 10, 'بلوك 2', 'twist'), ex('Step Back', null, '20 ث', 10, 'بلوك 3', 'stepback'), ex('Crunches', null, '20 ث', 10, 'بلوك 4', 'crunches')] },
      T3: { title: 'تاباتا 3', format: 'interval', rounds: 8, block: true, items: [ex('Jumping Jacks', null, '20 ث', 10, 'بلوك 1', 'jacks'), ex('Pike Push-Ups', null, '20 ث', 10, 'بلوك 2', 'pike'), ex('Squat', null, '20 ث', 10, 'بلوك 3', 'squat'), ex('Plank', null, '20 ث', 10, 'بلوك 4')] },
    },
    weeks: [
      { t: '3 بلوكات', d: 'ابدأ بـ 3 بلوكات بس.', blocks: 3 },
      { t: '4 بلوكات', d: 'التمرينة الكاملة.', blocks: 4 },
      { t: '4 بلوكات أسرع', d: 'حاول تزوّد العدّات في كل 20 ثانية.', blocks: 4 },
      { t: '5 بلوكات', d: 'كرّر أول بلوك في الآخر.', blocks: 5 },
    ],
    warning: 'تاباتا من أصعب الأنظمة. سخّن كويس، ولو حسّيت دوخة أو ألم صدر وقّف فورًا.',
  },

  /* ─────────────── CALISTHENICS ─────────────── */
  {
    slug: 'calisthenics', name: 'كاليسثنكس', en: 'CALISTHENICS', emoji: '🤸',
    tagline: 'وزن جسمك هو الحديد — من أول Push-Up لحد الـ Muscle-Up.',
    theme: { bg: '#0b1410', surface: '#13221b', ink: '#e7fff2', a: '#34d399', b: '#fbbf24', motif: 'leaves', mode: 'dark' },
    visual: 'ladder', place: 'home', goal: ['muscle', 'strength'], daysPerWeek: 4, minutes: '45', level: 'متوسط', levelN: 2,
    tags: ['وزن الجسم', 'عقلة وبارالل'],
    how: [
      { t: 'تقدّم بالمستويات', d: 'كل تمرين ليه نسخة أسهل وأصعب — اطلع درجة لما تعدّي العدد.' },
      { t: 'زقّ، شدّ، رجل، بطن', d: '4 أيام بتغطّي الجسم كله.' },
      { t: 'محتاج عقلة بس', d: 'وبارالل لو متاح — أو كرسيين ثابتين.' },
    ],
    goodFor: ['اللي بيتمرّن في البيت أو الحديقة', 'اللي عايز قوة وتحكم في جسمه'],
    notFor: ['اللي عنده إصابة كتف أو رسغ'],
    split: ['PUSH', 'PULL', 'rest', 'LEGS', 'CORE', 'rest', 'rest'],
    days: {
      PUSH: { title: 'زقّ', items: [ex('Push-Ups', 4, '12–15', 90, 'أسهل: على الركبة', 'pushups'), ex('Pike Push-Ups', 3, '8–10', 90, '', 'pike'), ex('Dips', 3, '8', 90, 'أسهل: على كرسي'), ex('Diamond Push-Ups', 3, '8', 75)] },
      PULL: { title: 'شدّ', items: [ex('Pull-Ups', 4, '5–8', 120, 'أسهل: Negative نزول بطيء'), ex('Australian Rows', 3, '12', 90), ex('Chin-Ups', 3, '6–8', 120), ex('Dead Hang', 3, '30 ث', 60)] },
      LEGS: { title: 'رجل', items: [ex('Squat', 4, '20', 75, '', 'squat'), ex('Bulgarian Split Squat', 3, '12 لكل رجل', 75), ex('Glute Bridge', 3, '15', 60, '', 'bridge'), ex('Pistol Squat Progression', 3, '5 لكل رجل', 90, 'امسك في باب'), ex('Calf Raises', 3, '20', 45)] },
      CORE: { title: 'بطن وتحكم', items: [ex('Plank', 3, '45 ث', 45), ex('Hollow Body Hold', 3, '30 ث', 45), ex('Hanging Knee Raise', 3, '12', 60), ex('Russian Twist', 3, '20', 45, '', 'twist'), ex('L-Sit Progression', 3, '10–15 ث', 60)] },
    },
    weeks: [
      { t: 'اعرف مستواك', d: 'سجّل أقصى عدد في كل تمرين.', sets: 0 },
      { t: 'زوّد العدد', d: '+1–2 عدّة في كل مجموعة.', sets: 0 },
      { t: 'زوّد مجموعة', d: 'مجموعة زيادة في أول تمرينين.', sets: 1, first: 2 },
      { t: 'اطلع درجة', d: 'لو عدّيت العدد، جرّب النسخة الأصعب.', sets: 0 },
    ],
    warning: 'سخّن الرسغ والكتف كويس قبل الزقّ والعقلة. متستعجلش المستويات الصعبة.',
  },

  /* ─────────────── FUNCTIONAL / AMRAP ─────────────── */
  {
    slug: 'functional', name: 'فانكشنال', en: 'FUNCTIONAL · AMRAP / EMOM', emoji: '🏃',
    tagline: 'تمارين بتحاكي حركة حياتك — دواير على الوقت: أكبر عدد جولات أو كل دقيقة.',
    theme: { bg: '#0a0f14', surface: '#121b24', ink: '#eaf2ff', a: '#f97316', b: '#38bdf8', motif: 'hex', mode: 'dark' },
    visual: 'clock', place: 'gym', goal: ['burn', 'strength'], daysPerWeek: 3, minutes: '30–40', level: 'متوسط', levelN: 2,
    tags: ['لياقة', 'على الوقت'],
    how: [
      { t: 'AMRAP', d: 'أكبر عدد جولات تقدر تعمله في وقت محدد.' },
      { t: 'EMOM', d: 'كل أول دقيقة اعمل العدد، والباقي من الدقيقة راحة.' },
      { t: 'حركات مركّبة', d: 'Kettlebell Swing، Thruster، Box Step-Up، Farmer\'s Walk.' },
    ],
    goodFor: ['اللي بيزهق من الجيم العادي', 'لياقة وقوة مع بعض'],
    notFor: ['اللي لسه بيتعلّم التكنيك (السرعة بتبوّظ الأداء)'],
    split: ['AMRAP', 'rest', 'EMOM', 'rest', 'CHIPPER', 'rest', 'rest'],
    days: {
      AMRAP: { title: 'AMRAP — أكبر عدد جولات', format: 'amrap', minutes: 15, items: [ex('Kettlebell Swing', null, '15', null), ex('Push-Ups', null, '10', null, '', 'pushups'), ex('Squat', null, '15', null, '', 'squat'), ex('Burpees', null, '5', null, '', 'burpees')] },
      EMOM: { title: 'EMOM — كل دقيقة', format: 'emom', minutes: 16, items: [ex('Thruster', null, '10', null, 'الدقيقة 1'), ex('Pull-Ups', null, '6', null, 'الدقيقة 2'), ex('Box Step-Up', null, '12', null, 'الدقيقة 3'), ex('Plank', null, '40 ث', null, 'الدقيقة 4')] },
      CHIPPER: { title: 'تشيبر — خلّص القايمة', format: 'chipper', items: [ex('Row / Jumping Jacks', null, '50', null, '', 'jacks'), ex('Walking Lunges', null, '40', null), ex('Kettlebell Swing', null, '30', null), ex('Push-Ups', null, '20', null, '', 'pushups'), ex('Burpees', null, '10', null, '', 'burpees'), ex("Farmer's Walk", null, '100 م', null)] },
    },
    weeks: [
      { t: 'تعلّم الحركات', d: 'وزن خفيف وتكنيك نضيف — سجّل عدد جولاتك.', min: -3 },
      { t: 'الأساس', d: 'الوقت الكامل.', min: 0 },
      { t: 'اكسر رقمك', d: 'حاول تعمل جولة زيادة عن الأسبوع اللي فات.', min: 0 },
      { t: 'وزن أتقل', d: 'زوّد وزن الكيتل بيل أو الدامبل شوية.', min: 0 },
    ],
    warning: 'السرعة متجيش على حساب التكنيك — خصوصًا في الـ Swing والـ Thruster. وقّف لو التكنيك باظ.',
  },

  /* ─────────────── MOBILITY ─────────────── */
  {
    slug: 'mobility', name: 'مرونة وإطالة', en: 'MOBILITY', emoji: '🧘',
    tagline: '15 دقيقة في اليوم تفك التخشّب وتحمي ضهرك ومفاصلك — لكل الناس.',
    theme: { bg: '#f2f7f5', surface: '#ffffff', ink: '#12352c', a: '#0f9d7a', b: '#f59e0b', motif: 'waves', mode: 'light' },
    visual: 'ladder', place: 'home', goal: ['mobility', 'start'], daysPerWeek: 5, minutes: '15', level: 'سهل', levelN: 1,
    tags: ['ضهر ورقبة', 'لكل الأعمار'],
    how: [
      { t: 'حركة مش شدّ', d: 'ادخل في الإطالة بالراحة واتنفّس — مفيش هزّ.' },
      { t: '5 أيام قصيرة', d: '15 دقيقة كل يوم أحسن من ساعة مرة في الأسبوع.' },
      { t: 'الضهر والحوض والكتف', d: 'أكتر أماكن بتتخشّب من القعدة.' },
    ],
    goodFor: ['اللي قاعد على مكتب طول اليوم', 'بعد أي تمرين', 'كبار السن والمبتدئين'],
    notFor: ['أي إصابة حادة (اكشف الأول)'],
    split: ['SPINE', 'HIPS', 'UPPER', 'rest', 'SPINE', 'FLOW', 'rest'],
    days: {
      SPINE: { title: 'الضهر', items: [ex('Cat-Cow', 2, '10 مرات', 20), ex('Child\'s Pose', 2, '40 ث', 20), ex('Thoracic Rotation', 2, '8 لكل ناحية', 20), ex('Toe Touch', 2, '30 ث', 20, 'انزل بالراحة', 'toetouch')] },
      HIPS: { title: 'الحوض والرجل', items: [ex('Hip Flexor Stretch', 2, '40 ث لكل رجل', 20), ex('90/90 Hip Switch', 2, '8 لكل ناحية', 20), ex('Hamstring Stretch', 2, '40 ث', 20, '', 'toetouch'), ex('Wall Sit', 2, '30 ث', 30, '', 'wallsit')] },
      UPPER: { title: 'الكتف والرقبة', items: [ex('Wall Angels', 2, '10', 20), ex('Chin Tuck', 2, '10', 20), ex('Doorway Chest Stretch', 2, '40 ث', 20), ex('Arm Circles', 2, '20', 15)] },
      FLOW: { title: 'فلو كامل', items: [ex("World's Greatest Stretch", 2, '5 لكل ناحية', 20), ex('Cat-Cow', 2, '10', 20), ex('Deep Squat Hold', 2, '40 ث', 20), ex('Glute Bridge', 2, '12', 20, '', 'bridge')] },
    },
    weeks: [
      { t: 'اعرف جسمك', d: 'ادخل في الإطالة لحد الشدّ الخفيف بس.', sets: 0 },
      { t: 'ثبّت', d: 'نفس الحركات وأعمق شوية.', sets: 0 },
      { t: 'زوّد', d: 'مجموعة زيادة للحركات اللي متخشّب فيها.', sets: 1, first: 2 },
      { t: 'عادة', d: 'كمّل — المرونة بتيجي بالاستمرار.', sets: 0 },
    ],
    warning: 'الإطالة متوجعش. لو حسّيت وجع حاد أو تنميل، وقّف — ولو الوجع شديد أو مستمر اكشف عند دكتور.',
  },
]

export const trainingBySlug = (slug) => TRAININGS.find((t) => t.slug === slug)

export const T_GOALS = [
  { id: 'all', label: 'الكل' },
  { id: 'start', label: 'للمبتدئين' },
  { id: 'muscle', label: 'عضل' },
  { id: 'strength', label: 'قوة' },
  { id: 'burn', label: 'حرق' },
  { id: 'mobility', label: 'مرونة' },
]
export const PLACE = { gym: 'جيم', home: 'من البيت' }
export const WEEKDAYS = ['السبت', 'الحد', 'الاتنين', 'التلات', 'الأربع', 'الخميس', 'الجمعة']
export const WD_SHORT = ['سبت', 'حد', 'اتنين', 'تلات', 'أربع', 'خميس', 'جمعة']
const SHORT = { SHOULDERS: 'SH', CHEST: 'CH', BACK: 'BK', LEGS: 'LEG', LEGS2: 'LEG', ARMS: 'ARM', AMRAP: 'AMR', EMOM: 'EMO', CHIPPER: 'CHP', SPINE: 'SPN', HIPS: 'HIP', UPPER: 'UP', LOWER: 'LOW', FULL: 'FUL', CORE: 'COR', FLOW: 'FLO', PUSH: 'PSH', PULL: 'PUL', CB: 'C+B', AS: 'A+S' }
export const shortKey = (k) => SHORT[k] || k

/** Day n (1..28) of a program, with the week's progression applied. */
export function trainingDay(t, n) {
  const w = Math.ceil(n / 7)
  const wi = (n - 1) % 7
  const split = w % 2 === 0 && t.splitAlt ? t.splitAlt : t.split
  const key = split[wi]
  const wk = t.weeks[w - 1] || t.weeks[t.weeks.length - 1]
  if (key === 'rest') return { n, week: w, weekday: WEEKDAYS[wi], rest: true, key, wk }
  const base = t.days[key]
  const items = base.items.map((it, i) => {
    let sets = it.sets
    if (typeof sets === 'number' && wk.sets) {
      const applies = wk.first ? i < wk.first : true
      if (applies) sets = Math.max(1, sets + wk.sets)
    }
    let reps = it.reps
    let rest = it.rest
    if (base.format === 'interval' && !base.block && wk.work) { reps = `${wk.work} ث`; rest = wk.restS }
    return { ...it, sets, reps, rest }
  })
  const out = { n, week: w, weekday: WEEKDAYS[wi], rest: false, key, wk, title: base.title, format: base.format || 'sets', items }
  if (base.format === 'interval') {
    out.rounds = base.block ? base.rounds : Math.max(2, base.rounds + (wk.rounds || 0))
    out.blocks = base.block ? (wk.blocks || items.length) : null
    if (base.block && out.blocks !== items.length) {
      out.items = Array.from({ length: out.blocks }, (_, i) => ({ ...items[i % items.length], note: `بلوك ${i + 1}` }))
    }
  }
  if (base.format === 'amrap' || base.format === 'emom') out.minutes = Math.max(8, base.minutes + (wk.min || 0))
  return out
}

export const fmtRest = (s) => (s == null ? '' : s >= 60 ? `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')} د` : `${s} ث`)
