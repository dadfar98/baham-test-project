<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use App\Model\User;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'followers_count'];

    public const FOLLOWER_PRICE = 4;
    public const FOLLOWING_REWARD = 2;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
