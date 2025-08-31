<?php

namespace App\Jobs;

use App\Actions\PushOrderToZoho;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushOrderToZohoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 5;
    public $timeout = 30;

    protected array $orderPayload;
    protected int $orderId; // 👈 نخزن رقم الطلب

    public function __construct(array $orderPayload, int $orderId)
    {
        $this->orderPayload = $orderPayload;
        $this->orderId = $orderId;
    }

    public function handle(PushOrderToZoho $pusher): void
    {
        // ✅ تحقق من أن الطلب لم يُرسل مسبقًا
        $order = Order::find($this->orderId);
        if (! $order) {
            // Log::warning('Order not found for Zoho sync', ['order_id' => $this->orderId]);
            return;
        }

        if ($order->synced_to_zoho) {
            // Log::info('Order already synced to Zoho', ['order_id' => $this->orderId]);
            return;
        }

        try {
            $result = $pusher->handle($this->orderPayload);

            // ✅ نجاح: علّم الطلب كمُزامَن
            $order->update([
                'synced_to_zoho' => true,
                'zoho_synced_at' => now(),
                'zoho_invoice_id' => $result['invoice']['invoice_id'] ?? null,
                'zoho_payment_id' => $result['payment']['payment_id'] ?? null,
            ]);

            // Log::info('Order synced to Zoho successfully', [
            //     'order_id' => $this->orderId,
            //     'invoice_id' => $result['invoice']['invoice_id'] ?? null,
            // ]);
        } catch (\Exception $e) {
            // Log::error('Failed to sync order to Zoho', [
            //     'order_id' => $this->orderId,
            //     'error' => $e->getMessage(),
            // ]);
            throw $e; // لإعادة المحاولة (بسبب $tries = 3)
        }
    }
}
