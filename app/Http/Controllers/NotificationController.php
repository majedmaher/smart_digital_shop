<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications() // كل الإشعارات (مقروءة وغير مقروءة)
            ->latest()
            ->get();

        return BaseController::sendResponse(NotificationResource::collection($notifications), __('messages.sent_data'));
    }

    public function markAsRead($id)
    {
        try {
            $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();

            $notification->markAsRead();

            return BaseController::sendResponse(NotificationResource::make($notification), __('messages.update_successfully', ['item' => __('messages.notification')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [], 500);
        }
    }

    public function markMultipleAsRead(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array'
            ]);
            $ids = $request->ids; // array of notification IDs

            // تحديث فقط غير المقروءة
            auth()->user()->notifications()
                ->whereIn('id', $ids)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            // استرجاع كل الإشعارات المحددة
            $notifications = auth()->user()->notifications()
                ->whereIn('id', $ids)
                ->get();

            return BaseController::sendResponse(NotificationResource::collection($notifications), __('messages.update_successfully', ['item' => __('messages.notification')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.something_went_wrong'), [$th->getMessage()], 500);
        }
    }

    public function markAllAsRead()
    {
        $notifications = auth()->user()->unreadNotifications()->get();
        $notifications->each(function ($notification) {
            $notification->markAsRead();
        });

        return BaseController::sendResponse(NotificationResource::collection($notifications), __('messages.update_successfully', ['item' => __('messages.notification')]));
    }
}
