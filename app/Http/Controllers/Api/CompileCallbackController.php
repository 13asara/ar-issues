<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompileCallbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = $request->header('X-Mind-Token');

        if ($token === null || ! hash_equals((string) config('mind.token'), $token)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'issue' => ['required', 'string', 'max:16'],
            'status' => ['required', 'in:ready,failed'],
            'log' => ['nullable', 'string', 'max:10000'],
        ]);

        $issue = Issue::query()->where('code', mb_strtoupper($data['issue']))->first();

        if ($issue === null) {
            return response()->json(['error' => 'issue_not_found'], 404);
        }

        $issue->update([
            'status' => $data['status'],
            'compile_log' => $data['log'] ?? $issue->compile_log,
            'compiled_at' => $data['status'] === 'ready' ? now() : $issue->compiled_at,
        ]);

        return response()->json(['ok' => true]);
    }
}
