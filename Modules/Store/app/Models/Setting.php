<?php

namespace Modules\Store\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'description',
        'type',
        'group',
        'is_public',
        'is_required',
        'validation_rules',
        'options',
        'display_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_required' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array',
        'display_order' => 'integer',
    ];

    /**
     * Get the setting values for this setting
     */
    public function values(): HasMany
    {
        return $this->hasMany(SettingValue::class);
    }

    /**
     * Get a setting value for a specific locale
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getValue(?string $locale = null): ?string
    {
        $value = $this->values()
            ->where('locale', $locale)
            ->first();

        return $value ? $value->value : null;
    }

    /**
     * Set a value for a specific locale
     *
     * @param string $value
     * @param string|null $locale
     * @return bool
     */
    public function setValue(string $value, ?string $locale = null): bool
    {
        return $this->values()->updateOrCreate(
            ['locale' => $locale],
            ['value' => $value]
        )->exists;
    }

    /**
     * Get the casted value based on the setting type
     *
     * @param string|null $locale
     * @return mixed
     */
    public function getCastedValue(?string $locale = null)
    {
        $value = $this->getValue($locale);

        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Scope a query to only include public settings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include settings from a specific group
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
} 