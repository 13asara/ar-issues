<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetVideo extends Model
{
    public const TYPE_LOCAL = 'local';
    public const TYPE_YOUTUBE = 'youtube';

    protected $table = 'target_videos';

    protected $fillable = ['target_image_id', 'source_type', 'disk_path', 'youtube_id', 'original_name'];

    public function image(): BelongsTo
    {
        return $this->belongsTo(TargetImage::class, 'target_image_id');
    }

    public function playableUrl(): ?string
    {
        return $this->source_type === self::TYPE_LOCAL && $this->disk_path
            ? rtrim(config('app.url'), '/').'/storage/'.$this->disk_path
            : null;
    }

    public function embedUrl(): ?string
    {
        return $this->source_type === self::TYPE_YOUTUBE && $this->youtube_id
            ? 'https://www.youtube-nocookie.com/embed/'.$this->youtube_id.'?autoplay=1&mute=0&playsinline=1&rel=0'
            : null;
    }
}
