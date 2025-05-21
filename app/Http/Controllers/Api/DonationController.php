<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DonationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'program_id' => 'nullable|exists:programs,id',
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'required|email|max:255',
            'donor_phone' => 'nullable|string|max:20',
            'donor_address' => 'nullable|string',
            'amount' => 'required|numeric|min:10000',
            'message' => 'nullable|string|max:500',
            'is_anonymous' => 'boolean',
            'user_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['invoice_number'] = Donation::generateInvoiceNumber();
        $data['payment_status'] = 'pending';

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
            if (!($data['is_anonymous'] ?? false)) {
                $data['donor_name'] = $request->user()->name;
                $data['donor_email'] = $request->user()->email;
            }
        }

        $data['is_anonymous'] = $data['is_anonymous'] ?? true;

        $donation = Donation::create($data);

        try {
            $midtrans = app('midtrans');

            $params = [
                'transaction_details' => [
                    'order_id' => $donation->invoice_number,
                    'gross_amount' => $donation->amount,
                ],
                'customer_details' => [
                    'first_name' => $donation->donor_name,
                    'email' => $donation->donor_email,
                    'phone' => $donation->donor_phone,
                ],
                'item_details' => [
                    [
                        'id' => $donation->program_id ?: 'general-donation',
                        'price' => $donation->amount,
                        'quantity' => 1,
                        'name' => $donation->program ?
                            'Donasi untuk Program: ' . $donation->program->title :
                            'Donasi Umum'
                    ]
                ],
                'callbacks' => [
                    'finish' => "http://localhost:8000/",
                ]
            ];

            $paymentUrl = $midtrans->createTransaction($params);

            $donation->update([
                'midtrans_order_id' => $params['transaction_details']['order_id'],
                'midtrans_response_json' => $paymentUrl
            ]);

            return response()->json([
                'message' => 'Donation created successfully',
                'data' => [
                    'donation' => $donation,
                    'payment_url' => $paymentUrl
                ]
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Payment gateway error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function status($invoice_number)
    {
        $donation = Donation::where('invoice_number', $invoice_number)->first();

        if (!$donation) {
            return response()->json([
                'message' => 'Donation not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => [
                'invoice_number' => $donation->invoice_number,
                'amount' => $donation->amount,
                'payment_status' => $donation->payment_status,
                'payment_method' => $donation->payment_method,
                'created_at' => $donation->created_at,
                'program' => $donation->program,
                'is_anonymous' => $donation->is_anonymous,
                'donor_name' => $donation->donor_name,
            ]
        ]);
    }

    public function handleNotification(Request $request)
    {
        $orderId = $request->input('order_id');
        $status = strtolower($request->input('transaction_status'));
        $fraudStatus = strtolower($request->input('fraud_status', ''));
        $paymentType = $request->input('payment_type', null);
        $transactionId = $request->input('transaction_id', null);

        $donation = Donation::where('invoice_number', $orderId)->first();

        if (!$donation) {
            return response()->json(['message' => 'Donation not found'], 404);
        }

        if ($status === 'capture') {
            if ($fraudStatus === 'challenge') {
                $donation->payment_status = 'challenge';
            } elseif ($fraudStatus === 'accept') {
                $donation->payment_status = 'success';
            }
        } elseif ($status === 'settlement') {
            $donation->payment_status = 'success';
        } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            $donation->payment_status = 'failed';
        }

        $donation->update([
            'payment_status' => $donation->payment_status,
            'payment_method' => $paymentType,
            'midtrans_transaction_id' => $transactionId,
            'midtrans_transaction_status' => $status,
            'midtrans_payment_type' => $paymentType,
            'midtrans_response_json' => $request->all()
        ]);

        return response()->json(['message' => 'Notification handled successfully']);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $donations = Donation::where('user_id', $user->id)
            ->with(['program:id,title,image'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Donation history retrieved',
            'data' => $donations
        ]);
    }
}
