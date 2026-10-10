<?php

namespace App\Http\Controllers\PrintProvider;

use App\Http\Controllers\Controller;
use App\Models\PrintProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A print shop's identity and licence files are private: only the admin and the shop's own owner can open them.
 * New files live on the private disk; files uploaded before that were on the public disk and are still found there
 * until `documents:make-private` has moved them.
 */
class DocumentController extends Controller
{
    public const FIELDS = ['verification_document', 'id_document'];

    public function __invoke(Request $request, PrintProvider $provider, string $field): StreamedResponse
    {
        abort_unless(in_array($field, self::FIELDS, true), 404);

        $user = $request->user();
        abort_unless($user->hasRole('admin') || $provider->user_id === $user->id, 403);

        $path = ltrim((string) $provider->getAttribute($field), '/');
        abort_if($path === '', 404);

        $disk = collect(['local', 'public'])->first(fn (string $name) => Storage::disk($name)->exists($path));
        abort_if($disk === null, 404);

        return Storage::disk($disk)->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** The link a page should use for one of the shop's documents, or null when there is no file. */
    public static function url(?PrintProvider $provider, string $field): ?string
    {
        $path = ltrim((string) $provider?->getAttribute($field), '/');

        if ($path === '' || ! collect(['local', 'public'])->contains(fn (string $name) => Storage::disk($name)->exists($path))) {
            return null;
        }

        return route('print-provider.documents', [$provider, $field]);
    }
}
