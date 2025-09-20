<?php

namespace App\Jobs;

use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Services\AbandonedCartService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessAbandonedCartNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting abandoned cart processing job");

            $this->processReminderNotifications();
            $this->processUrgentNotifications();
            $this->processFinalNotifications();

            Log::info("Abandoned cart processing job completed successfully");
        } catch (\Exception $e) {
            Log::error("Error in abandoned cart processing job: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Process reminder notifications (after 1 hour)
     */
    private function processReminderNotifications(): void
    {
        $reminderTime = Carbon::now()->subHours(9); // 8 + 1 = 9 hours
        $maxReminderTime = Carbon::now()->subHours(25); // 24 + 1 = 25 hours

        $orders = Order::where('status', OrderStatusEnum::PENDING->value)
            ->where('created_at', '>=', $reminderTime)
            ->where('created_at', '<', $maxReminderTime)
            ->where(function ($query) {
                $query->whereNull('abandoned_notifications_count')
                      ->orWhere('abandoned_notifications_count', 0);
            })
            ->get();

        foreach ($orders as $order) {
            try {
                AbandonedCartService::sendAbandonedCartNotification($order->id, 'reminder');
                Log::info("Reminder notification sent for order {$order->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send reminder notification for order {$order->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Process urgent notifications (after 24 hours)
     */
    private function processUrgentNotifications(): void
    {
        $urgentTime = Carbon::now()->subHours(32); // 8 + 24 = 32 hours
        $maxUrgentTime = Carbon::now()->subHours(80); // 72 + 8 = 80 hours

        $orders = Order::where('status', OrderStatusEnum::PENDING->value)
            ->where('created_at', '>=', $urgentTime)
            ->where('created_at', '<', $maxUrgentTime)
            ->where('abandoned_notifications_count', 1)
            ->get();

        foreach ($orders as $order) {
            try {
                AbandonedCartService::sendAbandonedCartNotification($order->id, 'urgent');
                Log::info("Urgent notification sent for order {$order->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send urgent notification for order {$order->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Process final notifications (after 72 hours)
     */
    private function processFinalNotifications(): void
    {
        $finalTime = Carbon::now()->subHours(80); // 8 + 72 = 80 hours

        $orders = Order::where('status', OrderStatusEnum::PENDING->value)
            ->where('created_at', '<', $finalTime)
            ->where('abandoned_notifications_count', 2)
            ->get();

        foreach ($orders as $order) {
            try {
                AbandonedCartService::sendAbandonedCartNotification($order->id, 'final');
                Log::info("Final notification sent for order {$order->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send final notification for order {$order->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Abandoned cart processing job failed", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
