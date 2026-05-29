<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DocumentServiceInterface;
use App\Contracts\RentalRequestServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\RentalRequest\UploadDocumentRequest;
use App\Http\Resources\RentalDocumentResource;
use App\Models\RentalDocument;
use App\Models\RentalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RentalDocumentController extends Controller
{
    public function __construct(
        private RentalRequestServiceInterface $service,
        private DocumentServiceInterface $documentService,
    ) {}

    public function store(UploadDocumentRequest $request, RentalRequest $rentalRequest): JsonResponse
    {
        $this->authorize('manageDocuments', $rentalRequest);

        if (!$rentalRequest->isPending()) {
            return response()->json([
                'message' => 'Impossible d\'ajouter des documents sur une demande traitée.',
            ], 422);
        }

        try {
            $document = $this->service->addDocument(
                $rentalRequest,
                $request->user(),
                $request->file('document'),
                $request->validated('type'),
                $request->validated('description')
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new RentalDocumentResource($document))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, RentalRequest $rentalRequest, RentalDocument $document): JsonResponse
    {
        $this->authorize('delete', $document);

        if ($document->rental_request_id !== $rentalRequest->id) {
            abort(404);
        }

        try {
            $this->service->deleteDocument($document, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    public function download(Request $request, RentalDocument $document): Response|JsonResponse
    {
        $this->authorize('view', $document);

        $signature = $request->query('signature');
        $expires   = (int) $request->query('expires', 0);
        $expected  = hash_hmac('sha256', $document->id . $document->file_path, config('app.key'));

        if ($signature !== $expected || now()->timestamp > $expires) {
            return response()->json(['message' => 'Lien expiré ou invalide.'], 403);
        }

        try {
            $content = $this->documentService->getDocumentContent($document->file_path);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        return response($content, 200, [
            'Content-Type'        => $document->mime_type,
            'Content-Disposition' => 'attachment; filename="' . $document->original_name . '"',
            'Content-Length'      => $document->file_size,
        ]);
    }

    public function verifyDocument(Request $request, RentalDocument $document): JsonResponse
    {
        $this->authorize('verify', $document);

        $document->update([
            'is_verified' => true,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return RentalDocumentResource::make($document->fresh())->response();
    }
}
