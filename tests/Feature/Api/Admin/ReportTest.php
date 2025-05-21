<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Report;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReportTest extends TestCase
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

    public function test_admin_can_view_reports()
    {
        $token = $this->getAdminToken();
        Report::factory()->count(3)->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reports');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_report_details()
    {
        $token = $this->getAdminToken();
        $report = Report::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reports/' . $report->id);
        $response->assertStatus(200);
    }

    public function test_admin_can_create_report()
    {
        $token = $this->getAdminToken();
        $category = Category::factory()->create();
        $program = \App\Models\Program::factory()->create();
        $payload = [
            'title' => 'Test Report',
            'description' => 'Short desc',
            'content' => 'Full content',
            'category_id' => $category->id,
            'summary' => 'Summary of the report',
            'program_id' => $program->id,
            'total_funds_used' => 10000,
            'report_date' => now()->toDateString(),
            'image' => UploadedFile::fake()->image('report.jpg'),
            'is_published' => true
        ];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reports', $payload);
        $response->assertStatus(201)->assertJsonFragment(['title' => 'Test Report']);
    }

    public function test_admin_can_update_report()
    {
        $token = $this->getAdminToken();
        $report = Report::factory()->create(['title' => 'Old Title']);
        $payload = [
            'title' => 'New Title',
            'description' => 'Updated desc',
            'is_published' => false
        ];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/reports/' . $report->id, $payload);
        $response->assertStatus(200)->assertJsonFragment(['title' => 'New Title']);
    }

    public function test_admin_can_delete_report()
    {
        $token = $this->getAdminToken();
        $report = Report::factory()->create();
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/reports/' . $report->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    public function test_admin_cannot_create_report_with_invalid_data()
    {
        $token = $this->getAdminToken();
        $payload = [
            'title' => '',
            'description' => '', // required
            'content' => '', // required
            'category_id' => 99999, // not exists
            'image' => null
        ];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/reports', $payload);
        $response->assertStatus(422);
    }

    public function test_admin_cannot_update_nonexistent_report()
    {
        $token = $this->getAdminToken();
        $payload = ['title' => 'Should Fail'];
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/reports/99999', $payload);
        $response->assertStatus(404);
    }

    public function test_forbidden_for_non_admin_user()
    {
        $user = User::factory()->create(['role' => 'user']);
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reports');
        $response->assertStatus(403);
    }
}
