<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['belly', 'legs', 'upper', 'cardio', 'pain', 'food', 'body'];

    protected $fillable = [
        'slug',
        'title',
        'hook',
        'category',
        'seconds',
        'sets',
        'work_seconds',
        'rest_seconds',
        'reps',
        'level',
        'muscles',
        'equipment',
        'calories_est',
        'tips',
        'description',
        'thumb',
        'video_url',
        'is_home',
        'is_active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'tips' => 'array',
            'is_home' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function toPublic(): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'ar' => $this->hook,
            'cat' => $this->category,
            'sec' => $this->seconds,
            'sets' => $this->sets ?? 3,
            'work' => $this->work_seconds ?? 40,
            'rest' => $this->rest_seconds ?? 20,
            'reps' => $this->reps,
            'level' => $this->level,
            'muscles' => $this->muscles,
            'equipment' => $this->equipment,
            'calories_est' => $this->calories_est,
            'tips' => $this->tips,
            'description' => $this->description,
            'img' => $this->thumb,
            'video' => $this->video_url,
            'home' => $this->is_home,
        ];
    }

    public function toAdmin(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'hook' => $this->hook,
            'category' => $this->category,
            'seconds' => $this->seconds,
            'sets' => $this->sets,
            'work_seconds' => $this->work_seconds,
            'rest_seconds' => $this->rest_seconds,
            'reps' => $this->reps,
            'level' => $this->level,
            'muscles' => $this->muscles,
            'equipment' => $this->equipment,
            'calories_est' => $this->calories_est,
            'tips' => $this->tips ?? [],
            'description' => $this->description,
            'thumb' => $this->thumb,
            'video_url' => $this->video_url,
            'is_home' => $this->is_home,
            'is_active' => $this->is_active,
            'sort' => $this->sort,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
