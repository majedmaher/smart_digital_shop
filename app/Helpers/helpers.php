<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Mgcodeur\CurrencyConverter\Facades\CurrencyConverter;

if (!function_exists('saveImage')) {
    function saveImage($photo, $folder_name): string
    {
        $folder = 'uploads/' . $folder_name;
        $image = $photo->getClientOriginalName(); //Name with extension 'filename.jpg'
        $name = explode('.', $image)[0]; // Filename 'filename'

        $fileName = $name . uniqid() . '.' . $photo->getClientOriginalExtension();
        $photo->move(public_path($folder), $fileName);
        return $folder . '/' . $fileName;
    }
}

if (!function_exists('saveImageInStorage')) {
    function saveImageInStorage($photo, $folder_name): string
    {
        $folder = 'uploads/' . $folder_name;
        $image = $photo->getClientOriginalName(); //Name with extension 'filename.jpg'
        $name = explode('.', $image)[0]; // Filename 'filename'

        $fileName = $name . uniqid() . '.' . $photo->getClientOriginalExtension();
        $photo->storeAs($folder, $fileName, 'uploads');
        return 'uploads/' . $folder . '/' . $fileName;
    }
}


if (!function_exists('currencyConverter')) {
    function currencyConverter($amount, $to, $decimals = 2): array
    {
        $formatted_string = '';
        $converted_amount = 0;

        // الخطوة 1: تحويل المبلغ والحصول على السلسلة المنسقة
        if (is_null($amount) || $amount == 0) {
            $converted_amount = 0;
        } else {
            $converted_amount = round(
                CurrencyConverter::convert($amount)
                    ->from('SAR')
                    ->to($to)
                    ->get(),
                $decimals
            );
        }

        // تنسيق المبلغ مع رمز العملة حسب اللغة
        if (app()->getLocale() == 'ar') {
            $formatted_string = Number::currency($converted_amount, in: $to, locale: 'ar');
        } else {
            $formatted_string = Number::currency($converted_amount, in: $to);
        }

        // تنظيف السلسلة من أي رموز تحكم غير مرئية قد تؤثر على المعالجة
        $clean_string = str_replace(["\u{200f}", "\u{200e}", "\u{00a0}"], ' ', $formatted_string);
        $clean_string = trim($clean_string);

        // الخطوة 2: استخراج الرقم والعملة
        $numeric_part = '';
        $currency_part = '';

        // استخراج الجزء الرقمي أولاً
        preg_match('/[\d.,٬٫]+/', $clean_string, $amount_match);
        if (isset($amount_match[0])) {
            $numeric_part = $amount_match[0];
            // إزالة الجزء الرقمي من السلسلة الأصلية للحصول على العملة
            $currency_part = str_replace($numeric_part, '', $clean_string);
        }

        // الخطوة 3: تنظيف النتائج وتحويلها
        $numeric_value = 0;
        if (!empty($numeric_part)) {
            // توحيد الفواصل العشرية إلى نقطة للتحويل الصحيح
            $number_for_conversion = str_replace(['٬', '٫', ','], ['.', '.', '.'], $numeric_part);
            $numeric_value = (float) $number_for_conversion;
        }

        // تنظيف رمز العملة من أي مسافات زائدة
        $currency_symbol = trim($currency_part);

        // حل بديل إذا لم يتم استخراج الرمز
        if (empty($currency_symbol)) {
            $currency_symbol = $to;
        }

        return [
            'amount' => $numeric_value,
            'currency' => $currency_symbol
        ];
    }
}
