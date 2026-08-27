<?php

namespace App\Services\SumUp;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over SumUp's REST API (no official SDK). This is a
 * single-merchant integration, authenticated with a secret API key
 * ("sup_sk_...") used directly as a bearer token on every request — unlike a
 * multi-merchant OAuth2 app, there's no separate client-credentials token
 * exchange step.
 */
class SumUpClient
{
    private const BASE_URL = 'https://api.sumup.com';

    /**
     * @param  array{checkout_reference: string, amount: float, currency: string, merchant_code: string, description: string, return_url: string}  $payload
     * @return array<string, mixed>
     */
    public function createCheckout(array $payload): array
    {
        return Http::withToken(config('services.sumup.api_key'))
            ->post(self::BASE_URL.'/v0.1/checkouts', $payload)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function getCheckout(string $checkoutId): array
    {
        return Http::withToken(config('services.sumup.api_key'))
            ->get(self::BASE_URL.'/v0.1/checkouts/'.$checkoutId)
            ->throw()
            ->json();
    }
}
