<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::query()
            ->when($request->category, fn($q) => $q->whereHas('category', fn($q) => $q->where('slug', $request->category)))
            ->when($request->program, fn($q) => $q->whereHas('program', fn($q) => $q->where('slug', $request->program)))
            ->with(['category', 'user', 'program', 'documentations'])
            ->where('is_published', true)
            ->withCount('comments');

        return response()->json([
            'message' => 'Reports retrieved successfully',
            'data' => $reports
        ]);
    }

    public function show($slug)
    {
        $report = Report::with([
            'category',
            'user',
            'program',
            'comments.user',
            'documentations',
        ])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json([
            'message' => 'Report retrieved successfully',
            'data' => $report
        ]);
    }

    public function showByCategory($categorySlug)
    {
        $reports = Report::with(['category', 'user'])
            ->whereHas('category', fn($query) => $query->where('slug', $categorySlug))
            ->where('is_published', true);

        return response()->json([
            'message' => 'Reports retrieved successfully',
            'data' => $reports
        ]);
    }
}
