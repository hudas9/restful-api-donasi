<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class CategoryTest extends TestCase
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

    public function test_guest_can_view_categories()
    {
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])
            ->assertJson([
                'message' => 'Categories retrieved successfully',
            ]);
    }

    public function test_user_can_view_categories()
    {
        $token = $this->getUserToken();

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])
            ->assertJson([
                'message' => 'Categories retrieved successfully',
            ]);
    }
}
