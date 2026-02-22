<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $default = $this->defaultRedirectUrl($request);

        return redirect()->intended($default);
    }

    private function defaultRedirectUrl(Request $request): string
    {
        $referer = $request->headers->get('referer');
        if ($referer && $this->isSameOrigin($request, $referer)) {
            return $referer;
        }

        return Fortify::redirects('login') ?? '/';
    }

    private function isSameOrigin(Request $request, string $url): bool
    {
        $ourHost = $request->getHost();
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;

        return $host !== null && strtolower($host) === strtolower($ourHost);
    }
}
