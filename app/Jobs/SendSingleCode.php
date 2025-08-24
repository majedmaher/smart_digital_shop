<?php

namespace App\Jobs;

use App\Mail\SendCodeMail;
use App\Models\Code;
use App\Models\FailedEmailLog;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSingleCode implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $code;
    public $email;
    public $item;

    public $tries = 3;
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(Code $code, string $email, OrderItem $item)
    {
        $this->code = $code;
        $this->email = $email;
        $this->item = $item;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(new SendCodeMail($this->code->code, $this->item->product->title));

            $this->code->update([
                'used_at' => now(),
                'is_used' => true,
                'order_item_id' => $this->item->id,
            ]);
        } catch (\Throwable $e) {
            // Log::error("Failed to send code {$this->code->code} to {$this->email}: " . $e->getMessage());

            FailedEmailLog::create([
                'order_id' => $this->item->order_id,
                'order_item_id' => $this->item->id,
                'email' => $this->email,
                'error_message' => $e->getMessage(),
            ]);

            throw $e; // لضمان إعادة المحاولة من Laravel
        }
    }
}
