<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use SoftDeletes;

    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    protected $fillable = ['user_id', 'plan_id', 'amount', 'method', 'status', 'payer_phone', 'receipt_path', 'admin_note'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->ref ??= static::newRef();
        });
    }

    public static function newRef(): string
    {
        do {
            $ref = 'FO-'.Str::upper(Str::random(6));
        } while (static::withTrashed()->where('ref', $ref)->exists());

        return $ref;
    }

    public function getRouteKeyName(): string
    {
        return 'ref';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
