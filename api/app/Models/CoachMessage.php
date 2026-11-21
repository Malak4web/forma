<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoachMessage extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'admin_id', 'sender', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function toClient(): array
    {
        return [
            'id' => $this->id,
            'from' => $this->sender,
            'body' => $this->body,
            'coach' => $this->sender === 'coach' ? ($this->admin?->name ?? 'الكوتش') : null,
            'read' => (bool) $this->read_at,
            'at' => $this->created_at->toIso8601String(),
        ];
    }
}
