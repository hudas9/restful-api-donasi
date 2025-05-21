<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ReportComment;

class ReportCommentController extends Controller
{
    public function index()
    {
        $comments = ReportComment::with(['report', 'user'])->get();

        return response()->json([
            'message' => 'Comments retrieved successfully',
            'data' => $comments
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:report_comments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $reportId = $request->report_id;

        $comment = ReportComment::create([
            'comment' => $request->comment,
            'report_id' => $reportId,
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment->load('user')
        ], 201);
    }

    public function show($id)
    {
        $comment = ReportComment::with(['report', 'user'])->findOrFail($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Comment retrieved successfully',
            'data' => $comment
        ]);
    }

    public function update(Request $request, $id)
    {
        $comment = ReportComment::findOrFail($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:report_comments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update([
            'comment' => $request->comment,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'data' => $comment
        ]);
    }

    public function destroy($id)
    {
        $comment = ReportComment::findOrFail($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully'
        ]);
    }
}
