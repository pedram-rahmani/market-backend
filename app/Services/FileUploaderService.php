<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FileUploaderService
{
    public function upload(UploadedFile $file, string $folder = 'reviews'): string
    {
        // save files in -> storage/app/public/reviews
        return $file->store($folder, 'public');
    }
}
