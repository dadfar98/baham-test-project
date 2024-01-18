<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Order;


class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getOngoingOrders()
    {
        $user = auth()->user();

        $already_following = $user->followings()->pluck('follows_user_id');

        $orders = Order::where('status', 'ongoing')
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('user_id', $already_following)
            ->with(['user' => function ($query) {
                $query->select('id', 'username');
            }])
            ->get();



        $response = [
            'status' => true,
            'orders' => $orders
        ];

        return response($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $needed_coins_amount = $request->followers_count * Order::FOLLOWER_PRICE;
        if ($user->coins < $needed_coins_amount) {
            return [
                'status' => false,
                'message' => "You need (". $needed_coins_amount. ") coins to get ". $request->followers_count. " followers. You have (". $user->coins .") coins. " .
                "You can get coins by following other orders or buy coins." ];
        }

        $user->coins -= $needed_coins_amount;
        $user->save();

        $order = Order::create([
            'user_id' => $user->id,
            'followers_count' => $request->followers_count,
        ]);

        $response = [
            'status' => true,
            'order' => $order
        ];

        return response($response, 201);
    }


    public function followByOrder(Request $request, Order $orderToFollow) {

        if ($orderToFollow->status == 'done') {
            $response = [
                'status' => false,
                "message" => "order is already done"
            ];

            return response($response, 400);
        }

        $user = auth()->user();

        $can_follow = $user->canFollow($orderToFollow->user);

        if ($can_follow['status']) {
            $following = $user->follow($orderToFollow->user, $orderToFollow);

            $response = [
                'status' => true,
                "following" => $following
            ];

            return response($response, 200);
        }

        return $can_follow;
    }

    public function buyCoins(Request $request) {
        //Do the bank transaction to pay for new coins;
        $transaction_success = true;

        if ($transaction_success) {
            $user = auth()->user();

            $user->coins += $request->coins_count;
            $user->save();

            $response = [
                'status' => true,
                'message' => "transaction successfull"
            ];

            return response($response, 200);
        }
    }
}
