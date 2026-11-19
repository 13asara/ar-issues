<?php

namespace App\Http\Controllers;

use App\Models\Issue;

class ArPageController extends Controller
{
    public function show(string $code)
    {
        $issue = Issue::query()->where('code', mb_strtoupper($code))->first();
        $exists = $issue !== null;

        return view('ar.index', [
            'code' => $code,
            'exists' => $exists,
            'title' => $issue?->title ?? 'AR выпуск',
        ]);
    }
}
