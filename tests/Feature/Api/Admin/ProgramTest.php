<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Program;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProgramTest extends TestCase
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
        $admin = User::find($admin->id); // ensure Authenticatable instance
        return auth('api')->login($admin);
    }

    public function test_admin_can_view_programs()
    {
        $token = $this->getAdminToken();
        Program::factory()->count(3)->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/programs');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_program_details()
    {
        $token = $this->getAdminToken();
        $program = Program::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/programs/' . $program->id);
        $response->assertStatus(200);
    }

    public function test_admin_can_create_program()
    {
        $token = $this->getAdminToken();
        $category = Category::factory()->create();
        $payload = [
            'title' => 'Test Program',
            'description' => 'Short desc',
            'content' => 'Full content',
            'category_id' => $category->id,
            'image' => UploadedFile::fake()->image('program.jpg'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'target_amount' => 100000,
            'is_published' => true
        ];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/programs', $payload);
        $response->assertStatus(201)->assertJsonFragment(['title' => 'Test Program']);
    }

    public function test_admin_can_update_program()
    {
        $token = $this->getAdminToken();
        $program = Program::factory()->create(['title' => 'Old Title']);
        $payload = [
            'title' => 'New Title',
            'description' => 'Updated desc',
            'is_published' => false
        ];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/programs/' . $program->id, $payload);
        $response->assertStatus(200)->assertJsonFragment(['title' => 'New Title']);
    }

    public function test_admin_can_delete_program()
    {
        $token = $this->getAdminToken();
        $program = Program::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/programs/' . $program->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }

    public function test_admin_cannot_create_program_with_invalid_data()
    {
        $token = $this->getAdminToken();
        $payload = [
            'title' => '', // required
            'description' => '', // required
            'content' => '', // required
            'category_id' => 99999, // not exists
            'image' => null,
            'start_date' => '',
            'target_amount' => -100 // invalid
        ];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/programs', $payload);
        $response->assertStatus(422);
    }

    public function test_admin_cannot_update_nonexistent_program()
    {
        $token = $this->getAdminToken();
        $payload = ['title' => 'Should Fail'];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/programs/99999', $payload);
        $response->assertStatus(404);
    }

    public function test_forbidden_for_non_admin_user()
    {
        $user = User::factory()->create(['role' => 'user']);
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/programs');
        $response->assertStatus(403);
    }
}
