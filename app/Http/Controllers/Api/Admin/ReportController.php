<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Helpers\SlugHelper;
use App\Models\DocumentationReport;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $reports = Report::query()
            ->when($request->category, fn($q) => $q->whereHas('category', fn($q) => $q->where('slug', $request->category)))
            ->when($request->program, fn($q) => $q->whereHas('program', fn($q) => $q->where('slug', $request->program)))
            ->with(['category', 'user', 'program', 'documentations'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Reports retrieved successfully',
            'data' => $reports
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Report::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'summary' => 'required|string|max:500',
            'content' => 'required|string',
            'program_id' => 'required|exists:programs,id',
            'category_id' => 'required|exists:categories,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'total_funds_used' => 'required|numeric|min:0',
            'report_date' => 'required|date',
            'beneficiaries' => 'nullable|array',
            'beneficiaries.*.name' => 'required|string',
            'beneficiaries.*.amount' => 'required|numeric',
            'documentations' => 'nullable|array',
            'documentations.*.file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:5120',
            'documentations.*.caption' => 'nullable|string|max:255',
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
        $data['slug'] = SlugHelper::generateUniqueSlug($request->title);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('reports', $image->hashName());
            $data['image'] = $image->hashName();
        }

        $report = Report::create($data);

        if ($request->has('documentations')) {
            foreach ($request->documentations as $doc) {
                $file = $doc['file'];
                $file->storeAs('documentations', $file->hashName());

                DocumentationReport::create([
                    'report_id' => $report->id,
                    'file_path' => $file->hashName(),
                    'file_type' => in_array($file->getClientOriginalExtension(), ['mp4', 'mov', 'avi']) ? 'video' : 'image',
                    'caption' => $doc['caption'] ?? null,
                    'order' => DocumentationReport::where('report_id', $report->id)->count()
                ]);
            }
        }

        return response()->json([
            'message' => 'Report created successfully',
            'data' => $report->load('documentations')
        ], 201);
    }

    public function show($id)
    {
        $report = Report::with(['category', 'user', 'program', 'documentations'])->findOrFail($id);

        if (!$report) {
            return response()->json([
                'message' => 'Report not found',
            ], 404);
        }

        $this->authorize('view', $report);

        return response()->json([
            'message' => 'Report retrieved successfully',
            'data' => $report
        ]);
    }

    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        if (!$report) {
            return response()->json([
                'message' => 'Report not found',
            ], 404);
        }

        $this->authorize('update', $report);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'summary' => 'sometimes|string|max:500',
            'content' => 'sometimes|string',
            'program_id' => 'sometimes|exists:programs,id',
            'category_id' => 'sometimes|exists:categories,id',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'total_funds_used' => 'sometimes|numeric|min:0',
            'report_date' => 'sometimes|date',
            'beneficiaries' => 'nullable|array',
            'beneficiaries.*.name' => 'required|string',
            'beneficiaries.*.amount' => 'required|numeric',
            'documentations' => 'nullable|array',
            'documentations.*.id' => 'sometimes|exists:documentation_reports,id',
            'documentations.*.file' => 'sometimes|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:5120',
            'documentations.*.caption' => 'nullable|string|max:255',
            'documentations.*.order' => 'sometimes|integer',
            'deleted_documentations' => 'nullable|array',
            'deleted_documentations.*' => 'exists:documentation_reports,id',
            'is_published' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($report->image) {
                Storage::delete('reports/' . $report->image);
            }

            $image = $request->file('image');
            $image->storeAs('reports', $image->hashName());
            $data['image'] = $image->hashName();
        }

        $report->update($data);

        if ($request->has('deleted_documentations')) {
            $docsToDelete = DocumentationReport::whereIn('id', $request->deleted_documentations)
                ->where('report_id', $report->id)
                ->get();

            foreach ($docsToDelete as $doc) {
                Storage::delete('documentations/' . $doc->file_path);
                $doc->delete();
            }
        }

        if ($request->has('documentations')) {
            foreach ($request->documentations as $doc) {
                if (isset($doc['id'])) {
                    $existingDoc = DocumentationReport::find($doc['id']);
                    if ($existingDoc) {
                        $updateData = [
                            'caption' => $doc['caption'] ?? $existingDoc->caption,
                            'order' => $doc['order'] ?? $existingDoc->order
                        ];

                        if (isset($doc['file'])) {
                            Storage::delete('documentations/' . $existingDoc->file_path);

                            $file = $doc['file'];
                            $file->storeAs('documentations', $file);

                            $updateData['file_path'] = $file->hashName();
                            $updateData['file_type'] = in_array($file->getClientOriginalExtension(), ['mp4', 'mov', 'avi']) ? 'video' : 'image';
                        }

                        $existingDoc->update($updateData);
                    }
                } else {
                    $file = $doc['file'];
                    $file->storeAs('documentations', $file->hashName());

                    DocumentationReport::create([
                        'report_id' => $report->id,
                        'file_path' => $file->hashName(),
                        'file_type' => in_array($file->getClientOriginalExtension(), ['mp4', 'mov', 'avi']) ? 'video' : 'image',
                        'caption' => $doc['caption'] ?? null,
                        'order' => $doc['order'] ?? DocumentationReport::where('report_id', $report->id)->count()
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Report updated successfully',
            'data' => $report->load('documentations')
        ]);
    }

    public function destroy($id)
    {
        $report = Report::findOrFail($id);

        if (!$report) {
            return response()->json([
                'message' => 'Report not found',
            ], 404);
        }

        $this->authorize('delete', $report);

        foreach ($report->documentations as $doc) {
            Storage::delete('documentations/' . $doc->file_path);
            $doc->delete();
        }

        if ($report->image) {
            Storage::delete('reports/' . $report->image);
        }

        $report->delete();

        return response()->json([
            'message' => 'Report deleted successfully'
        ]);
    }
}
