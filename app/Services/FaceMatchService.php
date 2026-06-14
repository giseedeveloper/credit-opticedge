<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            Log::warning('face_match.match skipped: FACE_MATCH_URL is not configured');

            if ($this->isRequired()) {
                return [
                    'status' => 'failed',
                    'score' => 0.0,
                    'reason' => 'Face match service is required but not configured.',
                ];
            }

            return [
                'status' => 'review',
                'score' => 0.0,
                'reason' => 'Face match service is not configured.',
            ];
        }

        try {
            $res = Http::timeout(45)
                ->retry(3, 500)
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
        } catch (ConnectionException $e) {
            Log::warning('face_match.match connection failed', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'review',
                'score' => 0.0,
                'reason' => 'Face match service is unreachable.',
            ];
        } catch (RequestException $e) {
            $body = $e->response ? substr((string) $e->response->body(), 0, 800) : null;
            Log::warning('face_match.match http error', [
                'endpoint' => $endpoint,
                'http_status' => $e->response?->status(),
                'body_preview' => $body,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'review',
                'score' => 0.0,
                'reason' => 'Face match failed. Try manual verification.',
            ];
        }
    }

    private function isRequired(): bool
    {
        return (bool) config('services.face_match.required', false);
    }
}
