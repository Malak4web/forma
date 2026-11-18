<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    use SoftDeletes;

    protected $fillable = ['slug', 'title', 'hook', 'category', 'seconds', 'level', 'muscles', 'tips', 'thumb', 'video_url', 'is_home', 'sort'];

    protected function casts(): array
    {
        return ['tips' => 'array', 'is_home' => 'boolean'];
    }

    public function toPublic(): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'ar' => $this->hook,
            'cat' => $this->category,
            'sec' => $this->seconds,
            'level' => $this->level,
            'muscles' => $this->muscles,
            'tips' => $this->tips,
            'img' => $this->thumb,
            'video' => $this->video_url,
            'home' => $this->is_home,
        ];
    }
}
