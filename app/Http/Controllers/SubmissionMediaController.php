<?php

namespace App\Http\Controllers;

use App\Support\SubmissionMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionMediaController extends Controller
{
    public function show(Request $request, string $path): StreamedResponse
    {
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $resolved = SubmissionMedia::resolve($path);
        if ($resolved === null) {
            abort(404);
        }

        return Storage::disk($resolved['disk'])->response(
            $resolved['path'],
            null,
            ['Cache-Control' => 'private, max-age=3600']
        );
    }
}
