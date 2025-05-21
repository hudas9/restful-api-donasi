<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use App\Models\Donation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DonationTest extends TestCase
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

    public function test_admin_can_view_donations()
    {
        $token = $this->getAdminToken();
        Donation::factory()->count(3)->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/donations');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_donation_details()
    {
        $token = $this->getAdminToken();
        $donation = Donation::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/donations/' . $donation->id);
        $response->assertStatus(200);
    }

    public function test_admin_can_update_donation()
    {
        $token = $this->getAdminToken();
        $donation = Donation::factory()->create(['payment_status' => 'pending']);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/donations/' . $donation->id, [
                'payment_status' => 'success'
            ]);
        $response->assertStatus(200)->assertJsonFragment(['payment_status' => 'success']);
    }

    public function test_admin_can_delete_donation()
    {
        $token = $this->getAdminToken();
        $donation = Donation::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/donations/' . $donation->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('donations', ['id' => $donation->id]);
    }
}
