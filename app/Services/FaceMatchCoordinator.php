<?php

namespace App\Services;

use App\Jobs\ProcessFaceMatchJob;
use App\Models\Verification;

/**
 * Prevents async face-match jobs from racing the FO live-scan sync API.
 */
class FaceMatchCoordinator
{
    private const int SyncGraceMinutes = 5;

    public function queueAsyncIfNeeded(Verification $verification): void
    {
        $verification->refresh();

        if ($verification->face_match_status === 'manual_verified') {
            return;
        }

        if ($this->hasRecentSyncResult($verification)) {
            return;
        }

        ProcessFaceMatchJob::dispatch($verification->id);
    }

    public function shouldSkipAsyncRun(Verification $verification): bool
    {
        if ($verification->face_match_status === 'manual_verified') {
            return true;
        }

        return $this->hasRecentSyncResult($verification);
    }

    private function hasRecentSyncResult(Verification $verification): bool
    {
        if (! $verification->face_match_ran_at) {
            return false;
        }

        if ($verification->face_match_ran_at->lt(now()->subMinutes(self::SyncGraceMinutes))) {
            return false;
        }

        return in_array(
            (string) $verification->face_match_status,
            ['passed', 'review', 'failed'],
            true
        );
    }
}
