<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'program_id',
        'user_id',
        'donor_name',
        'donor_email',
        'donor_phone',
        'donor_address',
        'amount',
        'message',
        'payment_method',
        'payment_status',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_transaction_status',
        'midtrans_payment_type',
        'midtrans_response_json',
        'is_anonymous'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'midtrans_response_json' => 'array',
        'is_anonymous' => 'boolean'
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateInvoiceNumber()
    {
        $prefix = 'DON' . date('YmdHis');
        $random = mt_rand(1000, 9999);
        return $prefix . $random;
    }
}
