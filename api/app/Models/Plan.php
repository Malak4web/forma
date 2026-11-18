<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = ['slug', 'name', 'price', 'period', 'duration_days', 'features', 'cta', 'tag', 'highlight', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['features' => 'array', 'highlight' => 'boolean', 'is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            'monthly' => 'شهريًا',
            'once' => 'مرة واحدة',
            default => 'على طول',
        };
    }

    public function toPublic(): array
    {
        return [
            'id' => $this->slug,
            'name' => $this->name,
            'price' => $this->price,
            'period' => $this->periodLabel(),
            'features' => $this->features,
            'cta' => $this->cta,
            'tag' => $this->tag,
            'highlight' => $this->highlight,
            'unlocks' => $this->programs->pluck('slug')->values(),
        ];
    }
}
