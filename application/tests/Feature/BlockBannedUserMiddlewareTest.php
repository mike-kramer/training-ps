<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockBannedUsersMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BlockBannedUserMiddlewareTest extends TestCase
{

    public function testBlockedUser403(): void
    {
        $user = User::factory()->state(["status" => User::STATUS_BANNED])->create();
        Route::get(
            "/api/test-route",
            fn() => ["success" => true]
        )->middleware(BlockBannedUsersMiddleware::class);

        $response = $this->actingAs($user)->get('/api/test-route');

        $response->assertStatus(403);
    }
}
