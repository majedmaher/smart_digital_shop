<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\HidesTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasRoles, HasFactory, Notifiable, HidesTimestamps;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'otp_code',
        'otp_expires_at',
        'phone',
        'date',
        'gender',
        'photo',
        'referral_code',
        'points',
        'wallet_balance',
        'social_provider',
        'social_id',
        'social_linked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'otp_expires_at',
        'email_verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',

        ];
    }

    public function generateReferralCode(): string
    {
        if (!$this->referral_code) {
            $this->referral_code = strtoupper(uniqid('REF'));
            $this->save();
        }
        return $this->referral_code;
    }

    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function successfulReferrals()
    {
        return $this->referralsMade()->whereNotNull('referred_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Check if user has social account linked
     */
    public function hasSocialAccount(): bool
    {
        return !is_null($this->social_provider) && !is_null($this->social_id);
    }

    /**
     * Get social provider name
     */
    public function getSocialProviderName(): ?string
    {
        if (!$this->hasSocialAccount()) {
            return null;
        }

        return match($this->social_provider) {
            'google' => 'جوجل',
            'facebook' => 'فيسبوك',
            'apple' => 'آبل',
            default => $this->social_provider,
        };
    }

    /**
     * Scope for users with social accounts
     */
    public function scopeWithSocialAccount($query)
    {
        return $query->whereNotNull('social_provider')->whereNotNull('social_id');
    }

    /**
     * Scope for users by social provider
     */
    public function scopeBySocialProvider($query, string $provider)
    {
        return $query->where('social_provider', $provider);
    }
}
