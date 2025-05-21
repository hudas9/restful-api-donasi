<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Donation;
use Illuminate\Support\Facades\Validator;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $donations = Donation::query()
            ->with(['program:id,title', 'user:id,name'])
            ->when($request->program, function ($query) use ($request) {
                $query->where('program_id', $request->program);
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('payment_status', $request->status);
            })
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('invoice_number', 'like', '%' . $request->search . '%')
                        ->orWhere('donor_name', 'like', '%' . $request->search . '%')
                        ->orWhere('donor_email', 'like', '%' . $request->search . '%');
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Donations retrieved successfully',
            'data' => $donations
        ]);
    }

    public function show($id)
    {
        $donation = Donation::with(['program', 'user'])->findOrFail($id);

        if (!$donation) {
            return response()->json([
                'message' => 'Donation not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Donation retrieved successfully',
            'data' => $donation
        ]);
    }

    public function update(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);

        if (!$donation) {
            return response()->json([
                'message' => 'Donation not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_status' => 'sometimes|in:pending,success,failed,challenge',
            'amount' => 'sometimes|numeric|min:10000',
            'message' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $donation->update($validator->validated());

        return response()->json([
            'message' => 'Donation updated successfully',
            'data' => $donation
        ]);
    }

    public function destroy($id)
    {
        $donation = Donation::findOrFail($id);

        if (!$donation) {
            return response()->json([
                'message' => 'Donation not found',
            ], 404);
        }

        $donation->delete();

        return response()->json([
            'message' => 'Donation deleted successfully'
        ]);
    }
}
