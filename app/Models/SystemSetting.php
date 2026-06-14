<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single configurable business value. Read/written through SystemSettingService
 * (which adds caching + audit logging) — avoid touching this model directly.
 */
class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'title',
        'description',
        'category',
        'is_editable',
    ];

    protected function casts(): array
    {
        return [
            'is_editable' => 'boolean',
        ];
    }
}
