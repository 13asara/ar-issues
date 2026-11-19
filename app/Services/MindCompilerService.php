<?php

namespace App\Services;

use App\Models\Issue;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class MindCompilerService
{
    public function prepareJob(Issue $issue): string
    {
        $images = $issue->images()
            ->orderBy('target_index')
            ->get()
            ->map(fn ($img) => storage_path('app/public/'.$img->disk_path))
            ->values()
            ->all();

        $job = [
            'issue' => $issue->code,
            'images' => $images,
            'output' => storage_path('app/public/issues/'.$issue->code.'/targets.mind'),
            'callback' => [
                'url' => rtrim(config('app.url'), '/').'/api/compile/callback',
                'token' => config('mind.token'),
                'issue' => $issue->code,
            ],
        ];

        $jobPath = storage_path('app/mind-jobs/'.$issue->code.'-'.Str::random(6).'.json');

        if (! is_dir(dirname($jobPath))) {
            mkdir(dirname($jobPath), 0775, true);
        }

        file_put_contents($jobPath, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $jobPath;
    }

    public function run(Issue $issue): array
    {
        if ($issue->images()->count() === 0) {
            return [false, 'No target images uploaded'];
        }

        $jobPath = $this->prepareJob($issue);

        $node = config('mind.node');
        $compilerScript = base_path('compiler/compile.js');
        $timeout = (int) config('mind.timeout');

        $result = Process::timeout($timeout)
            ->path(base_path('compiler'))
            ->run([$node, $compilerScript, '--job', $jobPath]);

        $log = trim($result->output()."\n".$result->errorOutput());

        return [$result->successful(), $log];
    }
}
