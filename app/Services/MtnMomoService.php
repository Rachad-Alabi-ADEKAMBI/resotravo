<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MtnMomoService
{
    private string  $baseUrl;
    private string  $collectionKey;
    private string  $environment;
    private string  $userId;
    private string  $apiKey;
    private string  $currency;
    private ?string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl       = config('momo.base_url',       'https://sandbox.momodeveloper.mtn.com');
        $this->collectionKey = config('momo.collection_key', '');
        $this->environment   = config('momo.environment',    'sandbox');
        $this->userId        = config('momo.user_id',        '');
        $this->apiKey        = config('momo.api_key',        '');
        $this->currency      = config('momo.currency',       'XOF');
        $this->callbackUrl   = config('momo.callback_url') ?: null;
    }

    // ──────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────────────

    /**
     * Initiate a collection request (debit from customer phone).
     *
     * @return array{success: bool, reference_id?: string, simulated?: bool, error?: string}
     */
    public function requestToPay(
        string $phone,
        float  $amount,
        string $externalId,
        string $description = 'Paiement Mesotravo'
    ): array {
        // ── Mode simulation (pas de credentials → dev/démo) ──
        if ($this->isSandboxWithoutCredentials()) {
            return [
                'success'      => true,
                'reference_id' => (string) Str::uuid(),
                'simulated'    => true,
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Impossible d\'obtenir le token MTN MoMo.'];
        }

        $referenceId = (string) Str::uuid();
        $phone       = $this->normalizePhone($phone);

        $headers = [
            'Authorization'             => "Bearer {$token}",
            'X-Reference-Id'            => $referenceId,
            'X-Target-Environment'      => $this->environment,
            'Ocp-Apim-Subscription-Key' => $this->collectionKey,
            'Content-Type'              => 'application/json',
        ];

        if ($this->callbackUrl) {
            $headers['X-Callback-Url'] = $this->callbackUrl;
        }

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post("{$this->baseUrl}/collection/v1_0/requesttopay", [
                'amount'       => (string) (int) round($amount),
                'currency'     => $this->currency,
                'externalId'   => $externalId,
                'payer'        => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
                'payerMessage' => mb_substr($description, 0, 160),
                'payeeNote'    => mb_substr($description, 0, 160),
            ]);

        if (in_array($response->status(), [200, 202])) {
            return ['success' => true, 'reference_id' => $referenceId];
        }

        Log::error('[MtnMomo] requestToPay failed', [
            'status'  => $response->status(),
            'body'    => $response->body(),
            'phone'   => $phone,
            'amount'  => $amount,
        ]);

        return [
            'success' => false,
            'error'   => $response->json('message') ?? "Erreur MTN MoMo (HTTP {$response->status()})",
        ];
    }

    /**
     * Poll the status of a payment request.
     *
     * @return 'SUCCESSFUL'|'FAILED'|'PENDING'
     */
    public function checkPaymentStatus(string $referenceId): string
    {
        if ($this->isSandboxWithoutCredentials()) {
            return 'SUCCESSFUL';
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return 'FAILED';
        }

        $response = Http::withHeaders([
            'Authorization'             => "Bearer {$token}",
            'X-Target-Environment'      => $this->environment,
            'Ocp-Apim-Subscription-Key' => $this->collectionKey,
        ])->timeout(15)
          ->get("{$this->baseUrl}/collection/v1_0/requesttopay/{$referenceId}");

        if (!$response->successful()) {
            Log::warning('[MtnMomo] checkPaymentStatus failed', [
                'reference_id' => $referenceId,
                'status'       => $response->status(),
            ]);
            return 'PENDING';
        }

        return match($response->json('status')) {
            'SUCCESSFUL' => 'SUCCESSFUL',
            'FAILED'     => 'FAILED',
            default      => 'PENDING',
        };
    }

    // ──────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────

    private function getAccessToken(): ?string
    {
        $credentials = base64_encode("{$this->userId}:{$this->apiKey}");

        $response = Http::withHeaders([
            'Authorization'             => "Basic {$credentials}",
            'Ocp-Apim-Subscription-Key' => $this->collectionKey,
        ])->timeout(15)
          ->post("{$this->baseUrl}/collection/token/");

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('[MtnMomo] getAccessToken failed', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return null;
    }

    /** No real credentials → use simulation mode */
    private function isSandboxWithoutCredentials(): bool
    {
        return $this->environment === 'sandbox'
            && (empty($this->collectionKey) || empty($this->userId) || empty($this->apiKey));
    }

    /** Normalize phone to ITU-T E.164 with Benin country code (229) */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        // Strip leading zeros
        $digits = ltrim($digits, '0');

        // Already has country code
        if (str_starts_with($digits, '229') && strlen($digits) === 11) {
            return $digits;
        }

        // Local 8-digit number
        if (strlen($digits) === 8) {
            return '229' . $digits;
        }

        return $digits;
    }
}
