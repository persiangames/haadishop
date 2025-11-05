<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'affiliate_code',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'last_login_at',
        'status',
        'loyalty_points_total',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    // Relationships
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function affiliateReferrals()
    {
        return $this->hasMany(AffiliateReferral::class, 'affiliate_user_id');
    }

    public function lotteryEntries()
    {
        return $this->hasMany(LotteryEntry::class, 'buyer_user_id');
    }

    public function loyaltyPoints()
    {
        return $this->hasMany(LoyaltyPoint::class);
    }

    // Helpers
    public function generateAffiliateCode(): string
    {
        do {
            $code = strtoupper(substr(md5($this->id . time()), 0, 8));
        } while (self::where('affiliate_code', $code)->exists());

        return $code;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->affiliate_code)) {
                $user->affiliate_code = $user->generateAffiliateCode();
            }
        });
    }
}

