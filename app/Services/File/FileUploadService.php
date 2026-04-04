<?php

namespace App\Services\File;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    protected string $disk = 'local';

    /**
     * Upload a file and create an Attachment record.
     *
     * @param UploadedFile $file
     * @param string $attachableType 'App\Models\Task' or 'App\Models\Comment'
     * @param int $attachableId
     * @param int $userId
     * @return Attachment
     */
    public function upload(
        UploadedFile $file,
        string $attachableType,
        int $attachableId,
        int $userId
    ): Attachment {
        $path = $file->store('attachments', $this->disk);

        return Attachment::create([
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'user_id' => $userId,
            'filename' => $file->getClientOriginalName(),
            'url' => Storage::disk($this->disk)->url($path),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Delete an attachment and its file.
     */
    public function delete(Attachment $attachment): bool
    {
        // Extract path from URL
        $path = str_replace(Storage::disk($this->disk)->url(''), '', $attachment->url);
        $path = ltrim($path, '/');

        Storage::disk($this->disk)->delete($path);

        return $attachment->delete();
    }
}
