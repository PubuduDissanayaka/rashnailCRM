<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExpenseAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'filename',
        'file_path',
        'file_type',
        'file_size',
        'attachment_type',
        'description',
        'uploaded_by',
    ];

    // Attachment type constants
    const TYPE_RECEIPT = 'receipt';
    const TYPE_INVOICE = 'invoice';
    const TYPE_CONTRACT = 'contract';
    const TYPE_OTHER = 'other';

    // Relationships
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Methods
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFormattedSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Boot method to delete file from storage on model deletion
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            // Delete the physical file from storage
            if ($attachment->file_path && Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }
        });
    }
}