<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DocumentationReport extends Model
{
    protected $fillable = [
        'report_id',
        'file_path',
        'file_type',
        'caption',
        'order'
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => asset('storage/documentations/' . $this->file_path)
        );
    }
}
