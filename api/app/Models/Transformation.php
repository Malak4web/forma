<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transformation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'age',
        'subtitle',
        'image_path',
        'sort',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function imageUrl(): string
    {
        return '/api/media/'.ltrim($this->image_path, '/');
    }

    public function toPublic(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'age' => $this->age,
            'subtitle' => $this->subtitle,
            'image_url' => $this->imageUrl(),
            'sort' => $this->sort,
            'is_active' => $this->is_active,
        ];
    }
}
