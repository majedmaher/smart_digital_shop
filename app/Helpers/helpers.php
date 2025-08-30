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
        // التحويل من SAR إلى العملة المطلوبة
        $converted_amount = 0;

        if ($amount !== null && $amount != 0) {
            $converted_amount = round(
                CurrencyConverter::convert($amount)
                    ->from('SAR')
                    ->to($to)
                    ->get(),
                $decimals
            );
        }

        // قائمة رموز جميع العملات (عربي + إنجليزي)
        $symbols = [
            'SAR' => ['ar' => 'ر.س',  'en' => 'SR'],
            'USD' => ['ar' => '$',    'en' => '$'],
            'EUR' => ['ar' => '€',    'en' => '€'],
            'AED' => ['ar' => 'د.إ',  'en' => 'AED'],
            'EGP' => ['ar' => 'ج.م',  'en' => 'EGP'],
            'QAR' => ['ar' => 'ر.ق',  'en' => 'QAR'],
            'KWD' => ['ar' => 'د.ك',  'en' => 'KWD'],
            'OMR' => ['ar' => 'ر.ع',  'en' => 'OMR'],
            'JOD' => ['ar' => 'د.أ',  'en' => 'JOD'],
            'GBP' => ['ar' => '£',    'en' => '£'],
            'CAD' => ['ar' => '$',    'en' => '$'],
            'AUD' => ['ar' => '$',    'en' => '$'],
            'CHF' => ['ar' => 'Fr',   'en' => 'CHF'],
            'SEK' => ['ar' => 'kr',   'en' => 'SEK'],
            'NOK' => ['ar' => 'kr',   'en' => 'NOK'],
            'DKK' => ['ar' => 'kr',   'en' => 'DKK'],
            'PLN' => ['ar' => 'zł',   'en' => 'PLN'],
            'CZK' => ['ar' => 'Kč',   'en' => 'CZK'],
            'HUF' => ['ar' => 'Ft',   'en' => 'HUF'],
            'RON' => ['ar' => 'lei',  'en' => 'RON'],
            'BGN' => ['ar' => 'лв',   'en' => 'BGN'],
            'TRY' => ['ar' => '₺',    'en' => 'TRY'],
            'RUB' => ['ar' => '₽',    'en' => 'RUB'],
            'INR' => ['ar' => '₹',    'en' => 'INR'],
            'PKR' => ['ar' => '₨',    'en' => 'PKR'],
            'JPY' => ['ar' => '¥',    'en' => '¥'],
            'CNY' => ['ar' => '¥',    'en' => 'CN¥'],
            'MXN' => ['ar' => '$',    'en' => '$'],
            'BRL' => ['ar' => 'R$',   'en' => 'R$'],
            'ZAR' => ['ar' => 'R',    'en' => 'R'],
            'SGD' => ['ar' => 'S$',   'en' => 'S$'],
            'HKD' => ['ar' => 'HK$',  'en' => 'HK$'],
            'MYR' => ['ar' => 'RM',   'en' => 'RM'],
            'THB' => ['ar' => '฿',    'en' => '฿'],
            'IDR' => ['ar' => 'Rp',   'en' => 'Rp'],
            'PHP' => ['ar' => '₱',    'en' => '₱'],
            'VND' => ['ar' => '₫',    'en' => '₫'],
            'KES' => ['ar' => 'KSh',  'en' => 'KSh'],
            'NGN' => ['ar' => '₦',    'en' => 'NGN'],
            'GHS' => ['ar' => '₵',    'en' => 'GHS'],
            'UAH' => ['ar' => '₴',    'en' => 'UAH'],
            'ILS' => ['ar' => '₪',    'en' => '₪'],
            // أضف أي عملة أخرى تحتاجها
        ];

        $locale = app()->getLocale(); // 'ar' أو 'en'
        $currency_symbol = $symbols[$to][$locale] ?? $to;

        $formatted_number = number_format($converted_amount, $decimals, '.', ',');

        return [
            'amount'    => (float) $converted_amount,
            'currency'  => $currency_symbol,
            'formatted' => $locale === 'ar'
                ? "$formatted_number $currency_symbol"
                : "$currency_symbol $formatted_number"
        ];
    }
}
