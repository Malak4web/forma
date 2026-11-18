<?php

// Egyptian-Arabic validation messages for the rules this API uses.
return [
    'accepted' => 'لازم توافق على :attribute.',
    'array' => ':attribute لازم يكون قائمة.',
    'between' => [
        'numeric' => ':attribute لازم يكون بين :min و :max.',
    ],
    'email' => ':attribute مش مظبوط.',
    'exists' => ':attribute مش موجود.',
    'image' => ':attribute لازم يكون صورة.',
    'in' => ':attribute مش من الاختيارات المتاحة.',
    'integer' => ':attribute لازم يكون رقم صحيح.',
    'max' => [
        'array' => ':attribute أكتر من المسموح.',
        'file' => ':attribute أكبر من :max كيلوبايت.',
        'numeric' => ':attribute لازم ميزيدش عن :max.',
        'string' => ':attribute لازم ميزيدش عن :max حرف.',
    ],
    'mimes' => ':attribute لازم يكون صورة (:values).',
    'min' => [
        'numeric' => ':attribute لازم ميقلّش عن :min.',
        'string' => ':attribute لازم يكون :min حروف على الأقل.',
    ],
    'numeric' => ':attribute لازم يكون رقم.',
    'password' => [
        'min' => 'الباسورد لازم يكون :min حروف على الأقل.',
    ],
    'regex' => ':attribute مش مظبوط.',
    'required' => 'اكتب :attribute.',
    'string' => ':attribute لازم يكون كلام.',
    'unique' => ':attribute ده متسجّل قبل كده.',
    'uploaded' => 'رفع :attribute فشل، جرّب تاني.',

    'custom' => [
        'phone' => ['regex' => 'رقم موبايل مصري صح (11 رقم يبدأ بـ 01).', 'unique' => 'الرقم ده عليه حساب بالفعل — ادخل بيه.'],
        'payer_phone' => ['regex' => 'رقم المحفظة مش مظبوط (11 رقم يبدأ بـ 01).', 'required' => 'اكتب الرقم اللي حوّلت منه.'],
        'receipt' => ['required' => 'ارفع صورة إيصال التحويل.'],
        'terms' => ['accepted' => 'لازم توافق على الشروط.'],
    ],

    'attributes' => [
        'name' => 'الاسم',
        'phone' => 'رقم الموبايل',
        'email' => 'الإيميل',
        'password' => 'الباسورد',
        'goal' => 'الهدف',
        'terms' => 'الشروط',
        'plan' => 'الباقة',
        'method' => 'طريقة الدفع',
        'payer_phone' => 'رقم المحفظة',
        'receipt' => 'الإيصال',
        'kg' => 'الوزن',
        'note' => 'الملاحظة',
        'age' => 'السن',
        'weight' => 'الوزن',
        'height' => 'الطول',
    ],
];
