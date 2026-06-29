<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    use HasFactory;

    protected $table = 'damage_reports';

    protected $fillable = [
        'request_id',
        'description',
        'severity',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
