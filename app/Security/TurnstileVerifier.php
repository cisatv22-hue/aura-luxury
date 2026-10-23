<?php

namespace App\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TurnstileVerifier
{
    public function enabled(): bool
    {
        return (bool) config('integrations.turnstile.site_key') && (bool) config('integrations.turnstile.secret_key');
    }

    public function verify(string $token, string $ip): bool
    {
        $secret = config('integrations.turnstile.secret_key');
        if (! is_string($secret) || $secret === '') {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
                'idempotency_key' => (string) Str::uuid(),
            ]);

            return $response->successful() && $response->json('success') === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
