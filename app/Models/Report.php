<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'program_id',
        'category_id',
        'image',
        'total_funds_used',
        'report_date',
        'beneficiaries',
        'is_published',
        'user_id'
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_funds_used' => 'decimal:2',
        'beneficiaries' => 'array',
        'is_published' => 'boolean'
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReportComment::class);
    }

    public function documentations(): HasMany
    {
        return $this->hasMany(DocumentationReport::class)->orderBy('order');
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? asset('storage/reports/' . $value) : null,
        );
    }
}
