<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneResetToken extends Model
{
    protected $fillable = [
        'phone','token_hash','expires_at','used_at','ip'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];
}
