<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class FaceMatchService
{
    /**
     * @return array{
     *   status: 'passed'|'review'|'failed',
     *   score: float,
     *   reason: ?string
     * }
     */
    public function match(UploadedFile $idFront, UploadedFile $headshot): array
    {
        $endpoint = config('services.face_match.url');

        if (! is_string($endpoint) || trim($endpoint) === '') {
            return [
                'status' => 'review',
                'score' => 0.0,
                'reason' => 'Face match service is not configured.',
            ];
        }

        try {
            $res = Http::timeout(25)
                ->retry(2, 250)
                ->attach('id_front', file_get_contents($idFront->getRealPath()), $idFront->getClientOriginalName())
                ->attach('headshot', file_get_contents($headshot->getRealPath()), $headshot->getClientOriginalName())
                ->post(rtrim($endpoint, '/').'/match');

            $res->throw();

            $data = $res->json();

            $status = is_string($data['status'] ?? null) ? $data['status'] : 'review';
            $score = is_numeric($data['score'] ?? null) ? (float) $data['score'] : 0.0;
            $reason = is_string($data['reason'] ?? null) ? $data['reason'] : null;

            if (! in_array($status, ['passed', 'review', 'failed'], true)) {
                $status = 'review';
            }

            $score = max(0.0, min(1.0, $score));

            return [
                'status' => $status,
                'score' => $score,
                'reason' => $reason,
            ];
        } catch (ConnectionException) {
            return [
                'status' => 'review',
                'score' => 0.0,
                'reason' => 'Face match service is unreachable.',
            ];
        } catch (RequestException) {
            return [
                'status' => 'review',
                'score' => 0.0,
                'reason' => 'Face match failed. Try manual verification.',
            ];
        }
    }
}
