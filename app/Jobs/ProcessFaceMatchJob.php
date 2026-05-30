<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Verification;
use App\Services\FaceMatchCoordinator;
use App\Services\FaceMatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessFaceMatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $verificationId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $verification = Verification::query()->with('customer')->find($this->verificationId);

        if (! $verification) {
            return;
        }

        $customer = $verification->customer;

        if (! $customer instanceof Customer) {
            return;
        }

        if (app(FaceMatchCoordinator::class)->shouldSkipAsyncRun($verification)) {
            return;
        }

        $idFrontPath = $customer->id_front_photo_path;
        $headshotPath = $customer->headshot_photo_path;

        if (! $idFrontPath || ! $headshotPath) {
            $verification->update([
                'face_match_status' => 'review',
                'face_match_score' => 0,
                'face_match_reason' => 'Missing ID front or headshot photo.',
                'face_match_ran_at' => now(),
            ]);

            return;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($idFrontPath) || ! $disk->exists($headshotPath)) {
            $verification->update([
                'face_match_status' => 'review',
                'face_match_score' => 0,
                'face_match_reason' => 'Face match photos not found on disk.',
                'face_match_ran_at' => now(),
            ]);

            return;
        }

        $idFrontFile = $disk->path($idFrontPath);
        $headshotFile = $disk->path($headshotPath);

        $service = app(FaceMatchService::class);

        $result = $service->match(
            new UploadedFile($idFrontFile, basename($idFrontFile), null, null, true),
            new UploadedFile($headshotFile, basename($headshotFile), null, null, true),
        );

        $verification->update([
            'face_match_status' => $result['status'],
            'face_match_score' => $result['score'],
            'face_match_reason' => $result['reason'],
            'face_match_ran_at' => now(),
        ]);
    }
}
