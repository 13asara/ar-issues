<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPILING = 'compiling';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    protected $fillable = ['code', 'title', 'status'];
    protected $casts = ['compiled_at' => 'datetime'];

    public function images(): HasMany
    {
        return $this->hasMany(TargetImage::class)->orderBy('target_index');
    }

    public function arUrl(): string
    {
        return rtrim(config('app.url'), '/').'/ar/'.$this->code;
    }

    public function mindUrl(): string
    {
        return rtrim(config('app.url'), '/').'/storage/issues/'.$this->code.'/targets.mind';
    }

    public function needsRecompile(): bool
    {
        if ($this->status !== self::STATUS_READY) {
            return true;
        }

        if ($this->compiled_at === null) {
            return true;
        }

        $lastChange = $this->images()->max('updated_at');

        return $lastChange !== null && strtotime($lastChange) > $this->compiled_at->getTimestamp();
    }
}
