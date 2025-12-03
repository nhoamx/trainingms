<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EvaluationCustomField extends Model
{
    /** @use HasFactory<\Database\Factories\EvaluationCustomFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'paper_evaluation_id',
        'field_key',
        'key_label',
        'value',
    ];

    /**
     * Relationship with PaperEvaluation
     */
    public function paperEvaluation(): BelongsTo
    {
        return $this->belongsTo(PaperEvaluation::class);
    }

    /**
     * Convert a label to a snake_case key
     */
    public static function labelToKey(string $label): string
    {
        // Remove accents and special characters
        $key = Str::ascii($label);
        // Convert to snake_case
        $key = Str::snake($key);
        // Remove any remaining non-alphanumeric characters except underscores
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        // Remove multiple consecutive underscores
        $key = preg_replace('/_+/', '_', $key);
        // Trim underscores from start/end
        $key = trim($key, '_');

        return $key;
    }

    /**
     * Scope to filter by key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('field_key', $key);
    }

    /**
     * Scope to filter by key and value
     */
    public function scopeByKeyValue($query, string $key, string $value)
    {
        return $query->where('field_key', $key)->where('value', $value);
    }
}
