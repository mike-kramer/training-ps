<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UsersListViewTest extends TestCase
{
    use RefreshDatabase;
    private User $adminUser;

    /**
     * @var Collection<User>
     */
    private Collection $users;

    public function setUp(): void
    {
        parent::setUp();
        $roleAdmin = Role::where("name", "admin")->first();
        $this->assertNotNull($roleAdmin);
        $this->adminUser = User::factory()->for($roleAdmin)->state([
            "created_at" => now()
        ])->create();
        $this->users = User::factory()->state(new Sequence(
            ["created_at" => now()->subMinutes(1)],
            ["created_at" => now()->subMinutes(2)],
            ["created_at" => now()->subMinutes(3)],
        ))->count(3)->create();
        $this->users->prepend($this->adminUser);
    }

    public function testGetUsers(): void
    {

        $resp = $this->actingAs($this->adminUser)->get('/api/admin/users');
        $resp->assertStatus(200);
        $data = $this->users->map(function ($user) {
            $res = $user->toArray();
            unset($res['role']);
            return $res;
        })->toArray();
        $resp->assertJson([
            "data" => $data,
        ]);
    }

    public function testGetUsersByUserWithoutPermission(): void
    {
        $notAdminUser = User::factory()->create();
        $resp = $this->actingAs($notAdminUser)->get('/api/admin/users');
        $resp->assertStatus(403);
    }
}
