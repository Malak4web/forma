<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramWeek extends Model
{
    protected $fillable = ['program_id', 'number', 'title', 'pool'];

    protected function casts(): array
    {
        return ['pool' => 'array'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
