<?php

namespace Tests\Feature\Api\Admin;

use App\Models\ProgramComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Program;

class ProgramCommentTest extends TestCase
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

    public function test_admin_can_view_program_comments()
    {
        $token = $this->getAdminToken();
        $program = Program::factory()->create();
        ProgramComment::factory()->count(3)->create(['program_id' => $program->id]);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/program-comments');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_program_comment_details()
    {
        $token = $this->getAdminToken();
        $comment = ProgramComment::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/program-comments/' . $comment->id);
        $response->assertStatus(200);
    }

    public function test_admin_can_update_program_comment()
    {
        $token = $this->getAdminToken();
        $comment = ProgramComment::factory()->create(['comment' => 'Old Comment']);
        $payload = ['comment' => 'Updated Comment'];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/program-comments/' . $comment->id, $payload);
        $response->assertStatus(200)->assertJsonFragment(['comment' => 'Updated Comment']);
    }

    public function test_admin_can_delete_program_comment()
    {
        $token = $this->getAdminToken();
        $comment = ProgramComment::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/program-comments/' . $comment->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('program_comments', ['id' => $comment->id]);
    }

    public function test_admin_cannot_update_nonexistent_program_comment()
    {
        $token = $this->getAdminToken();
        $payload = ['comment' => 'Should Fail'];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/program-comments/99999', $payload);
        $response->assertStatus(404);
    }

    public function test_forbidden_for_non_admin_user()
    {
        $user = User::factory()->create(['role' => 'user']);
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/program-comments');
        $response->assertStatus(403);
    }
}
