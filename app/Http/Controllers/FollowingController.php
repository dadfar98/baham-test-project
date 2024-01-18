<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\User;
use App\Models\Order;


class FollowingController extends Controller
{
    public function follow(Request $request, User $userToFollow) {
        $user = auth()->user();

        $can_follow = $user->canFollow($userToFollow);

        if ($can_follow['status']) {
            $ongoing_order = Order::where('status', 'ongoing')->where('user_id', $userToFollow->id)->first();

            $following = $user->follow($userToFollow, $ongoing_order);

            $response = [
                'status' => true,
                "following" => $following
            ];

            return response($response, 200);
        }

        return $can_follow;
    }
}
