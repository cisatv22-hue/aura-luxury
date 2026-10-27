<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireConfirmedTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('admin.require_two_factor', true)) {
            return $next($request);
        }

        if ($request->user() && ! $request->user()->two_factor_confirmed_at) {
            return redirect()->route('security.edit')->with('status', 'two-factor-required');
        }

        return $next($request);
    }
}
