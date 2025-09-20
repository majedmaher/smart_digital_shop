<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspiciousTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'user_id',
        'risk_score',
        'risk_factors',
        'user_ip',
        'user_country',
        'card_country',
        'amount_cents',
        'status',
        'analyzed_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
    ];

    protected $casts = [
        'risk_factors' => 'array',
        'analyzed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the payment that owns the suspicious transaction.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user that owns the suspicious transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the transaction.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending review transactions.
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    /**
     * Scope for approved transactions.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for blocked transactions.
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    /**
     * Scope for high risk transactions.
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>=', 80);
    }

    /**
     * Scope for country mismatch transactions.
     */
    public function scopeCountryMismatch($query)
    {
        return $query->whereNotNull('user_country')
                    ->whereNotNull('card_country')
                    ->whereRaw('user_country != card_country');
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount_cents / 100, 2) . ' ريال';
    }

    /**
     * Get risk level.
     */
    public function getRiskLevelAttribute(): string
    {
        if ($this->risk_score >= 90) {
            return 'critical';
        } elseif ($this->risk_score >= 70) {
            return 'high';
        } elseif ($this->risk_score >= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get risk level label.
     */
    public function getRiskLevelLabelAttribute(): string
    {
        return match($this->risk_level) {
            'critical' => 'حرج',
            'high' => 'عالي',
            'medium' => 'متوسط',
            'low' => 'منخفض',
            default => 'غير محدد'
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending_review' => 'في انتظار المراجعة',
            'approved' => 'موافق عليه',
            'blocked' => 'محظور',
            default => 'غير محدد'
        };
    }

    /**
     * Check if transaction has country mismatch.
     */
    public function hasCountryMismatch(): bool
    {
        return $this->user_country &&
               $this->card_country &&
               $this->user_country !== $this->card_country;
    }

    /**
     * Get country mismatch details.
     */
    public function getCountryMismatchDetails(): array
    {
        if (!$this->hasCountryMismatch()) {
            return [];
        }

        return [
            'user_country' => $this->user_country,
            'card_country' => $this->card_country,
            'mismatch_severity' => 'high',
            'description' => "المستخدم من {$this->user_country} والبطاقة من {$this->card_country}"
        ];
    }
}
