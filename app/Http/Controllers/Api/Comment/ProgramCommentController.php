<?php

namespace App\Http\Controllers\Api\Comment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProgramComment;

class ProgramCommentController extends Controller
{
    public function store(Request $request, $programId)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:program_comments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = ProgramComment::create([
            'comment' => $request->comment,
            'program_id' => $programId,
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment->load('user')
        ], 201);
    }

    public function update(Request $request, $commentId)
    {

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = ProgramComment::findOrFail($commentId);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ], 404);
        }

        if ($request->user()->cannot('update', $comment)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->update(['comment' => $request->comment]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'data' => $comment->fresh('user')
        ]);
    }

    public function destroy(Request $request, $commentId)
    {
        $comment = ProgramComment::findOrFail($commentId);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ], 404);
        }

        if ($request->user()->cannot('delete', $comment)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully'
        ]);
    }
}
