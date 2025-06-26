<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $programs = Program::query()
            ->when($request->category, function ($query) use ($request) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            })
            ->where('is_published', true)
            ->with(['category', 'user'])
            ->withCount('comments');

        return response()->json([
            'message' => 'Programs retrieved successfully',
            'data' => $programs
        ]);
    }

    public function show($slug)
    {
        $program = Program::with(['category', 'user', 'comments.user'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (!$program) {
            return response()->json([
                'message' => 'Program not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Program retrieved successfully',
            'data' => $program
        ]);
    }

    public function showByCategory($categorySlug)
    {
        $programs = Program::with(['category', 'user'])
            ->whereHas('category', function ($query) use ($categorySlug) {
                $query->where('slug', $categorySlug);
            })
            ->where('is_published', true);

        return response()->json([
            'message' => 'Programs retrieved successfully',
            'data' => $programs
        ]);
    }
}
