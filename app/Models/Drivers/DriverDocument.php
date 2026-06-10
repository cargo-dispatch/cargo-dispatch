<?php

namespace App\Models\Drivers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DriverDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id', 'type', 'file_path', 'original_name',
        'file_size', 'mime_type', 'status',
        'verified_by', 'verified_at', 'rejection_reason', 'expires_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at'  => 'date',
    ];

    // Document type labels for display
    public static array $typeLabels = [
        'profile_photo'      => 'Profile Photo',
        'cdl_front'          => 'CDL (Front)',
        'cdl_back'           => 'CDL (Back)',
        'medical_card'       => 'Medical Card',
        'drug_test'          => 'Drug Test Result',
        'mvr_report'         => 'MVR Report',
        'proof_of_insurance' => 'Proof of Insurance',
        'w9_form'            => 'W-9 Form',
        'direct_deposit'     => 'Direct Deposit Form',
        'other'              => 'Other',
    ];

    // Required document types for onboarding approval
    public static array $requiredTypes = [
        'cdl_front',
        'medical_card',
        'drug_test',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getTypeLabelAttribute(): string
    {
        return static::$typeLabels[$this->type] ?? $this->type;
    }

    public function isExpiringSoon(): bool
    {
        return $this->expires_at && $this->expires_at->diffInDays(now()) <= 30;
    }
}
