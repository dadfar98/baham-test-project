<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Order;
use App\Models\Following;

class FollowTest extends TestCase
{
    use DatabaseTransactions;

    public function testCanFollow()
    {
        // Create two user instances
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
        // Test when trying to follow a user who is not being followed
        $response = $user1->canFollow($user2);
        $this->assertTrue($response['status']);
        $this->assertEquals("ok", $response['message']);

        // Test when trying to follow oneself
        $response = $user1->canFollow($user1);
        $this->assertFalse($response['status']);
        $this->assertEquals("you can't follow yourself", $response['message']);

        // Test when trying to follow a user already being followed
        $user1->follow($user2); // Assuming there is a follow method in the User model
        $response = $user1->canFollow($user2);
        $this->assertFalse($response['status']);
        $this->assertEquals("already following this user!", $response['message']);

    }


    public function testFollowWithoutOngoingOrder()
    {
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

        $following = $user1->follow($user2);

        $initialCoinsUser1 = $user1->coins;

        $this->assertDatabaseHas('followings', [
            'user_id' => $user1->id,
            'follows_user_id' => $user2->id,
            'order_id' => null
        ]);

        $this->assertInstanceOf(Following::class, $following);
        $this->assertEquals($user1->id, $following->user_id);
        $this->assertEquals($user2->id, $following->follows_user_id);
        $this->assertNull($following->order_id);

        $this->assertEquals($initialCoinsUser1, $user1->coins);

    }

    public function testFollowWithDoneOrder()
    {
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

        $ongoing_order = Order::factory()->create(['user_id'=>  $user2->id, 'status' => 'done', 'followers_count' => 10]);

        $following = $user1->follow($user2, $ongoing_order);

        $this->assertDatabaseHas('followings', [
            'user_id' => $user1->id,
            'follows_user_id' => $user2->id,
            'order_id' => null
        ]);

        $this->assertInstanceOf(Following::class, $following);
        $this->assertEquals($user1->id, $following->user_id);
        $this->assertEquals($user2->id, $following->follows_user_id);
        $this->assertNull($following->order_id);
    }

    public function testFollowWithOngoingOrderStaysOngoing()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $ongoing_order = Order::factory()->create(['user_id'=>  $user2->id, 'status' => 'ongoing', 'followers_count' => 10]);

        $initialCoins = $user1->coins;

        $following = $user1->follow($user2, $ongoing_order);

        $this->assertDatabaseHas('followings', [
            'user_id' => $user1->id,
            'follows_user_id' => $user2->id,
            'order_id' => $ongoing_order->id
        ]);

        $this->assertInstanceOf(Following::class, $following);
        $this->assertEquals($user1->id, $following->user_id);
        $this->assertEquals($user2->id, $following->follows_user_id);
        $this->assertEquals($following->order_id, $ongoing_order->id);
        $this->assertEquals($ongoing_order->status, 'ongoing');

        // Check if the user received bonus coins
        $this->assertEquals($initialCoins + Order::FOLLOWING_REWARD, $user1->coins);
    }

    public function testFollowWithOngoingOrderTurnsDone()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $ongoing_order = Order::factory()->create(['user_id'=>  $user2->id, 'status' => 'ongoing', 'followers_count' => 2]);

        $initialCoinsUser1 = $user1->coins;
        $initialCoinsUser3 = $user3->coins;

        $following = $user1->follow($user2, $ongoing_order);
        $following2 = $user3->follow($user2, $ongoing_order);

        $this->assertDatabaseHas('followings', [
            'user_id' => $user1->id,
            'follows_user_id' => $user2->id,
            'order_id' => $ongoing_order->id
        ]);

        $this->assertInstanceOf(Following::class, $following);

        $this->assertEquals($user1->id, $following->user_id);
        $this->assertEquals($user2->id, $following->follows_user_id);
        $this->assertEquals($following->order_id, $ongoing_order->id);
        $this->assertEquals($ongoing_order->status, 'done');

        // Check if the user received bonus coins
        $this->assertEquals($initialCoinsUser1 + Order::FOLLOWING_REWARD, $user1->coins);
        $this->assertEquals($initialCoinsUser3 + Order::FOLLOWING_REWARD, $user3->coins);
    }

}
