<?php

namespace App\Jobs;

use App\Enum\ShippingMethodPayment;
use App\Models\Code;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCodeAfterPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;
    public $tries = 3;
    public $timeout = 60;
    // public $backoff = [10, 30, 60]; // وقت الانتظار بين المحاولات (ثواني)

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        foreach ($this->order->items as $item) {
            if ($item->product->shipping_payment !== ShippingMethodPayment::CODE->value) {
                continue;
            }

            $shippingData = is_array($item->shipping_data)
                ? $item->shipping_data
                : json_decode($item->shipping_data, true);

            $email = $shippingData['email'] ?? null;
            // Log::info('ddd', ['order_id' => $item->shipping_data]);

            // $email = $item->shipping_data['email'] ?? null;
            if (!$email) {
                Log::warning("No email in shipping_data for item ID: {$item->id}");
                continue;
            }

            $codes = Code::where('product_id', $item->product_id)
                ->whereNull('used_at')
                ->limit($item->quantity)
                ->get();

            if ($codes->count() < $item->quantity) {
                Log::error("Not enough codes for product ID {$item->product_id}, order #{$this->order->id}");
                continue;
            }
            // Log::info('zzz', ['order_id' => $codes]);

            foreach ($codes as $code) {
                dispatch(new SendSingleCode($code, $email, $item));

                // try {
                //     Mail::to($email)->send(new SendCodeMail($code->code, $item->product->title));

                //     $code->update([
                //         'used_at' => now(),
                //         'is_used' => true,
                //         'order_item_id' => $item->id,
                //     ]);
                // } catch (\Throwable $e) {
                //     Log::error("Failed to send code {$code->code} to {$email}: " . $e->getMessage());
                // FailedEmailLog::create([
                //     'order_id' => $this->order->id,
                //     'order_item_id' => $item->id,
                //     'email' => $email,
                //     'error_message' => $e->getMessage(),
                // ]);
                //     throw $e; // إعادة المحاولة التلقائية

                //     // continue; // تابع إرسال الباقي
                // }
            }
        }
    }
}
