<?php

namespace App\Services;

use App\Contracts\DocumentServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService implements DocumentServiceInterface
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.documents_disk', 'documents');
    }

    public function storeDocument(UploadedFile $file, int $rentalRequestId, string $type): array
    {
        $uid       = now()->timestamp . '_' . uniqid();
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $filename  = "{$type}_{$uid}.{$extension}";
        $directory = "documents/{$rentalRequestId}/{$type}";
        $filePath  = "{$directory}/{$filename}";

        Storage::disk($this->disk)->put(
            $filePath,
            file_get_contents($file->getRealPath())
        );

        return [
            'file_path'     => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'     => $file->getSize(),
        ];
    }

    public function deleteDocument(string $filePath): void
    {
        Storage::disk($this->disk)->delete($filePath);
    }

    public function deleteAllDocuments(int $rentalRequestId): void
    {
        Storage::disk($this->disk)->deleteDirectory("documents/{$rentalRequestId}");
    }

    public function getDocumentContent(string $filePath): string
    {
        if (!Storage::disk($this->disk)->exists($filePath)) {
            throw new ModelNotFoundException('Document introuvable.');
        }

        return Storage::disk($this->disk)->get($filePath);
    }
}
