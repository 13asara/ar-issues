<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CompileMindJob;
use App\Models\Issue;
use App\Models\TargetImage;
use App\Models\TargetVideo;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IssueController extends Controller
{
    public function index()
    {
        return view('admin.issues.index', [
            'issues' => Issue::withCount('images')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('admin.issues.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $issue = Issue::create([
            'title' => $data['title'],
            'code' => strtoupper(Str::random(8)),
            'status' => Issue::STATUS_DRAFT,
        ]);

        return redirect()->route('admin.issues.show', $issue);
    }

    public function show(Issue $issue)
    {
        $issue->load('images.videos', 'images.issue');

        return view('admin.issues.show', [
            'issue' => $issue,
            'needsRecompile' => $issue->needsRecompile(),
        ]);
    }

    public function destroy(Issue $issue)
    {
        $issue->delete();

        return redirect()->route('admin.issues.index');
    }

    public function uploadImages(Request $request, Issue $issue)
    {
        $request->validate([
            'images' => ['required', 'array', 'max:8'],
            'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:20480', 'dimensions:min_width=200,min_height=200'],
        ]);

        $nextIndex = (int) ($issue->images()->max('target_index') ?? -1) + 1;

        foreach ($request->file('images') as $file) {
            $size = @getimagesize($file->getRealPath());
            $ext = strtolower($file->getClientOriginalExtension());
            $diskPath = sprintf('issues/%s/images/%02d.%s', $issue->code, $nextIndex, $ext);

            $file->storeAs(dirname($diskPath), basename($diskPath), 'public');

            $issue->images()->create([
                'target_index' => $nextIndex,
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'original_name' => $file->getClientOriginalName(),
                'disk_path' => $diskPath,
            ]);

            $nextIndex++;
        }

        return back()->with('success', 'Фото загружены');
    }

    public function deleteImage(Issue $issue, TargetImage $image)
    {
        Storage::disk('public')->delete($image->disk_path);

        foreach ($image->videos as $video) {
            if ($video->disk_path) {
                Storage::disk('public')->delete($video->disk_path);
            }
        }

        $image->delete();

        return back()->with('success', 'Фото удалено. При изменении состава фото нужна повторная компиляция');
    }

    public function saveVideo(Request $request, Issue $issue, TargetImage $image)
    {
        $data = $request->validate([
            'source_type' => ['required', 'in:local,youtube'],
            'video_file' => ['required_if:source_type,local', 'nullable', 'file', 'max:512000'],
            'youtube' => ['required_if:source_type,youtube', 'nullable', 'string'],
        ]);

        if ($data['source_type'] === TargetVideo::TYPE_LOCAL) {
            $file = $request->file('video_file');

            if ($file === null) {
                return back()->withErrors(['video_file' => 'Файл не выбран']);
            }

            $path = sprintf('issues/%s/videos/%02d-%s.%s', $issue->code, $image->target_index, Str::random(6), strtolower($file->getClientOriginalExtension()));

            $file->storeAs(dirname($path), basename($path), 'public');

            $image->videos()->updateOrCreate([], [
                'source_type' => TargetVideo::TYPE_LOCAL,
                'disk_path' => $path,
                'youtube_id' => null,
                'original_name' => $file->getClientOriginalName(),
            ]);
        } else {
            $youtubeId = $this->extractYoutubeId($data['youtube'] ?? '');

            if ($youtubeId === null) {
                return back()->withErrors(['youtube' => 'Не удалось распознать ссылку/ID YouTube']);
            }

            $image->videos()->updateOrCreate([], [
                'source_type' => TargetVideo::TYPE_YOUTUBE,
                'disk_path' => null,
                'youtube_id' => $youtubeId,
            ]);
        }

        return back()->with('success', 'Видео прикреплено');
    }

    public function compile(Request $request, Issue $issue)
    {
        if ($issue->images()->count() === 0) {
            return back()->withErrors(['compile' => 'Сначала загрузите целевые фото']);
        }

        $issue->update([
            'status' => Issue::STATUS_COMPILING,
            'compile_log' => null,
        ]);

        dispatch(new CompileMindJob($issue));

        return back()->with('success', 'Компиляция поставлена в очередь. Статус обновится автоматически');
    }

    public function qr(Issue $issue)
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 8,
            'imageBase64' => false,
            'quietzoneSize' => 2,
        ]);

        return response((new QRCode($options))->render($issue->arUrl()), 200)
            ->header('Content-Type', 'image/png');
    }

    private function extractYoutubeId(string $input): ?string
    {
        if (preg_match('#^[A-Za-z0-9_-]{6,20}$#', $input) === 1) {
            return $input;
        }

        $ok = preg_match('#(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{6,20})#', $input, $m);

        return $ok === 1 ? $m[1] : null;
    }
}
