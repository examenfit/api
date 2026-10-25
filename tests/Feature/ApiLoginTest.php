<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Safety net for the Laravel upgrade: the SPA cookie login the app (react-sanctum) relies on.
class ApiLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_endpoint_requires_authentication()
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_user_can_log_in_with_valid_credentials()
    {
        $user = User::factory()->create(['role' => 'docent']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_log_in_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(422);

        $this->assertGuest();
    }

    public function test_user_with_unknown_role_is_rejected()
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('status', 'unauthorized');
    }

    public function test_authenticated_user_can_read_own_profile()
    {
        $user = User::factory()->create(['role' => 'docent']);

        $this->actingAs($user)->getJson('/api/user')->assertOk();
    }

    public function test_authenticated_user_can_list_courses()
    {
        $user = User::factory()->create(['role' => 'docent']);

        $this->actingAs($user)->getJson('/api/courses')->assertOk();
    }
}
