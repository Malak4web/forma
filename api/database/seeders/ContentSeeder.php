<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Plan;
use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Library, programs and plans — idempotent (safe to re-run).
 * PRICES ARE PLACEHOLDERS until Amr decides.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $t = fn (string $id) => "/img/thumbs/{$id}.webp";

        $exercises = [
            ['climbers', 'Mountain Climbers', 'تمرين من البيت هيخسّسك الكرش', 'belly', 25, 'متوسط', 'البطن + الكتف + نَفَس', ['إيدك تحت كتفك بالظبط', 'وسطك مفرود، متعلّيش المؤخرة', 'لو صعب، بدّل رجلك بالراحة من غير نط']],
            ['crunches', 'Crunches', 'عايز بطن مشدودة؟', 'belly', 31, 'مبتدئ', 'البطن العلوية', ['ارفع كتافك بس، مش ضهرك كله', 'متشدّش رقبتك بإيدك', 'نفَس طالع وانت طالع']],
            ['twist', 'Russian Twist', 'عايز وسط مشدود من الجناب؟', 'belly', 26, 'متوسط', 'الجناب', ['ضهرك مفرود ومايل لورا شوية', 'لفّ بصدرك مش بإيدك بس', 'رجلك على الأرض لو لسه مبتدئ']],
            ['squat', 'Squat', 'رجلك رفيعة؟', 'legs', 30, 'مبتدئ', 'الفخد الأمامي + الأرداف', ['رجلك بعرض كتفك', 'انزل كأنك هتقعد على كرسي', 'ركبتك في اتجاه صوابعك']],
            ['bridge', 'Tabletop Bridge', 'عايز أرداف مشدودة ورجل قوية؟', 'legs', 29, 'مبتدئ', 'الأرداف + خلف الفخد', ['اضغط بكعبك في الأرض', 'اعصر الأرداف فوق ثانية', 'متقوّسش أسفل ضهرك']],
            ['wallsit', 'Wall Sit', 'تمرين واحد بس لركبتك', 'pain', 24, 'مبتدئ', 'الفخد الأمامي', ['ضهرك لازق في الحيطة', 'ركبتك فوق كعبك مش قدّامه', 'لو الركبة وجعتك، اطلع شوية لفوق']],
            ['pushups', 'Push-Ups', 'عايز صدر عريض من غير جيم؟', 'upper', 27, 'متوسط', 'الصدر + الترايسبس', ['جسمك خط واحد من راسك لرجلك', 'كوعك مايل لورا مش مفتوح', 'ابدأ على ركبتك لو صعب']],
            ['pike', 'Pike Push-Ups', 'عايز كتف عريض ودراع قوي؟', 'upper', 25, 'متقدم', 'الكتف', ['المؤخرة لفوق، جسمك شكل V', 'انزل براسك قدّام إيدك', 'ابدأ بنزلة صغيرة']],
            ['jacks', 'Jumping Jacks', 'عايز تحرق دهون من غير ما تنزل؟', 'cardio', 27, 'مبتدئ', 'الجسم كله', ['انزل على مشط رجلك', 'لو فيه جيران، اعملها من غير نط', 'حافظ على نفَس منتظم']],
            ['burpees', 'Burpees', 'هيخسّسك في 10 دقايق', 'cardio', 27, 'متقدم', 'الجسم كله', ['شيل النطّة لو تقيلة عليك', 'ضهرك مفرود وانت نازل', 'السرعة بعد ما تتقن الحركة']],
            ['stepback', 'Step Back', 'هيخسّسك من غير نط', 'cardio', 28, 'مبتدئ', 'رجل + نَفَس', ['رجل ورا والتانية ثابتة', 'جسمك معتدل', 'بدّل بسرعة ثابتة']],
            ['toetouch', 'Toe Touch', 'جسمك متخشّب الصبح؟', 'pain', 25, 'مبتدئ', 'خلف الفخد + الضهر', ['انزل بالراحة من غير هزّ', 'ركبتك مثنية شوية عادي', 'اقف لو حسّيت وجع مش شدّ']],
            ['koshari', 'Koshari', 'طبق الكشري ده = ساعة جري', 'food', 21, null, null, null],
            ['konafa', 'Konafa', 'حتة الكنافة دي = 25 دقيقة نط حبل', 'food', 20, null, null, null],
            ['shawarma', 'Shawarma', 'ساندوتش الشاورما = 45 دقيقة جري', 'food', 19, null, null, null],
            ['indomie', 'Indomie', 'باكو الأندومي = ساعة و10 دقايق مشي', 'food', 18, null, null, null],
            ['sleep', 'Sleep after eating', 'اتغدّيت ونمت؟ بص جوّاك', 'body', 26, null, null, null],
            ['coffee', 'Coffee at night', 'قهوة بالليل؟ بص جوّاك', 'body', 24, null, null, null],
        ];
        foreach ($exercises as $i => [$slug, $title, $hook, $cat, $sec, $level, $muscles, $tips]) {
            Exercise::withTrashed()->updateOrCreate(['slug' => $slug], [
                'title' => $title, 'hook' => $hook, 'category' => $cat, 'seconds' => $sec, 'level' => $level,
                'muscles' => $muscles, 'tips' => $tips, 'thumb' => $t($slug), 'is_home' => ! in_array($cat, ['food', 'body']), 'sort' => $i,
            ]);
        }

        $programs = [
            ['30', 'تحدّي 30 يوم', 'من البيت، من غير أدوات', 30, '15–20', 'مبتدئ ← متوسط', 'cover-karsh', 'الأكتر طلبًا',
                'كل يوم 3 تمارين، كرّرهم 3 مرات. الحِمل بيزيد كل أسبوع، وفي الآخر بتقارن نفسك بأول يوم.', [
                    ['نصحّي الجسم', ['squat', 'bridge', 'crunches', 'stepback', 'jacks', 'wallsit']],
                    ['نزوّد النَفَس', ['climbers', 'twist', 'squat', 'jacks', 'bridge', 'stepback']],
                    ['نرفع الحِمل', ['pushups', 'climbers', 'twist', 'burpees', 'squat', 'bridge']],
                    ['الأسبوع الكبير', ['burpees', 'pike', 'pushups', 'climbers', 'twist', 'squat']],
                    ['الختام', ['burpees', 'pushups', 'squat']],
                ]],
            ['10min', '10 دقايق في اليوم', 'للي معندوش وقت خالص', 21, '10', 'كل المستويات', 'cover-10min', null,
                'دايرة سريعة كل يوم، 10 دقايق بالظبط. مفيش حجّة.', [
                    ['نبدأ بالراحة', ['jacks', 'squat', 'crunches', 'stepback']],
                    ['نسرّع', ['climbers', 'jacks', 'twist', 'squat']],
                    ['نولّع', ['burpees', 'climbers', 'pushups', 'jacks']],
                ]],
            ['nojump', 'من غير نط', 'للشقق والجيران والركبة', 14, '15', 'مبتدئ', 'cover-nojump', null,
                'كل التمارين من غير ولا نطّة. مناسب لو ساكن في دور عالي أو ركبتك حسّاسة.', [
                    ['الأساس', ['stepback', 'squat', 'bridge', 'crunches', 'wallsit']],
                    ['نشدّ', ['stepback', 'twist', 'pushups', 'bridge', 'squat']],
                ]],
            ['back', 'ضهر ورقبة من غير وجع', 'للي قاعد على مكتب طول اليوم', 14, '10', 'مبتدئ', 'wallsit', null,
                'حركات خفيفة تفكّ الضهر وتقوّي اللي حواليه. لو الوجع شديد أو مستمر، اكشف عند دكتور.', [
                    ['نفكّ', ['toetouch', 'bridge', 'wallsit']],
                    ['نقوّي', ['bridge', 'wallsit', 'toetouch', 'crunches']],
                ]],
        ];
        foreach ($programs as $i => [$slug, $title, $sub, $days, $mins, $level, $thumb, $tag, $pitch, $weeks]) {
            $program = Program::withTrashed()->updateOrCreate(['slug' => $slug], [
                'title' => $title, 'subtitle' => $sub, 'pitch' => $pitch, 'days' => $days, 'minutes' => $mins,
                'level' => $level, 'thumb' => $t($thumb), 'tag' => $tag, 'sort' => $i, 'is_active' => true,
            ]);
            foreach ($weeks as $n => [$wt, $pool]) {
                $program->weeks()->updateOrCreate(['number' => $n + 1], ['title' => $wt, 'pool' => $pool]);
            }
        }

        $plans = [
            ['free', 'مجاني', 0, 'free', null, 'ابدأ ببلاش', null, false, ['كل فيديوهات المكتبة', 'أول 3 أيام من أي برنامج', 'حاسبة السعرات + نظام أكل مبدئي'], []],
            ['challenge', 'تحدّي 30 يوم', 199, 'once', null, 'ابدأ التحدّي', 'الأكتر طلبًا', false, ['برنامج الـ 30 يوم كامل', 'مؤقّت تمرين يوم بيوم', 'متابعة الأيام والوزن', 'ليك على طول، مش اشتراك'], ['30']],
            ['pro', 'فورمة PRO', 149, 'monthly', 30, 'اشترك', null, true, ['كل البرامج كاملة', 'كل برنامج جديد بينزل', 'نظام أكل مفصّل على هدفك', 'متابعة الوزن والقياسات'], ['30', '10min', 'nojump', 'back']],
        ];
        foreach ($plans as $i => [$slug, $name, $price, $period, $duration, $cta, $tag, $hl, $features, $unlocks]) {
            $plan = Plan::withTrashed()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'price' => $price, 'period' => $period, 'duration_days' => $duration, 'cta' => $cta,
                'tag' => $tag, 'highlight' => $hl, 'features' => $features, 'sort' => $i, 'is_active' => true,
            ]);
            $plan->programs()->sync(Program::whereIn('slug', $unlocks)->pluck('id'));
        }
    }
}
