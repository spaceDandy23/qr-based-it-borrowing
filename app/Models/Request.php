<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'equipment_id',
        'user_id',
        'purpose',
        'start_date',
        'end_date',
        'status',
        'decided_at',
        'checked_out_at',
        'returned_at',
        'return_condition',
        'return_notes',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'decided_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function extension()
    {
        return $this->hasOne(Extension::class)->latestOfMany();
    }

    public function damageReport()
    {
        return $this->hasOne(DamageReport::class)->latestOfMany();
    }

    public function isOverdue(): bool
    {
        return $this->status === 'Checked Out' && $this->end_date->isPast();
    }
}
