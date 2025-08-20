<?php

namespace App\Helpers;

use App\Models\Order;

class OrderHelper
{
    public static function buildItems(Order $order): array
    {
        $items = [];
        $totalCents = (int) round($order->total_price * 100);
        $calculatedCents = 0;

        foreach ($order->items as $item) {
            $itemCents = (int) round($item->total * 100);

            $unitCents = (int) floor($itemCents / $item->quantity); // 👈 استخدم floor
            $items[] = [
                'name' => $item->product->name,
                'amount' => $itemCents,
                'description' => $item->product->description ?? 'Product',
                'quantity' => 1,
            ];

            $calculatedCents += $unitCents * $item->quantity;
        }

        // ✅ توزيع الفرق
        // $diff = $totalCents - $calculatedCents;
        // if ($diff !== 0) {
        //     // عدّل على آخر عنصر أو وزّعه على الكمية
        //     $lastIndex = count($items) - 1;
        //     $items[$lastIndex]['amount'] += (int) round($diff / $items[$lastIndex]['quantity']);
        // }

        return $items;
    }
}
