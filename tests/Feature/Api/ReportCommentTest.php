<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Report;
use App\Models\ReportComment;

class ReportCommentTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = env('API_KEY', 'default_key');
    }

    public function test_user_can_post_comment_on_report()
    {
        $report = Report::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $payload = [
            'comment' => 'This is a test comment'
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/reports/{$report->id}/comments", $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'comment',
                    'user',
                ]
            ]);

        $this->assertDatabaseHas('report_comments', [
            'user_id' => $user->id,
            'report_id' => $report->id,
            'comment' => 'This is a test comment'
        ]);
    }

    public function test_user_comment_fail_with_invalid_data()
    {
        $report = Report::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $payload = [
            'comment' => ''
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/reports/{$report->id}/comments", $payload);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed',
                'errors' => [
                    'comment' => ['The comment field is required.']
                ]
            ]);
    }

    public function test_user_can_update_comment_on_report()
    {
        $report = Report::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $comment = ReportComment::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
            'comment' => 'Old comment'
        ]);

        $payload = [
            'comment' => 'Updated comment'
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/report-comments/{$comment->id}", $payload);
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Comment updated successfully',
                'data' => [
                    'id' => $comment->id,
                    'comment' => 'Updated comment'
                ]
            ]);

        $this->assertDatabaseHas('report_comments', [
            'id' => $comment->id,
            'comment' => 'Updated comment'
        ]);
    }

    public function test_user_can_update_comment_fail_with_invalid_data()
    {
        $report = Report::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $comment = reportComment::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
            'comment' => 'Old comment'
        ]);

        $payload = [
            'comment' => ''
        ];

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/report-comments/{$comment->id}", $payload);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed',
                'errors' => [
                    'comment' => ['The comment field is required.']
                ]
            ]);
    }

    public function test_user_can_delete_comment_on_report()
    {
        $report = Report::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $comment = ReportComment::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
            'comment' => 'Comment to delete'
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/report-comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Comment deleted successfully'
            ]);

        $this->assertDatabaseMissing('report_comments', [
            'id' => $comment->id
        ]);
    }
}
