<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_admin_can_view_categories()
    {
        $token = $this->getAdminToken();
        Category::factory()->count(3)->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/categories');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_category_details()
    {
        $token = $this->getAdminToken();
        $category = Category::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/categories/' . $category->id);
        $response->assertStatus(200);
    }

    public function test_admin_can_create_category()
    {
        $token = $this->getAdminToken();
        $payload = [
            'name' => 'Zakat'
        ];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/categories', $payload);
        $response->assertStatus(201)->assertJsonFragment(['name' => 'Zakat']);
    }

    public function test_admin_can_update_category()
    {
        $token = $this->getAdminToken();
        $category = Category::factory()->create(['name' => 'Lama']);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/categories/' . $category->id, [
                'name' => 'Baru'
            ]);
        $response->assertStatus(200)->assertJsonFragment(['name' => 'Baru']);
    }

    public function test_admin_can_delete_category()
    {
        $token = $this->getAdminToken();
        $category = Category::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/categories/' . $category->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
