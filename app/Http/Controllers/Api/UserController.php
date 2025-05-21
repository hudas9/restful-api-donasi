<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'message' => 'User profile retrieved successfully',
            'data' => [
                'user' => $request->user()->only(['id', 'name', 'email', 'image', 'email_verified_at'])
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'current_password' => 'required_with:password',
            'password' => 'sometimes|string|min:8|confirmed|different:current_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 401);
            }
            $data['password'] = Hash::make($data['password']);
            unset($data['current_password']);
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
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'image', 'email_verified_at'])
            ]
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->image) {
            Storage::delete($user->image);
        }

        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}
