<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\TargetVideo;
use Illuminate\Http\JsonResponse;

class IssueConfigController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $issue = Issue::query()
            ->where('code', mb_strtoupper($code))
            ->with('images.videos')
            ->first();

        if ($issue === null) {
            return response()->json([
                'error' => 'issue_not_found',
                'message' => 'Выпуск не найден',
            ], 404);
        }

        return response()->json([
            'code' => $issue->code,
            'title' => $issue->title,
            'status' => $issue->status,
            'mind_url' => $issue->status === Issue::STATUS_READY ? $issue->mindUrl() : null,
            'targets' => $issue->images->map(function ($image) use ($issue) {
                $video = $image->videos->first();
                $videoPayload = null;

                if ($video !== null) {
                    $videoPayload = $video->source_type === TargetVideo::TYPE_LOCAL
                        ? [
                            'type' => TargetVideo::TYPE_LOCAL,
                            'url' => $video->playableUrl(),
                            'title' => $video->original_name,
                        ]
                        : [
                            'type' => TargetVideo::TYPE_YOUTUBE,
                            'youtube_id' => $video->youtube_id,
                            'url' => $video->embedUrl(),
                            'title' => $video->original_name,
                        ];
                }

                return [
                    'target_index' => $image->target_index,
                    'image_url' => $image->imageUrl(),
                    'aspect' => ($image->width && $image->height) ? $image->width / $image->height : 1.0,
                    'video' => $videoPayload,
                ];
            })->values(),
        ]);
    }
}
