<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TargetImage extends Model
{
    protected $table = 'target_images';

    protected $fillable = ['issue_id', 'disk_path', 'target_index', 'width', 'height', 'original_name'];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(TargetVideo::class);
    }

    public function imageUrl(): string
    {
        return rtrim(config('app.url'), '/').'/storage/'.$this->disk_path;
    }
}
