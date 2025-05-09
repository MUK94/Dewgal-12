<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicketReply extends Model
{
    use SoftDeletes;
    public function supportTicket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_user_id');
    }

    public function scopeUnseen($query)
    {
        return $query->where('seen', 0);
    }
}
