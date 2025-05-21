<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = env('API_KEY', 'default_key');
    }

    public function test_user_can_register_successfully()
    {
        $response = $this->withHeader('API_KEY', $this->apiKey)->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['user' => ['id', 'name', 'email']]])
            ->assertJson([
                'message' => 'User registered successfully. Please check your email for verification.',
                'data' => [
                    'user' => [
                        'name' => 'Test User',
                        'email' => 'testuser@example.com'
                    ]
                ]
            ]);
        $this->assertDatabaseHas('users', ['email' => 'testuser@example.com']);
    }

    public function test_register_fails_with_invalid_data()
    {
        $response = $this->withHeader('API_KEY', $this->apiKey)->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'notmatching'
        ]);
        $response->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    }

    public function test_user_can_verify_email_with_valid_link()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $id = $user->getKey();
        $hash = sha1($user->getEmailForVerification());

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson("/api/auth/email/verify/{$id}/{$hash}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email verified successfully']);

        $this->assertNotNull($user->fresh()->email_verified_at);

        $response2 = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson("/api/auth/email/verify/{$id}/{$hash}");

        $response2->assertStatus(200)
            ->assertJson(['message' => 'Email already verified']);
    }

    public function test_user_cannot_verify_email_with_invalid_link()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $id = $user->getKey();
        $hash = 'invalidhash';

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson("/api/auth/email/verify/{$id}/{$hash}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid verification link']);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => bcrypt('Password123!'),
        ]);
        $response = $this->withHeader('API_KEY', $this->apiKey)->postJson('/api/auth/login', [
            'email' => 'loginuser@example.com',
            'password' => 'Password123!'
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                ]
            ])
            ->assertJson([
                'message' => 'Login successful',
                'data' => [
                    'token_type' => 'Bearer',
                ]
            ]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->withHeader('API_KEY', $this->apiKey)->postJson('/api/auth/login', [
            'email' => 'notfound@example.com',
            'password' => 'wrongpassword'
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid email or password'
            ]);
    }

    public function test_authenticated_user_can_view_profile()
    {
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/profile');
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User profile retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'image' => $user->image,
                        'email_verified_at' => optional($user->email_verified_at)->toJSON(),
                    ]
                ]
            ]);
    }

    public function test_user_can_update_profile_with_valid_data()
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword123!')
        ]);
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/profile', [
                'name' => 'Updated Name'
            ]);
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'name' => 'Updated Name',
                        'email' => $user->email,
                    ]
                ]
            ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name'
        ]);

        $response2 = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/profile', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!'
            ]);
        $response2->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully'
            ]);
    }

    public function test_profile_update_fails_with_invalid_data()
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword123!')
        ]);
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/profile', [
                'name' => '',
                'email' => 'not-an-email',
                'image' => 'not-an-image',
                'current_password' => 'wrongpassword',
                'password' => 'newpassword',
                'password_confirmation' => 'differentpassword'
            ]);
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);

        $response2 = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/user/profile', [
                'current_password' => 'wrongpassword',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!'
            ]);
        $response2->assertStatus(401)
            ->assertJson(['message' => 'Current password is incorrect']);
    }

    public function test_authenticated_user_can_logout_successfully()
    {
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);
    }


    public function test_authenticated_user_can_resend_verification_email_if_not_verified()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/email/verify/resend');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Verification email sent successfully']);
    }

    public function test_authenticated_user_cannot_resend_verification_email_if_already_verified()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user = User::find($user->id);
        $token = auth('api')->login($user);

        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/email/verify/resend');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email already verified']);
    }

    public function test_unauthenticated_user_with_no_authorization_token()
    {
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->getJson('/api/user/profile');
        $response->assertStatus(401)
            ->assertJson(['message' => 'Authorization Token not found']);
    }

    public function test_unauthenticated_user_with_invalid_authorization_token()
    {
        $invalidToken = 'invalid.token.value';
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $invalidToken)
            ->getJson('/api/user/profile');
        $response->assertStatus(401)
            ->assertJson(['message' => 'Token is Invalid']);
    }

    public function test_unauthenticated_user_with_expired_authorization_token()
    {
        JWTAuth::shouldReceive('parseToken')->andThrow(TokenExpiredException::class);
        $user = User::factory()->create();
        $user = User::find($user->id);
        $token = auth('api')->login($user);
        $response = $this->withHeader('API_KEY', $this->apiKey)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/profile');
        $response->assertStatus(401)
            ->assertJson(['message' => 'Token is Expired']);
    }
}
