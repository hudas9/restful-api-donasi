<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Program;
use App\Models\Category;
use App\Models\User;

class ProgramTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = env('API_KEY', 'default_key');
    }

    public function test_guest_can_view_programs()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        Program::factory()->count(2)->create([
            'is_published' => true,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
        Program::factory()->create(['is_published' => false]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/programs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'description',
                            'content',
                            'category_id',
                            'user_id',
                            'image',
                            'start_date',
                            'end_date',
                            'target_amount',
                            'collected_amount',
                            'is_published',
                            'created_at',
                            'updated_at',
                            'category',
                            'user',
                        ]
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ])
            ->assertJson([
                'message' => 'Programs retrieved successfully',
            ]);
    }


    public function test_guest_can_view_program_details()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $program = Program::factory()->create([
            'is_published' => true,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/programs/' . $program->slug);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'content',
                    'category_id',
                    'user_id',
                    'image',
                    'start_date',
                    'end_date',
                    'target_amount',
                    'collected_amount',
                    'is_published',
                    'created_at',
                    'updated_at',
                    'category',
                    'user',
                ]
            ])
            ->assertJson([
                'message' => 'Program retrieved successfully',
            ]);
    }
    public function test_guest_can_view_programs_by_category()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        Program::factory()->create([
            'is_published' => true,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
        Program::factory()->create([
            'is_published' => true,
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/programs/category/' . $category->slug);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'description',
                            'content',
                            'category_id',
                            'user_id',
                            'image',
                            'start_date',
                            'end_date',
                            'target_amount',
                            'collected_amount',
                            'is_published',
                            'created_at',
                            'updated_at',
                            'category',
                            'user',
                        ]
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ])
            ->assertJson([
                'message' => 'Programs retrieved successfully',
            ]);
    }

    public function test_user_can_view_programs()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        Program::factory()->count(2)->create([
            'is_published' => true,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
        Program::factory()->create(['is_published' => false]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/programs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'description',
                            'content',
                            'category_id',
                            'user_id',
                            'image',
                            'start_date',
                            'end_date',
                            'target_amount',
                            'collected_amount',
                            'is_published',
                            'created_at',
                            'updated_at',
                            'category',
                            'user',
                            'comments_count',
                        ]
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ])
            ->assertJson([
                'message' => 'Programs retrieved successfully',
            ]);
    }

    public function test_user_can_view_program_details()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        $program = Program::factory()->create([
            'is_published' => true,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/programs/' . $program->slug);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'content',
                    'category_id',
                    'user_id',
                    'image',
                    'start_date',
                    'end_date',
                    'target_amount',
                    'collected_amount',
                    'is_published',
                    'created_at',
                    'updated_at',
                    'category',
                    'user',
                    'comments',
                ]
            ])
            ->assertJson([
                'message' => 'Program retrieved successfully',
            ]);
    }

    public function test_user_can_view_programs_by_category()
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        Program::factory()->create([
            'is_published' => true,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
        Program::factory()->create([
            'is_published' => true,
        ]);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/programs/category/' . $category->slug);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'description',
                            'content',
                            'category_id',
                            'user_id',
                            'image',
                            'start_date',
                            'end_date',
                            'target_amount',
                            'collected_amount',
                            'is_published',
                            'created_at',
                            'updated_at',
                            'category',
                            'user',
                        ]
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ])
            ->assertJson([
                'message' => 'Programs retrieved successfully',
            ]);
    }
}
