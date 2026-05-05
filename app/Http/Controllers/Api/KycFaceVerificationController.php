<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Verification;
use App\Services\FaceMatchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class KycFaceVerificationController extends Controller
{
    use ApiResponse;

    // ──────────────────────────────────────────────────────────────────────────
    // STEP A — Upload ID Front Photo
    // POST /api/v1/kyc/application/{customer_id}/face/id-photo
    //
    // FO uploads the customer's ID card (front side) first.
    // This is the reference image that the live face scan will be compared against.
    // Returns the stored URL so the FO app can preview it immediately.
    // ──────────────────────────────────────────────────────────────────────────
    public function uploadIdPhoto(Request $request, string $customerId): JsonResponse
    {
        $customer = $this->findCustomerOrFail($customerId);

        $request->validate([
            'id_front_photo' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('id_front_photo')->store('kyc/id_front', 'public');

        $customer->update(['id_front_photo_path' => $path]);

        $verification = Verification::firstOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            ['status' => 'pending', 'stage' => 1, 'fo_id' => auth()->id()],
        );

        if ($verification->face_match_status !== 'manual_verified') {
            $verification->update([
                'face_match_status' => 'pending',
                'face_match_score' => null,
                'face_match_reason' => null,
                'face_match_ran_at' => null,
            ]);
        }

        return $this->successResponse([
            'customer_id' => $customer->id,
            'id_front_url' => $this->photoUrl($path),
            'face_match_status' => $verification->fresh()->face_match_status,
        ], 'ID front photo saved. Proceed to face scan.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STEP B — Submit Face Scan Frame & Run Match Synchronously
    // POST /api/v1/kyc/application/{customer_id}/face/verify
    //
    // The FO app's camera scans the customer's face in real-time.
    // When the camera detects a clear face, the app captures that frame and
    // POSTs it here. The backend immediately compares it against the stored
    // ID front photo and returns the result — no background queue needed.
    //
    // The FO app should call this multiple times if the result is not 'passed'
    // (e.g. bad lighting, angle) — each call overwrites the previous headshot.
    // ──────────────────────────────────────────────────────────────────────────
    public function verifyFace(Request $request, string $customerId): JsonResponse
    {
        $customer = $this->findCustomerOrFail($customerId);

        $request->validate([
            'face_frame' => ['required', 'image', 'max:5120'],
        ]);

        if (! $customer->id_front_photo_path) {
            return $this->errorResponse(
                'Upload the ID front photo before running face verification.',
                422
            );
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($customer->id_front_photo_path)) {
            return $this->errorResponse(
                'ID front photo is missing from storage. Re-upload it and try again.',
                422
            );
        }

        $headshotPath = $request->file('face_frame')->store('kyc/headshot', 'public');

        $customer->update(['headshot_photo_path' => $headshotPath]);

        $idFrontFile = $disk->path($customer->id_front_photo_path);
        $headshotFile = $disk->path($headshotPath);

        $result = app(FaceMatchService::class)->match(
            new UploadedFile($idFrontFile, basename($idFrontFile), null, null, true),
            new UploadedFile($headshotFile, basename($headshotFile), null, null, true),
        );

        $verification = Verification::firstOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            ['status' => 'pending', 'stage' => 1, 'fo_id' => auth()->id()],
        );

        if ($verification->face_match_status !== 'manual_verified') {
            $verification->update([
                'face_match_status' => $result['status'],
                'face_match_score' => $result['score'],
                'face_match_reason' => $result['reason'],
                'face_match_ran_at' => now(),
            ]);
        }

        $passed = $result['status'] === 'passed';

        $message = match ($result['status']) {
            'passed' => 'Face verified — uso unalingana na kitambulisho.',
            'review' => 'Haikuwa wazi. Jaribu tena au endelea kwa ukaguzi wa mkono.',
            default => 'Uso haulingani na kitambulisho. Jaribu tena.',
        };

        return $this->successResponse([
            'customer_id' => $customer->id,
            'passed' => $passed,
            'face_match' => [
                'status' => $result['status'],
                'score' => $result['score'],
                'reason' => $result['reason'],
                'ran_at' => now()->toDateTimeString(),
                'alert' => ! $passed,
            ],
            'headshot_url' => $this->photoUrl($headshotPath),
            'id_front_url' => $this->photoUrl($customer->id_front_photo_path),
        ], $message);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STEP C — Poll Face Match Status
    // GET /api/v1/kyc/application/{customer_id}/face/status
    //
    // Returns the current face match state for the customer.
    // Useful after navigating away and returning, or for displaying
    // the verification badge on the identity step screen.
    // ──────────────────────────────────────────────────────────────────────────
    public function faceStatus(string $customerId): JsonResponse
    {
        $customer = $this->findCustomerOrFail($customerId, ['latestKycVerification.faceMatchManualVerifiedBy']);

        $v = $customer->latestKycVerification;

        return $this->successResponse([
            'customer_id' => $customer->id,
            'has_id_front' => filled($customer->id_front_photo_path),
            'has_headshot' => filled($customer->headshot_photo_path),
            'id_front_url' => $this->photoUrl($customer->id_front_photo_path),
            'headshot_url' => $this->photoUrl($customer->headshot_photo_path),
            'face_match' => $v ? [
                'status' => $v->face_match_status,
                'score' => $v->face_match_score,
                'reason' => $v->face_match_reason,
                'ran_at' => $v->face_match_ran_at?->toDateTimeString(),
                'alert' => in_array($v->face_match_status, ['review', 'failed'], true),
                'manual_verified_by' => $v->faceMatchManualVerifiedBy?->name,
                'manual_verified_at' => $v->face_match_manual_verified_at?->toDateTimeString(),
            ] : null,
        ], 'Face verification status retrieved.');
    }

    private function findCustomerOrFail(string $customerId, array $with = []): Customer
    {
        $query = Customer::with($with);

        if (! auth()->user()?->isAdmin()) {
            $query->where('registered_by', auth()->id());
        }

        return $query->findOrFail($customerId);
    }

    private function photoUrl(?string $path): ?string
    {
        return $path ? route('api.kyc.public-media', ['path' => $path]) : null;
    }
}
