<?php

namespace App\Jobs;

use App\Models\Issue;
use App\Services\MindCompilerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompileMindJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public int $tries = 1;

    public function __construct(public Issue $issue)
    {
        $this->timeout = (int) config('mind.timeout', 900) + 60;
    }

    public function handle(MindCompilerService $compiler): void
    {
        [$ok, $log] = $compiler->run($this->issue);

        if ($ok) {
            $this->issue->fresh()?->update([
                'status' => Issue::STATUS_READY,
                'compiled_at' => now(),
                'compile_log' => $log,
            ]);

            return;
        }

        $this->issue->update([
            'status' => Issue::STATUS_FAILED,
            'compile_log' => $log,
        ]);

        $this->fail("Mind compiler failed for issue {$this->issue->code}: {$log}");
    }

    public function failed(\Throwable $exception): void
    {
        $this->issue->update([
            'status' => Issue::STATUS_FAILED,
            'compile_log' => mb_substr($exception->getMessage(), 0, 5000),
        ]);
    }
}
