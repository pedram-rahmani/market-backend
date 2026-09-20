<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/storage/{path}', function (string $path) {
    $path = str_replace('\\', '/', $path);

    abort_if(
        str_contains($path, '..') || str_starts_with($path, '/'),
        404
    );

    $disks = ['public', 'storage'];

    foreach ($disks as $diskName) {
        $disk = Storage::disk($diskName);

        if ($disk->exists($path)) {
            return response()->file($disk->path($path));
        }
    }

    abort(404);
})->where('path', '.*');

require __DIR__.'/auth.php';
