<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyLog extends Model
{
    use SoftDeletes;

    public const MAX_WATER = 16;

    protected $fillable = ['user_id', 'date', 'water', 'meals'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'water' => 'integer', 'meals' => 'array'];
    }

    public function toClient(): array
    {
        return ['date' => $this->date->toDateString(), 'water' => $this->water, 'meals' => array_values($this->meals ?? [])];
    }
}
