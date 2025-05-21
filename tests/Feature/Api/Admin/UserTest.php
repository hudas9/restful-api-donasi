<?php

namespace Tests\Feature\Api\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = env('API_KEY', 'default_key');
    }

    protected function getAdminToken()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin = User::find($admin->id);
        return auth('api')->login($admin);
    }

    public function test_admin_can_view_users()
    {
        $token = $this->getAdminToken();
        User::factory()->count(3)->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/users');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_details()
    {
        $token = $this->getAdminToken();
        $user = User::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/users/' . $user->id);
        $response->assertStatus(200);
    }

    public function test_admin_can_create_user()
    {
        $token = $this->getAdminToken();
        $payload = [
            'name' => 'Admin Baru',
            'email' => 'adminbaru@example.com',
            'role' => 'admin',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/users', $payload);
        $response->assertStatus(201);
    }

    public function test_admin_can_update_user()
    {
        $token = $this->getAdminToken();
        $user = User::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/users/' . $user->id, [
                'name' => 'Updated Admin'
            ]);
        $response->assertStatus(200);
    }

    public function test_admin_can_delete_user()
    {
        $token = $this->getAdminToken();
        $user = User::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/users/' . $user->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_reset_user_password()
    {
        $token = $this->getAdminToken();
        $user = User::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/users/' . $user->id . '/reset-password', [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!'
            ]);
        $response->assertStatus(200);
    }

    public function test_admin_can_verify_email_user()
    {
        $token = $this->getAdminToken();
        $user = User::factory()->create(['email_verified_at' => null]);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/users/' . $user->id . '/verify-email');
        $response->assertStatus(200);
    }

    public function test_admin_can_resend_user_verification_email()
    {
        $token = $this->getAdminToken();
        $user = User::factory()->create(['email_verified_at' => null]);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/users/' . $user->id . '/send-verification-email');
        $response->assertStatus(200);
    }

    public function test_admin_can_send_user_reset_password_email()
    {
        $token = $this->getAdminToken();
        $user = User::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/users/' . $user->id . '/send-reset-password-email');
        $response->assertStatus(200);
    }
}
