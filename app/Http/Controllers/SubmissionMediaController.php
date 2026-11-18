<?php

namespace App\Http\Controllers;

use App\Support\SubmissionMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionMediaController extends Controller
{
    public function show(Request $request, string $path): StreamedResponse|Response
    {
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $resolved = SubmissionMedia::resolve($path);
        if ($resolved === null) {
            if (SubmissionMedia::isImage($path)) {
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500" width="100%" height="100%">'
                    . '<rect width="800" height="500" fill="#f1f5f9"/>'
                    . '<g fill="#94a3b8" transform="translate(350, 190)">'
                    . '<rect width="100" height="75" rx="8" fill="none" stroke="#94a3b8" stroke-width="6"/>'
                    . '<circle cx="32" cy="28" r="10" fill="#94a3b8"/>'
                    . '<path d="M12 60 L45 32 L88 65" stroke="#94a3b8" stroke-width="6" fill="none" stroke-linecap="round"/>'
                    . '</g>'
                    . '<text x="400" y="310" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif" font-size="18" fill="#64748b" text-anchor="middle" font-weight="600">Submission Image Preview</text>'
                    . '</svg>';

                return response($svg, 200, [
                    'Content-Type' => 'image/svg+xml',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }

            abort(404);
        }

        return Storage::disk($resolved['disk'])->response(
            $resolved['path'],
            null,
            ['Cache-Control' => 'private, max-age=3600']
        );
    }
}
