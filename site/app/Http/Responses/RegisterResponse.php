<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
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

        return Fortify::redirects('register') ?? '/';
    }

    private function isSameOrigin(Request $request, string $url): bool
    {
        $ourHost = $request->getHost();
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;

        return $host !== null && strtolower($host) === strtolower($ourHost);
    }
}
