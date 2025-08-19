<?php

namespace App\Services;

use App\Events\TicketMessageCreated;
use App\Http\Controllers\API\BaseController;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Notifications\TicketReplyNotification;
use Illuminate\Http\JsonResponse;

class TicketService
{
    private static string $image_folder = 'tickets';

    public static function index(): JsonResponse
    {
        try {
            $tickets = Ticket::with(['latestMessage', 'user'])
                ->where('user_id', auth()->id())
                ->latest()
                ->get();
            return BaseController::sendResponse($tickets, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function adminIndex(): JsonResponse
    {
        try {
            $tickets = Ticket::with('user', 'latestMessage')->latest()->get();
            return BaseController::sendResponse($tickets, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public static function getAllReplies(Ticket $ticket): JsonResponse
    {
        try {
            $messages = $ticket->messages;

            return BaseController::sendResponse($messages, __('messages.sent_data'));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }
    // public static function getAllReplies($ticket_id): JsonResponse
    // {
    //     try {
    //         $messages = TicketMessage::where('ticket_id', $ticket_id)->latest()->get();

    //         return BaseController::sendResponse($messages, __('messages.sent_data'));
    //     } catch (\Throwable $th) {
    //         return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
    //     }
    // }

    public static function store($data): JsonResponse
    {
        try {
            $ticket = Ticket::create([
                'user_id' => auth()->id(),
                'subject' => $data['subject'],
                // 'priority' => $data['priority'] ?? 'normal',
            ]);

            $message = $ticket->messages()->create([
                // 'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $data['message'],
                // 'attachments' => $attachments,
            ]);

            if (isset($data['attachments']) && !empty($data['attachments'])) {
                foreach ($data['attachments'] as $image) {
                    $message->attachments()->create([
                        // 'ticket_message_id' => $message->id,
                        'file_path' => saveImage($image, self::$image_folder)
                    ]);
                }
            }

            event(new TicketMessageCreated($message));

            return BaseController::sendResponse($ticket->load('messages'), __('messages.store_successfully', ['item' => __('messages.ticket')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.ticket')]), [$th->getMessage()], 500);
        }
    }

    public static function show($ticket): JsonResponse
    {
        foreach (auth()->user()->unreadNotifications as $notification) {
            if (
                $notification->type === TicketReplyNotification::class &&
                $notification->data['ticket_id'] === $ticket['id']
            ) {
                $notification->markAsRead();
            }
        }

        return BaseController::sendResponse($ticket->load(['messages.user', 'user']), __('messages.sent_data'));
    }

    public static function updateStatus($data, $ticket)
    {
        $ticket->update(['status' => $data['status']]);
        return BaseController::sendResponse(['status' => 'updated'], __('messages.update_successfully', ['item' => __('messages.ticket')]));
    }

    public static function reply($data, $ticket): JsonResponse
    {
        try {
            $message = $ticket->messages()->create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $data['message'],
                // 'attachments' => $attachments,
            ]);
            if (isset($data['attachments']) && !empty($data['attachments'])) {
                foreach ($data['attachments'] as $image) {
                    TicketAttachment::create([
                        'ticket_message_id' => $message->id,
                        'file_path' => saveImage($image, self::$image_folder)
                    ]);
                }
            }

            // إشعار للطرف الآخر
            // $isAdmin = auth()->user()->can('reply to messages');
            // $notifiable = $isAdmin ? $ticket->user : user()->whereHas('permissions', fn($q) => $q->where('name', 'reply to messages'))->first();

            // if ($notifiable) {
            //     $notifiable->notify(new TicketReplyNotification($ticket, $message));
            // }
            event(new TicketMessageCreated($message));


            return BaseController::sendResponse($message, __('messages.store_successfully', ['item' => __('messages.message')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.message')]), [$th->getMessage()], 500);
        }
    }
}
