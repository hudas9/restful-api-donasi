<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Program;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Helpers\SlugHelper;
use Illuminate\Support\Facades\Storage;

class ProgramController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Program::class);

        $programs = Program::query()
            ->when($request->category, function ($query) use ($request) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            })
            ->with(['category', 'user'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Programs retrieved successfully',
            'data' => $programs
        ]);
    }

    public function show($id)
    {
        $program = Program::with(['category', 'user'])->findOrFail($id);

        if (!$program) {
            return response()->json([
                'message' => 'Program not found',
            ], 404);
        }

        $this->authorize('view', $program);

        return response()->json([
            'message' => 'Program retrieved successfully',
            'data' => $program
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Program::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_amount' => 'required|numeric|min:0',
            'is_published' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = $request->user()->id;
        $data['slug'] = SlugHelper::generateUniqueSlug($data['title']);
        $data['is_published'] = $data['is_published'] ?? false;
        $data['start_date'] = $data['start_date'] ?? now();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('programs', $image->hashName());
            $data['image'] = $image->hashName();
        }

        $program = Program::create($data);

        return response()->json([
            'message' => 'Program created successfully',
            'data' => $program
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        if (!$program) {
            return response()->json([
                'message' => 'Program not found',
            ], 404);
        }

        $this->authorize('update', $program);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:500',
            'content' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_amount' => 'sometimes|numeric|min:0',
            'is_published' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['title'])) {
            $data['slug'] = SlugHelper::generateUniqueSlug($data['title'], $program->id);
        }

        if ($request->hasFile('image')) {
            if ($program->image) {
                Storage::delete('public/programs/' . $program->image);
            }

            $image = $request->file('image');
            $imageName = time() . '_' . $image->hashName();
            $image->storeAs('public/programs', $imageName);
            $data['image'] = $imageName;
        }

        $program->update($data);

        return response()->json([
            'message' => 'Program updated successfully',
            'data' => $program
        ]);
    }

    public function destroy($id)
    {
        $program = Program::findOrFail($id);

        if (!$program) {
            return response()->json([
                'message' => 'Program not found',
            ], 404);
        }

        $this->authorize('delete', $program);

        if ($program->image) {
            Storage::delete('public/programs/' . $program->image);
        }

        $program->delete();

        return response()->json([
            'message' => 'Program deleted successfully'
        ]);
    }
}
