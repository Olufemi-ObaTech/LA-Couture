<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffMessage extends Model
{
    protected $fillable = ['from_id', 'to_id', 'subject', 'body', 'read_at', 'replied_to_id'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function from()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function to()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    public function repliedTo()
    {
        return $this->belongsTo(self::class, 'replied_to_id');
    }
}
