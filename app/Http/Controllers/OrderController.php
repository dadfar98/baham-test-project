<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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

        $wanted_followers_count = $request->followers_count;
        $needed_coins_amount = $request->followers_count * Order::FOLLOWER_PRICE;

        if ($user->coins < $needed_coins_amount) {
            return [
                'status' => false,
                'message' => "You need (". $needed_coins_amount. ") coins to get ". $wanted_followers_count . " followers. You have (". $user->coins .") coins. " .
                "You can get coins by following other orders or buy coins." ];
        }

        try {
            $order = DB::transaction(function () use($user, $wanted_followers_count, $needed_coins_amount) {
                $user->coins -= $needed_coins_amount;
                $user->save();

                $order = Order::create([
                    'user_id' => $user->id,
                    'followers_count' => $wanted_followers_count,
                ]);

                return $order;
            });

            $response = [
                'status' => true,
                'order' => $order
            ];

        } catch(\Throwable $e) {
            $response = [
                'status' => false,
                'message' => "failed to create order"
            ];
        }

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
                'status' => is_null($following) ? false : true,
                'following' => $following,
                'message' => is_null($following) ? "follow failed" : "follow successfull"
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

            $wanted_coins_count = $request->coins_count;

            try {
                DB::transaction(function () use($user, $wanted_coins_count) {
                    $user->coins += $wanted_coins_count;
                    $user->save();
                });

                $response = [
                    'status' => true,
                    'message' => "transaction successfull"
                ];

            } catch (\Throwable $e) {
                $response = [
                    'status' => false,
                    'message' => "transaction failed"
                ];
            }

            return response($response, 200);
        }
    }
}
