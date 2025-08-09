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
        $result = '';
        // التحقق من القيمة
        if (is_null($amount) || $amount == 0) {
            if (app()->getLocale() == 'ar') {
                $string = Number::currency($amount, in: $to, locale: 'ar');
                $result = str_replace(["\u{200f}", "\u{200e}", "\u{00a0}"], '', $string); // إضافة non-breaking space
            } else {
                $result = Number::currency($amount, in: $to);
            }
        } else {
            $amount = round(
                CurrencyConverter::convert($amount)
                    ->from('SAR')
                    ->to($to)
                    ->get(),
                $decimals
            );
            if (app()->getLocale() == 'ar') {
                $string = Number::currency($amount, in: $to, locale: 'ar');
                $result = str_replace(["\u{200f}", "\u{200e}", "\u{00a0}"], '', $string); // إضافة non-breaking space
            } else {
                $result = Number::currency($amount, in: $to);
            }
        }

        // استخراج الرقم والعملة
        preg_match('/([\d٫٬\.]+)/u', $result, $amountMatch);  // استخراج الرقم
        preg_match('/[^\d٫٬\.]+/u', $result, $currencyMatch);  // استخراج رمز العملة
        Log::info($currencyMatch);  // طباعة النتيجة في اللوج لمراجعتها

        // التأكد من أن قيمة العملة تحتوي على الحروف الكاملة (د.ك أو ر.س)
        $currency = isset($currencyMatch[0]) ? trim($currencyMatch[0]) : '';

        return [
            'amount' => isset($amountMatch[1]) ? (float) str_replace([',', '٫', '٬'], '.', $amountMatch[1]) : 0,
            'currency' => $currency
        ];
    }
}
