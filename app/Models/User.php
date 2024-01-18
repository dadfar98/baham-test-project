<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Models\Following;
use App\Models\Order;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followings', 'follows_user_id', 'user_id');
    }

    public function followings()
    {
        return $this->belongsToMany(User::class, 'followings', 'user_id', 'follows_user_id');
    }

    public function isFollowing(User $user) {
        return Following::where('user_id', $this->id)->where('follows_user_id', $user->id)->exists();
    }

    public function canFollow(User $userToFollow) {

        if ($this->id == $userToFollow->id) {
            $response = [
                'status' => false,
                'message' => "you can't follow yourself"
            ];
            return $response;
        }

        if ($this->isFollowing($userToFollow)) {
            $response = [
                'status' => false,
                'message' => "already following this user!"
            ];
            return $response;
        }

        $response = [
            'status' => true,
            'message' => "ok"
        ];
        return $response;
    }

    public function follow(User $user, Order $ongoing_order = null) {
        $following = Following::create([
            'user_id' => $this->id,
            'follows_user_id' => $user->id,
        ]);

        if ($ongoing_order and $ongoing_order->status == 'ongoing') {
            $following->order_id = $ongoing_order->id;
            $following->save();

            $this->coins += Order::FOLLOWING_REWARD;
            $this->save();

            $number_of_follows = Following::where('order_id', $ongoing_order->id)->count();

            if ($number_of_follows == $ongoing_order->followers_count) {
                $ongoing_order->status = 'done';
                $ongoing_order->save();
            }
        }

        return $following;
    }
}
