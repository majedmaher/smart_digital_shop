<?php

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
    function currencyConverter($amount, $to, $decimals = 2): string
    {
        // التحقق من القيمة
        if (is_null($amount) || $amount == 0) {
            if (app()->getLocale() == 'ar') {
                $string = Number::currency($amount, in: $to, locale: 'ar');
                return str_replace(["\u{200f}", "\u{200e}"], '', $string);
            } else {
                return Number::currency($amount, in: $to);
            }
        }
        $amount =  round(
            CurrencyConverter::convert($amount)
                ->from('SAR')
                ->to($to)
                ->get(),
            $decimals
        );
        if (app()->getLocale() == 'ar') {
            $string = Number::currency($amount, in: $to, locale: 'ar');
            return str_replace(["\u{200f}", "\u{200e}"], '', $string);
        } else {
            return Number::currency($amount, in: $to);
        }
    }
}
