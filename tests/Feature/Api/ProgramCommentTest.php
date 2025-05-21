<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Program;
use App\Models\ProgramComment;

class ProgramCommentTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = env('API_KEY', 'default_key');
    }

    public function test_user_can_post_comment_on_program()
    {
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $payload = [
            'comment' => 'This is a test comment'
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/programs/{$program->id}/comments", $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'comment',
                    'user',
                ]
            ]);

        $this->assertDatabaseHas('program_comments', [
            'user_id' => $user->id,
            'program_id' => $program->id,
            'comment' => 'This is a test comment'
        ]);
    }

    public function test_user_comment_fail_with_invalid_data()
    {
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $payload = [
            'comment' => ''
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/programs/{$program->id}/comments", $payload);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed',
                'errors' => [
                    'comment' => ['The comment field is required.']
                ]
            ]);
    }

    public function test_user_can_update_comment_on_program()
    {
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $comment = ProgramComment::create([
            'program_id' => $program->id,
            'user_id' => $user->id,
            'comment' => 'Old comment'
        ]);

        $payload = [
            'comment' => 'Updated comment'
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/program-comments/{$comment->id}", $payload);
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Comment updated successfully',
                'data' => [
                    'id' => $comment->id,
                    'comment' => 'Updated comment'
                ]
            ]);

        $this->assertDatabaseHas('program_comments', [
            'id' => $comment->id,
            'comment' => 'Updated comment'
        ]);
    }

    public function test_user_can_update_comment_fail_with_invalid_data()
    {
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $comment = ProgramComment::create([
            'program_id' => $program->id,
            'user_id' => $user->id,
            'comment' => 'Old comment'
        ]);

        $payload = [
            'comment' => ''
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/program-comments/{$comment->id}", $payload);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed',
                'errors' => [
                    'comment' => ['The comment field is required.']
                ]
            ]);
    }

    public function test_user_can_delete_comment_on_program()
    {
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $comment = ProgramComment::create([
            'program_id' => $program->id,
            'user_id' => $user->id,
            'comment' => 'Comment to delete'
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/program-comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Comment deleted successfully'
            ]);

        $this->assertDatabaseMissing('program_comments', [
            'id' => $comment->id
        ]);
    }
}
