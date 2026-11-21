<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUp extends Model
{
    use SoftDeletes;

    public const STATUSES = ['on_track', 'needs_attention', 'delayed', 'completed'];

    protected $fillable = [
        'user_id',
        'admin_id',
        'followup_date',
        'next_followup_date',
        'status',
        'source',
        'weight_kg',
        'waist_cm',
        'chest_cm',
        'arm_cm',
        'thigh_cm',
        'hips_cm',
        'body_fat',
        'diet_adherence',
        'workout_adherence',
        'sleep_hours',
        'water_liters',
        'energy_level',
        'member_feedback',
        'coach_notes',
        'plan_adjustments',
    ];

    protected function casts(): array
    {
        return [
            'followup_date' => 'date:Y-m-d',
            'next_followup_date' => 'date:Y-m-d',
            'weight_kg' => 'float',
            'waist_cm' => 'float',
            'chest_cm' => 'float',
            'arm_cm' => 'float',
            'thigh_cm' => 'float',
            'hips_cm' => 'float',
            'body_fat' => 'float',
            'sleep_hours' => 'float',
            'water_liters' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
