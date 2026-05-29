<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface DocumentServiceInterface
{
    /** @return array{ file_path: string, original_name: string, mime_type: string, file_size: int } */
    public function storeDocument(UploadedFile $file, int $rentalRequestId, string $type): array;

    public function deleteDocument(string $filePath): void;

    public function deleteAllDocuments(int $rentalRequestId): void;

    public function getDocumentContent(string $filePath): string;
}
