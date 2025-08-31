<?php

namespace App\Actions;

use App\Services\ZohoBooksService;
use Illuminate\Support\Facades\Log;

class PushOrderToZoho
{
    public function __construct(protected ZohoBooksService $zoho) {}

    public function handle(array $order): array
    {
        // 1) Contact
        $contact = $this->zoho->getOrCreateContact([
            'name'  => $order['customer']['name']  ?? null,
            'email' => $order['customer']['email'] ?? null,
            'phone' => $order['customer']['phone'] ?? null,
        ]);

        // 2) Items + tax_id per line
        $lineItems = [];
        foreach ($order['items'] as $it) {
            // ✅ حساب السعر بعد الخصم (في كل منتج)
            $finalPrice = (float) $it['price'] - (float) ($it['discount'] ?? 0);

            $item = $this->zoho->getOrCreateItem([
                'sku'      => $it['sku']   ?? null,
                'name'     => $it['name']  ?? null,
                'price'    => $finalPrice, // ← السعر بعد الخصم
                'tax_rate' => $it['tax_rate'] ?? 0,
            ]);

            // أضف الضريبة للسطر
            $line = [
                'item_id'  => $item['item_id'],
                'rate'     => $finalPrice,
                'quantity' => (float) ($it['qty'] ?? 1),
            ];

            // أضف tax_id إذا وُجد
            if ($it['tax_rate'] > 0) {
                $taxId = $this->zoho->ensureTaxExistsByRate($it['tax_rate']);
                if ($taxId) {
                    $line['tax_id'] = $taxId;
                }
            }

            $lineItems[] = $line;
        }

        // 3) Invoice (بدون خصم عام)
        $invoice = $this->zoho->createInvoice($contact, $lineItems, []);

        // 4) Payment — ادفع الرصيد الكامل
        $payment = $this->zoho->recordPayment(
            $invoice['invoice_id'],
            (float) $invoice['balance'], // ✅ ادفع الرصيد فقط
            'others',
            [
                'customer_id' => $contact['contact_id'],
                'reference'   => $order['transaction_id'] ?? null,
                'description' => $order['payment_method'] ?? null,
            ]
        );

        return compact('invoice', 'payment');
    }
}
