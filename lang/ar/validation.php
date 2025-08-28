<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'max' => [
        'string' => 'قد لا يكون :attribute أكبر من :max حروف.',
    ],
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالح.',
    'unique' => 'تم أخذ :attribute بالفعل.',
    'min' => [
        'string' => 'يجب أن يكون :attribute على الأقل :min حروف.',
        'integer' => 'يجب أن يكون :attribute على الأقل :min عنصر.',
    ],
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'exists' => ':attribute غير موجود.',
    'before_or_equal' => 'تاريخ البداية يجب أن يكون تاريخًا قبل أو يساوي اليوم وقبل أو يساوي تاريخ النهاية.',
    'after_or_equal' => 'تاريخ النهاية يجب أن يكون تاريخًا قبل أو يساوي اليوم وبعد أو يساوي تاريخ البداية.',

    'between' => [
        'numeric' => ':attribute يجب أن يكون بين :min و :max.',
    ],

    'attributes' => [
        'name' => 'الاسم',
        'cart' => 'السلة',
        'quantity' => 'الكمية',
        'shipping_data' => 'بيانات الشحن',
        'coupon_code' => 'كوبون الخصم',
        'email' => 'عنوان البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'vat_rate' => 'قيمة الضريبة',
    ],
];
