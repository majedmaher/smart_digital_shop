<?php

namespace App\Jobs;

use App\Services\AbandonedCartService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendAbandonedCartNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $notificationType;

    /**
     * Create a new job instance.
     */
    public function __construct(string $notificationType = 'reminder')
    {
        $this->notificationType = $notificationType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting abandoned cart notifications job for type: {$this->notificationType}");

            // إرسال الإشعارات المجمعة
            $result = AbandonedCartService::sendBulkNotifications($this->notificationType);

            if ($result->getData()->success) {
                $data = $result->getData()->data;
                Log::info("Abandoned cart notifications sent successfully", [
                    'type' => $this->notificationType,
                    'total_carts' => $data->total_abandoned_carts,
                    'notifications_sent' => $data->notifications_sent,
                    'errors' => $data->errors,
                ]);
            } else {
                Log::error("Failed to send abandoned cart notifications", [
                    'type' => $this->notificationType,
                    'error' => $result->getData()->message,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error in abandoned cart notifications job: " . $e->getMessage(), [
                'type' => $this->notificationType,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Abandoned cart notifications job failed", [
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
