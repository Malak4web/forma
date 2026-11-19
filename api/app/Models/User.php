<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'phone', 'email', 'password', 'goal'])]
#[Hidden(['password', 'remember_token', 'is_admin'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(WorkoutCompletion::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(WeightLog::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** Subscriptions that are active right now (lifetime or not yet ended). */
    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()
            ->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** Program ids the user has paid access to. */
    public function unlockedProgramIds(): Collection
    {
        return once(fn () => Program::query()
            ->whereHas('plans.subscriptions', fn ($q) => $q
                ->where('user_id', $this->id)
                ->where('starts_at', '<=', now())
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now())))
            ->pluck('id'));
    }

    public function hasAccessTo(Program $program): bool
    {
        return $this->unlockedProgramIds()->contains($program->id);
    }
}
