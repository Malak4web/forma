<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'phone', 'user_id', 'goal', 'sex', 'age', 'weight', 'height', 'activity', 'kcal', 'protein', 'carbs', 'fat', 'source', 'status', 'ip'];
}
