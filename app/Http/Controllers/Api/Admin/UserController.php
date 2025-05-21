<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $users = User::all();

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => ['users' => $users]
        ]);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => ['user' => $user],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role' => 'required|in:admin,user',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $data['password'] = Hash::make($data['password']);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('users', $image->hashName());
            $data['image'] = $image->hashName();
        }

        $user = User::create($data);

        return response()->json([
            'message' => 'User created successfully',
            'data' => ['user' => $user],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role' => 'sometimes|in:admin,user',
            'password' => 'sometimes|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::delete('users/' . basename($user->image));
            }
            $image = $request->file('image');
            $image->storeAs('users', $image->hashName());
            $data['image'] = $image->hashName();
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => ['user' => $user],
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        if ($user->image) {
            Storage::delete('users/' . basename($user->image));
        }
        $user->delete();
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->password = Hash::make($request->password);

        $user->save();

        return response()->json([
            'message' => 'Password reset successfully',
            'data' => ['user' => $user],
        ]);
    }

    public function verifyEmail($id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified'
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message' => 'Email verified successfully'
        ]);
    }

    public function sendVerificationEmail($id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified'
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent successfully'
        ]);
    }

    public function sendResetPasswordEmail($id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $token = app('auth.password.broker')->createToken($user);

        $user->sendPasswordResetNotification($token);

        return response()->json([
            'message' => 'Reset password email sent successfully'
        ]);
    }
}
