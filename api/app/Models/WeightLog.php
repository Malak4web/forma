<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeightLog extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'logged_on', 'kg'];

    protected function casts(): array
    {
        return ['logged_on' => 'date:Y-m-d', 'kg' => 'float'];
    }
}
