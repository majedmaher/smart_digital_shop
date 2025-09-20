<?php

namespace App\Notifications;

use App\Models\SuspiciousTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousTransactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected SuspiciousTransaction $transaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(SuspiciousTransaction $transaction)
    {
        $this->transaction = $transaction;
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
        $transaction = $this->transaction;
        $user = $transaction->user;
        $payment = $transaction->payment;

        return (new MailMessage)
            ->subject(__('messages.suspicious_transaction_alert_subject'))
            ->greeting(__('messages.hello') . ' ' . $notifiable->name)
            ->line(__('messages.suspicious_transaction_detected'))
            ->line(__('messages.transaction_details'))
            ->line(__('messages.transaction_id') . ': #' . $transaction->id)
            ->line(__('messages.payment_id') . ': #' . $payment->id)
            ->line(__('messages.user_name') . ': ' . $user->name)
            ->line(__('messages.user_email') . ': ' . $user->email)
            ->line(__('messages.amount') . ': ' . $transaction->formatted_amount)
            ->line(__('messages.risk_score') . ': ' . $transaction->risk_score . '/100')
            ->line(__('messages.risk_level') . ': ' . $transaction->risk_level_label)
            ->line(__('messages.user_ip') . ': ' . $transaction->user_ip)
            ->line(__('messages.user_country') . ': ' . $transaction->user_country)
            ->line(__('messages.card_country') . ': ' . $transaction->card_country)
            ->line(__('messages.analyzed_at') . ': ' . $transaction->analyzed_at->format('Y-m-d H:i:s'))
            ->when($transaction->hasCountryMismatch(), function ($mail) use ($transaction) {
                $mail->line(__('messages.country_mismatch_warning'));
                $mail->line(__('messages.mismatch_details') . ': ' . $transaction->getCountryMismatchDetails()['description']);
            })
            ->line(__('messages.risk_factors'))
            ->when($transaction->risk_factors, function ($mail) use ($transaction) {
                foreach ($transaction->risk_factors as $factor) {
                    $mail->line("- " . $factor['description']);
                }
            })
            ->action(__('messages.review_transaction'), url('/admin/suspicious-transactions/' . $transaction->id))
            ->line(__('messages.urgent_review_required'))
            ->line(__('messages.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $transaction = $this->transaction;

        return [
            'type' => 'suspicious_transaction',
            'transaction_id' => $transaction->id,
            'payment_id' => $transaction->payment_id,
            'user_id' => $transaction->user_id,
            'user_name' => $transaction->user->name,
            'user_email' => $transaction->user->email,
            'risk_score' => $transaction->risk_score,
            'risk_level' => $transaction->risk_level,
            'amount' => $transaction->amount_cents / 100,
            'user_ip' => $transaction->user_ip,
            'user_country' => $transaction->user_country,
            'card_country' => $transaction->card_country,
            'has_country_mismatch' => $transaction->hasCountryMismatch(),
            'analyzed_at' => $transaction->analyzed_at,
            'message' => __('messages.suspicious_transaction_detected'),
            'action_url' => url('/admin/suspicious-transactions/' . $transaction->id),
        ];
    }
}
