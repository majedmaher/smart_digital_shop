<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAbandonedCartNotifications;
use Illuminate\Console\Command;

class ProcessAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'abandoned-carts:process {--type=all : Type of notifications to process (reminder, urgent, final, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process abandoned carts and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        $this->info("Processing abandoned carts notifications...");

        if ($type === 'all') {
            // تشغيل معالجة شاملة
            ProcessAbandonedCartNotifications::dispatch();
            $this->info("Abandoned cart processing job dispatched successfully.");
        } else {
            // تشغيل نوع محدد من الإشعارات
            \App\Jobs\SendAbandonedCartNotifications::dispatch($type);
            $this->info("Abandoned cart {$type} notifications job dispatched successfully.");
        }

        $this->info("Command completed successfully.");
    }
}
