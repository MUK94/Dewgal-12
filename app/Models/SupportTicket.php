<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

class SupportTicket extends Model
{
    use SoftDeletes;
    public function supportCategory()
    {
        return $this->belongsTo(SupportCategory::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function supportTicketReplies()
    {
        return $this->hasMany(SupportTicketReply::class)->orderBy('created_at', 'desc');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeMediumPriority($query)
    {
        return $query->where('priority', 'medium');
    }

    public function scopeLowPriority($query)
    {
        return $query->where('priority', 'low');
    }

    public function scopeRegularPriority($query)
    {
        return $query->where('priority', 'regular');
    }

    public function scopeUnseen($query)
    {
        return $query->where('seen', 0);
    }

    public function scopePending($query)
    {
        return $query->where('status', 0);
    }
}
