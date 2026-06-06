<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\Report;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function storePropertyReport(StoreReportRequest $request, Property $property): JsonResponse
    {
        if ($property->isOwnedBy($request->user())) {
            return response()->json([
                'message' => 'Vous ne pouvez pas signaler votre propre annonce.',
            ], 422);
        }

        try {
            $report = Report::create([
                'reporter_id'     => $request->user()->id,
                'reportable_type' => Property::class,
                'reportable_id'   => $property->id,
                'reason'          => $request->reason,
                'description'     => $request->description,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'message' => 'Vous avez déjà signalé cette annonce.',
            ], 422);
        }

        return ReportResource::make($report)->response()->setStatusCode(201);
    }

    public function storeMessageReport(StoreReportRequest $request, Message $message): JsonResponse
    {
        $conversation = $message->conversation;

        if (!$conversation->isParticipant($request->user())) {
            return response()->json([
                'message' => 'Vous ne pouvez pas signaler ce message.',
            ], 403);
        }

        if ($message->sender_id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas signaler votre propre message.',
            ], 422);
        }

        try {
            $report = Report::create([
                'reporter_id'     => $request->user()->id,
                'reportable_type' => Message::class,
                'reportable_id'   => $message->id,
                'reason'          => $request->reason,
                'description'     => $request->description,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'message' => 'Vous avez déjà signalé ce message.',
            ], 422);
        }

        return ReportResource::make($report)->response()->setStatusCode(201);
    }
}
