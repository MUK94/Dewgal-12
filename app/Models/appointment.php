<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'recipient_id',
        'scheduled_at',
        'location',
        'notes',
        'status',
        'accepted_at',
        'declined_at',
        'cancelled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
