<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Donation;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    protected function getUserToken()
    {
        $user = User::factory()->create();
        $user = User::find($user->id);

        return auth('api')->login($user);
    }

    public function test_guest_can_make_donation()
    {
        $program = Program::factory()->create();
        $payload = [
            'program_id' => $program->id,
            'donor_name' => 'Guest',
            'donor_email' => 'guest@example.com',
            'donor_phone' => '08123456789',
            'donor_address' => 'Jl. Contoh No. 1',
            'amount' => 10000,
            'message' => 'Semoga bermanfaat',
            'is_anonymous' => true,
        ];

        $this->app->instance('midtrans', new class {
            public function createTransaction($params)
            {
                return 'http://payment.url?amount=' . ($params['transaction_details']['gross_amount'] ?? '0');
            }
        });
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->postJson('/api/donations', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'donation',
                    'payment_url'
                ]
            ]);
        $this->assertDatabaseHas('donations', [
            'donor_email' => 'guest@example.com',
            'amount' => 10000
        ]);
    }

    public function test_user_can_make_donation()
    {
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        $program = Program::factory()->create();
        $payload = [
            'program_id' => $program->id,
            'donor_name' => 'User',
            'donor_email' => 'user@example.com',
            'donor_phone' => '08123456789',
            'donor_address' => 'Jl. Contoh No. 1',
            'amount' => 10000,
            'message' => 'Semoga bermanfaat',
            'is_anonymous' => false,
            'user_id' => $user->id,
        ];

        $this->app->instance('midtrans', new class {
            public function createTransaction($params)
            {
                return 'http://payment.url';
            }
        });

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/donations', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'donation',
                    'payment_url'
                ]
            ]);
        $this->assertDatabaseHas('donations', [
            'donor_email' => $user->email,
            'amount' => 10000
        ]);
    }

    public function test_donation_fail_with_invalid_data()
    {
        $payload = [
            'program_id' => 9999,
            'donor_name' => '',
            'donor_email' => 'invalid-email',
            'amount' => 9000,
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->postJson('/api/donations', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'program_id',
                'donor_name',
                'donor_email',
                'amount'
            ]);
    }

    public function test_guest_can_view_donation_status()
    {
        $program = Program::factory()->create();
        $donation = Donation::factory()->create([
            'payment_status' => 'pending',
            'is_anonymous' => false,
            'program_id' => $program->id,
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/donations/status/' . $donation->invoice_number);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'invoice_number',
                    'amount',
                    'payment_status',
                    'payment_method',
                    'created_at',
                    'program',
                    'is_anonymous',
                    'donor_name'
                ]
            ]);
    }

    public function test_guest_cannot_view_donation_status_with_invalid_invoice_number()
    {
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/donations/status/invalid-invoice-number');
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Donation not found'
            ]);
    }

    public function test_user_can_view_donation_status()
    {
        $program = Program::factory()->create();
        $token = $this->getUserToken();
        $donation = Donation::factory()->create([
            'payment_status' => 'pending',
            'is_anonymous' => false,
            'program_id' => $program->id,
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/donations/status/' . $donation->invoice_number);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'invoice_number',
                    'amount',
                    'payment_status',
                    'payment_method',
                    'created_at',
                    'program',
                    'is_anonymous',
                    'donor_name'
                ]
            ]);
    }

    public function test_user_cannot_view_donation_status_with_invalid_invoice_number()
    {
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/donations/status/invalid-invoice-number');
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Donation not found'
            ]);
    }

    public function test_user_can_view_donation_history()
    {
        $user = User::factory()->create();
        $token = $this->getUserToken();
        $program = Program::factory()->create();
        Donation::factory()->count(2)->create([
            'user_id' => $user->id,
            'program_id' => $program->id,
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/donations/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data'
                ]
            ]);
    }
}
