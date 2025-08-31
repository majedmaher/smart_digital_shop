<?php

namespace App\Helpers;

use App\Enum\PaymentCurrencyEnum;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderHelper
{
    public static function buildItems(Order $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            // تحويل كل شيء إلى سنتات
            $unitPriceCents = (int) round($item->price * 100);
            $totalDiscountCents = (int) round($item->discount * 100);
            $totalVatCents = (int) round($item->vat * 100);

            // الحساب الكلي للمنتج
            $netTotalCents = ($unitPriceCents * $item->quantity) - $totalDiscountCents;
            $grossTotalCents = $netTotalCents + $totalVatCents;
            $grossTotalCents = max(1, $grossTotalCents); // لا يقل عن 1 سنت

            // السعر لكل وحدة (مقرب)
            $amountPerUnit = (int) round(currencyConverter($grossTotalCents / $item->quantity, 'SAR')['amount']);
            $amountPerUnit = max(1, $amountPerUnit);

            // أدخل كل وحدة بشكل منفصل
            for ($i = 0; $i < $item->quantity; $i++) {
                $items[] = [
                    'name' => $item->product->name,
                    'amount' => $amountPerUnit,
                    'description' => $item->product->description ?? 'Product',
                    'quantity' => 1,
                ];
            }
        }

        // ✅ التأكد من أن المجموع مطابق
        $calculatedTotal = collect($items)->sum('amount');
        $expectedTotal = (int) currencyConverter(round($order->total_price * 100), PaymentCurrencyEnum::SAR->value)['amount'];
        $difference = $expectedTotal - $calculatedTotal;

        if (abs($difference) > 0 && count($items) > 0) {
            $lastIndex = count($items) - 1;
            $items[$lastIndex]['amount'] += $difference;
            $items[$lastIndex]['amount'] = max(1, $items[$lastIndex]['amount']);
        }

        // ✅ تأكد أن لا يوجد عناصر صفرية
        $items = array_filter($items, fn($item) => $item['amount'] > 0);

        // ✅ تأكد أن هناك على الأقل عنصر واحد
        if (empty($items)) {
            $items[] = [
                'name' => 'Order',
                'amount' => $expectedTotal,
                'description' => 'Single item',
                'quantity' => 1,
            ];
        }

        return $items;
    }
}
