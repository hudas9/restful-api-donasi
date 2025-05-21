<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Report;
use App\Models\Category;
use App\Models\User;
use App\Models\Program;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = env('API_KEY', 'default_key');
    }

    public function test_guest_can_view_reports()
    {
        $category = Category::factory()->create();
        $program = Program::factory()->create();
        Report::factory()->count(3)->for($category)->for($program)->create(['is_published' => true]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'slug', 'category', 'user', 'program', 'documentations', 'comments_count']
                    ]
                ]
            ]);
    }

    public function test_guest_can_view_report_details()
    {
        $category = Category::factory()->create();
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $report = Report::factory()->for($category)->for($program)->for($user)->create(['is_published' => true]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/reports/' . $report->slug);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'category',
                    'user',
                    'program',
                    'comments',
                    'documentations'
                ]
            ]);
    }

    public function test_guest_can_view_reports_by_category()
    {
        $category = Category::factory()->create();
        $program = Program::factory()->create();
        Report::factory()->count(2)->for($category)->for($program)->create(['is_published' => true]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/reports/category/' . $category->slug);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'slug', 'category', 'user']
                    ]
                ]
            ]);
    }

    public function test_user_can_view_reports()
    {
        $category = Category::factory()->create();
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        Report::factory()->count(3)->for($category)->for($program)->create(['is_published' => true]);

        $response = $this->withHeader('API_KEY', $this->apiKey)->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'slug', 'category', 'user', 'program', 'documentations', 'comments_count']
                    ]
                ]
            ]);
    }

    public function test_user_can_view_report_details()
    {
        $category = Category::factory()->create();
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        $report = Report::factory()->for($category)->for($program)->for($user)->create(['is_published' => true]);

        $response = $this->withHeader('API_KEY', $this->apiKey)->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reports/' . $report->slug);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'category',
                    'user',
                    'program',
                    'comments',
                    'documentations'
                ]
            ]);
    }

    public function test_user_can_view_reports_by_category()
    {
        $category = Category::factory()->create();
        $program = Program::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        Report::factory()->count(2)->for($category)->for($program)->create(['is_published' => true]);

        $response = $this->withHeader('API_KEY', $this->apiKey)->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reports/category/' . $category->slug);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'slug', 'category', 'user']
                    ]
                ]
            ]);
    }
}
