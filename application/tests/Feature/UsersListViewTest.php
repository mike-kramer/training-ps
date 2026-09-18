<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
    private ?CarbonImmutable $now = null;

    public function setUp(): void
    {
        parent::setUp();
        $roleAdmin = Role::where("name", "admin")->first();
        $this->assertNotNull($roleAdmin);
        $this->now = CarbonImmutable::now();
        $this->adminUser = User::factory()->for($roleAdmin)->state([
            "created_at" => $this->now
        ])->create();
        $this->users = User::factory()->state(new Sequence(
            ["created_at" => $this->now->subMinutes(1)],
            ["created_at" => $this->now->subMinutes(2)],
            ["created_at" => $this->now->subMinutes(3)],
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

    public function testGetUsersWithPagination(): void
    {
        for ($p = 1; $p <= 2; $p++) {
            $resp = $this
                ->actingAs($this->adminUser)
                ->get('/api/admin/users?perPage=2&page=' . $p);
            $resp->assertStatus(200);
            $data = $this->users->slice(($p - 1) * 2, 2)->map(function ($user) {
                $res = $user->toArray();
                unset($res['role']);
                return $res;
            })->values()->toArray();
            $resp->assertJson([
                "data" => $data,
                "meta" => [
                    "current_page" => $p,
                ]
            ]);
        }
    }

    public function testEmailFiltration(): void
    {
        $emailPart = explode("@", $this->adminUser->email)[0];
        $resp = $this->actingAs($this->adminUser)->get('/api/admin/users?email=' . $emailPart);
        $resp->assertStatus(200);
        $returnedData = $resp->json();
        $this->assertEquals(count($returnedData["data"]), 1);
        $this->assertEquals($returnedData["data"][0]["email"], $this->adminUser->email);
    }

    public function testCashboxIdFiltration(): void
    {
        $cashbox = Cashbox::factory()->state([
            "user_id" => $this->adminUser->id,
        ])->create();
        $resp = $this->actingAs($this->adminUser)->get('/api/admin/users?cashbox_id=' . $cashbox->id);
        $resp->assertStatus(200);
        $returnedData = $resp->json();
        $this->assertEquals(count($returnedData["data"]), 1);
        $this->assertEquals($returnedData["data"][0]["id"], $this->adminUser->id);

        $resp = $this->actingAs($this->adminUser)->get(
            '/api/admin/users?cashbox_id=' . ($cashbox->id + 1000)
        );
        $resp->assertStatus(200);
        $this->assertEquals(count($resp->json("data")), 0);

    }

    public function testCashboxNameFiltration(): void
    {
        $cashbox = Cashbox::factory()->state([
            "user_id" => $this->adminUser->id,
        ])->create();
        $resp = $this->actingAs($this->adminUser)->get('/api/admin/users?cashbox_name=' . $cashbox->name);
        $resp->assertStatus(200);
        $returnedData = $resp->json();
        $this->assertEquals(count($returnedData["data"]), 1);
        $this->assertEquals($returnedData["data"][0]["id"], $this->adminUser->id);
    }

    public function testRegDateFiltration(): void
    {
        $requestFromDate = $this->now->subMinutes(2);
        $resp = $this->actingAs($this->adminUser)->get('/api/admin/users?created_from=' . $requestFromDate);
        $resp->assertStatus(200);
        $data = $this->users->slice(0, 3)->map(function ($user) {
            $res = $user->toArray();
            unset($res['role']);
            return $res;
        })->values()->toArray();
        $this->assertEquals(3, count($resp->json("data")));
        $resp->assertJson([
            "data" => $data
        ]);
    }
}
