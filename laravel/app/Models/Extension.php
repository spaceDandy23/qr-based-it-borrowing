<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Extension extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'new_end',
        'reason',
        'status',
        'requested_at',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'new_end' => 'date',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
