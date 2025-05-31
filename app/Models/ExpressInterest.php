<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
// If you are using soft deletes on your User model and you need to access deleted users,
// ensure you have the HasFactory trait if you are using model factories.
// use Illuminate\Database\Eloquent\Factories\HasFactory; // Uncomment if needed

class ExpressInterest extends Model
{
    // If you plan to use factories, uncomment this:
    // use HasFactory;

    // This relationship uses 'user_id' by convention.
    // It also handles soft deleted users using ->withTrashed().
    // Since you also have a 'recipient()' method pointing to 'user_id',
    // consider if this 'user()' method is still needed or if 'recipient()' is clearer.
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class); // Changed from hasmany to hasMany (camelCase)
    }

    protected $table = 'express_interests';

    protected $fillable = [
        'user_id',       // This is the RECIPIENT of the interest
        'interested_by', // This is the SENDER of the interest
        'status',
    ];

    /**
     * Get the user who SENT this interest. (Uses 'interested_by' column)
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'interested_by');
    }

    /**
     * Get the user who RECEIVED this interest. (Uses 'user_id' column)
     * This is the 'recipient' relationship that provides clear semantics.
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
