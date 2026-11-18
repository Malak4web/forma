<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use SoftDeletes;

    /** Days anyone (even guests) can open before paying. */
    public const FREE_DAYS = 3;

    protected $fillable = ['slug', 'title', 'subtitle', 'pitch', 'days', 'minutes', 'level', 'thumb', 'tag', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(ProgramWeek::class)->orderBy('number');
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }

    public function isFreeDay(int $day): bool
    {
        return $day >= 1 && $day <= self::FREE_DAYS;
    }

    /** The plan to pitch for this program: the most specific paid plan (fewest programs), then cheapest. */
    public function primaryPlan(): ?Plan
    {
        return $this->plans()->withCount('programs')->where('price', '>', 0)->where('is_active', true)
            ->orderBy('programs_count')->orderBy('price')->first();
    }

    public function toPublic(): array
    {
        $cheapest = $this->plans->where('price', '>', 0)
            ->sortBy([['programs_count', 'asc'], ['price', 'asc']])->first();

        return [
            'id' => $this->slug,
            'title' => $this->title,
            'sub' => $this->subtitle,
            'pitch' => $this->pitch,
            'days' => $this->days,
            'mins' => $this->minutes,
            'level' => $this->level,
            'img' => $this->thumb,
            'tag' => $this->tag,
            'plan' => $cheapest?->slug,
            'weeks' => $this->weeks->map(fn (ProgramWeek $w) => ['t' => $w->title, 'pool' => $w->pool])->values(),
        ];
    }
}
