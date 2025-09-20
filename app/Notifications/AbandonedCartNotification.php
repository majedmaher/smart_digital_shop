<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Coupon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbandonedCartNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;
    protected ?Coupon $discountCoupon;
    protected string $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, ?Coupon $discountCoupon = null, string $type = 'reminder')
    {
        $this->order = $order;
        $this->discountCoupon = $discountCoupon;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $greeting = $this->getGreeting();
        $message = $this->getMessage();
        $actionText = $this->getActionText();
        $actionUrl = $this->getActionUrl();

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($message)
            ->action($actionText, $actionUrl);

        // إضافة تفاصيل الطلب
        $mailMessage->line(__('messages.order_details'));
        $mailMessage->line(__('messages.order_number') . ': #' . $this->order->id);
        $mailMessage->line(__('messages.total_amount') . ': ' . number_format($this->order->total_price, 2) . ' ريال');

        // إضافة قائمة المنتجات
        foreach ($this->order->items as $item) {
            $mailMessage->line("- {$item->product->title} (x{$item->quantity})");
        }

        // إضافة كوبون الخصم إذا كان متوفراً
        if ($this->discountCoupon) {
            $mailMessage->line(__('messages.special_discount_offer'));
            $mailMessage->line(__('messages.discount_code') . ': ' . $this->discountCoupon->code);
            $mailMessage->line(__('messages.discount_value') . ': ' . $this->discountCoupon->value . '%');
            $mailMessage->line(__('messages.discount_expires') . ': ' . $this->discountCoupon->expires_at->format('Y-m-d H:i'));
        }

        $mailMessage->line(__('messages.thank_you_for_choosing_us'));

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'abandoned_cart',
            'order_id' => $this->order->id,
            'order_total' => $this->order->total_price,
            'notification_type' => $this->type,
            'discount_coupon' => $this->discountCoupon ? [
                'code' => $this->discountCoupon->code,
                'value' => $this->discountCoupon->value,
                'expires_at' => $this->discountCoupon->expires_at,
            ] : null,
            'message' => $this->getMessage(),
            'action_url' => $this->getActionUrl(),
        ];
    }

    /**
     * Get notification subject based on type
     */
    private function getSubject(): string
    {
        $subjects = [
            'reminder' => __('messages.abandoned_cart_reminder_subject'),
            'urgent' => __('messages.abandoned_cart_urgent_subject'),
            'final' => __('messages.abandoned_cart_final_subject'),
        ];

        return $subjects[$this->type] ?? $subjects['reminder'];
    }

    /**
     * Get greeting message
     */
    private function getGreeting(): string
    {
        return __('messages.hello') . ' ' . $this->order->user->name . '!';
    }

    /**
     * Get main message based on type
     */
    private function getMessage(): string
    {
        $messages = [
            'reminder' => __('messages.abandoned_cart_reminder_message'),
            'urgent' => __('messages.abandoned_cart_urgent_message'),
            'final' => __('messages.abandoned_cart_final_message'),
        ];

        return $messages[$this->type] ?? $messages['reminder'];
    }

    /**
     * Get action button text
     */
    private function getActionText(): string
    {
        return __('messages.complete_your_order');
    }

    /**
     * Get action URL
     */
    private function getActionUrl(): string
    {
        return url('/orders/' . $this->order->id . '/complete');
    }
}
