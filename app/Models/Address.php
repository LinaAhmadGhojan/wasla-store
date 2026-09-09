<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    public const LABEL_HOME = 'home';
    public const LABEL_WORK = 'work';
    public const LABEL_OTHER = 'other';

    public const LABEL_TYPES = [
        self::LABEL_HOME,
        self::LABEL_WORK,
        self::LABEL_OTHER,
    ];

    protected $fillable = [
        'user_id',
        'label',
        'label_type',
        'recipient_name',
        'phone',
        'country',
        'city',
        'state',
        'postal_code',
        'street_address',
        'courier_notes',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected $appends = [
        'label_type_ar',
        'has_coordinates',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLabelTypeArAttribute(): string
    {
        return match ($this->label_type) {
            self::LABEL_WORK => 'العمل',
            self::LABEL_OTHER => 'عنوان آخر',
            default => 'البيت',
        };
    }

    public function getHasCoordinatesAttribute(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function displayLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        return $this->label_type_ar;
    }
}
