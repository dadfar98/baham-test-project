<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Order;

class OrderTest extends TestCase
{
    use DatabaseTransactions;


    public function testCoinsAddedOnSuccessfulTransaction()
    {
        // create mock user
        $user = User::factory()->create([
            'name' => 'ali',
            'email' => 'ali@gmail.com',
            'username' => 'ali',
            'coins' => 100,
        ]);

        $this->actingAs($user); // mock authentication


        // Call the route with required parameters
        $response = $this->post(route('buy-coins'), [
            'coins_count' => 50, // Buying 50 coins
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true,
                     'message' => 'transaction successfull',
                 ]);

        // Assert that the user's coin balance has been updated
        $this->assertEquals(150, $user->refresh()->coins); // User should now have 150 coins
    }

    public function testCreateOrderSuccessIfNotEnoughCoins(): void
    {
        // create mock user
        $user = User::factory()->create([
            'name' => 'ali',
            'email' => 'ali@gmail.com',
            'username' => 'ali',
            'coins' => 10,
        ]);

        $this->actingAs($user); // mock authentication

        $response = $this->post(route('create-order'), [
            'followers_count' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => false,
                'message' => 'You need (40) coins to get 10 followers. You have (10) coins. You can get coins by following other orders or buy coins.',
            ]);

    }

    public function testCreateOrderSuccessIfEnoughCoins(): void
    {
        // create mock user
        $user = User::factory()->create([
            'name' => 'ali',
            'email' => 'ali@gmail.com',
            'username' => 'ali',
            'coins' => 100,
        ]);

        $this->actingAs($user); // mock authentication

        $request_data =[ 'followers_count' => 10];

        $response = $this->post(route('create-order'), $request_data);

        // Assert that the order was inserted into the database
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status'  => 'ongoing',
            'followers_count' => $request_data['followers_count'],
        ]);

        // Assert that the user's coins have been updated
        $this->assertEquals(100 - ($request_data['followers_count'] * Order::FOLLOWER_PRICE), $user->fresh()->coins);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'order' => [
                    'user_id' => $user->id,
                    'followers_count' => $request_data['followers_count'],
                ]
             ]);
    }


    public function testGetOngoingOrders() : void
    {
        // Create a user
        $user1 = User::factory()->create([
            'name' => 'ali',
            'email' => 'ali@gmail.com',
            'username' => 'ali',
            'coins' => 100,
        ]);

        $user2 = User::factory()->create([
            'name' => 'sadegh',
            'email' => 'sadegh@gmail.com',
            'username' => 'sadegh',
            'coins' => 100,
        ]);

        $order1 = Order::factory()->create([
            'user_id' => $user1->id,
            'status'  => 'ongoing',
            'followers_count' => 10,
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user2->id,
            'status'  => 'done',
            'followers_count' => 20,
        ]);


        // Call the controller function
        $response = $this->actingAs($user2)->get(route('get-ongoing-orders'));

        // Assert the response
        $response->assertStatus(200)
                 ->assertJson([
                    'status' => true,
                    'orders' => [
                        [
                            'id' => $order1->id,
                            'status' => 'ongoing',
                            'user' => [
                                'id' => $order1->user->id,
                                'username' => $order1->user->username,
                            ]
                        ],
                    ]
                 ]);
    }


    public function testFollowByOrderOrderDone(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $order1 = Order::factory()->create(['user_id'=>  $user2->id, 'status' => 'done', 'followers_count' => 2]);

        $response = $this->actingAs($user1)->post('api/orders/follow/'.$order1->id, []);


        $this->assertDatabaseMissing('followings', [
            'user_id' => $user1->id,
            'follows_user_id' => $user2->id,
            'order_id' => null
        ]);

        $response->assertStatus(400)
        ->assertJson([
           'status' => false,
           'message' => 'order is already done'
        ]);

    }

    public function testFollowByOrderOrderOngoing(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $order1 = Order::factory()->create(['user_id'=>  $user2->id, 'status' => 'ongoing', 'followers_count' => 2]);

        $response = $this->actingAs($user1)->post('api/orders/follow/'.$order1->id, []);


        $this->assertDatabaseHas('followings', [
            'user_id' => $user1->id,
            'follows_user_id' => $user2->id,
            'order_id' => $order1->id
        ]);

        $response->assertStatus(200);

        $this->assertEquals($response['status'], True);

    }
}
