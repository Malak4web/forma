<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    public const STATUSES = ['new', 'contacted', 'converted'];

    protected $fillable = [
        'name',
        'phone',
        'user_id',
        'goal',
        'diet',
        'sex',
        'age',
        'weight',
        'height',
        'activity',
        'kcal',
        'protein',
        'carbs',
        'fat',
        'source',
        'status',
        'notes',
        'contacted_at',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'activity' => 'float',
            'contacted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
