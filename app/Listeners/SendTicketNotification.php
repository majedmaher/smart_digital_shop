<?php

namespace App\Listeners;

use App\Events\TicketMessageCreated;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTicketNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TicketMessageCreated $event): void
    {
        $message = $event->message;
        $ticket = $message->ticket;

        if ($message->user?->hasPermissionTo('reply tickets')) {
            // تعيين هذا المشرف إذا لم يتم التعيين من قبل
            if (!$ticket->assigned_to) {
                $ticket->assigned_to = $message->user_id;
                $ticket->save();
            }

            $recipient = $ticket->user;
            if ($recipient && $recipient->id !== $message->user_id) {
                $ticket->update(['status' => 'pending']);
                $recipient->notify(new TicketReplyNotification($ticket, $message));
            }
        } else {
            // الرد من العميل → أرسل للمشرف المعين
            // $recipient = $ticket->assigned_to
            //     ? User::find($ticket->assigned_to)
            //     : User::permission('reply to messages')->first(); // بديل: notify all

            // إذا بدك تبعت لكل المشرفين في حال ما في تعيين:
            $recipients = $ticket->assigned_to
                ? [User::find($ticket->assigned_to)]
                : User::permission('reply tickets')->get();
            foreach ($recipients as $recipient) {
                if ($recipient->id !== $message->user_id) {
                    $recipient->notify(new TicketReplyNotification($ticket, $message));
                }
            }
        }
    }
}
