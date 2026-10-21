<?php

namespace App\Http\Controllers;

use App\Models\CustomFont;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FontController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => CustomFont::query()->where('is_active', true)->orderBy('family')->orderBy('name')->get()->map(fn (CustomFont $font) => $this->resource($font))]);
    }

    public function show(CustomFont $customFont): JsonResponse
    {
        abort_unless($customFont->is_active, 404);
        return response()->json(['data' => $this->resource($customFont)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['font' => ['required', 'file', 'max:10240']]);
        $file = $data['font'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['ttf', 'otf'], true)) throw ValidationException::withMessages(['font' => 'يُسمح برفع TTF أو OTF فقط.']);
        $mime = (string) $file->getMimeType();
        if (! in_array($mime, ['font/ttf', 'font/otf', 'application/x-font-ttf', 'application/x-font-opentype', 'application/octet-stream'], true)) throw ValidationException::withMessages(['font' => 'محتوى الملف ليس خطًا صالحًا.']);
        $hash = hash_file('sha256', $file->getRealPath());
        if (CustomFont::where('file_hash', $hash)->exists()) throw ValidationException::withMessages(['font' => 'هذا الخط موجود مسبقًا.']);
        $family = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $path = $file->storeAs('fonts/shared', Str::uuid().'.'.$extension, 'local');
        $font = CustomFont::create(['uploaded_by' => $request->user()->id, 'name' => $family, 'family' => $family, 'format' => $extension, 'file_path' => $path, 'disk' => 'local', 'file_hash' => $hash, 'mime_type' => $mime, 'file_size' => $file->getSize(), 'is_active' => true]);
        return response()->json(['data' => $this->resource($font)], 201);
    }

    public function status(Request $request, CustomFont $customFont): JsonResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $customFont->update(['is_active' => $data['is_active'], 'suspended_at' => $data['is_active'] ? null : now()]);
        return response()->json(['data' => $this->resource($customFont)]);
    }

    public function file(CustomFont $customFont)
    {
        abort_unless($customFont->is_active, 404);
        $disk = Storage::disk($customFont->disk);
        abort_unless($disk->exists($customFont->file_path), 404);
        return response()->file($disk->path($customFont->file_path), ['Content-Type' => $customFont->mime_type, 'Cache-Control' => 'public, max-age=3600', 'Access-Control-Allow-Origin' => config('app.url')]);
    }

    private function resource(CustomFont $font): array
    {
        return ['id' => $font->id, 'name' => $font->name, 'family' => $font->family, 'format' => $font->format, 'is_active' => $font->is_active, 'file_url' => route('fonts.file', $font)];
    }
}
