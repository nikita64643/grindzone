<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['vkontakte', 'discord', 'telegram', 'google', 'max'];

    public function redirect(Request $request, string $provider): RedirectResponse|Response|HttpResponse
    {
        $redirect = $this->validateProviderOrRedirect($request, $provider, 'login');
        if ($redirect) {
            return $redirect;
        }
        $request->session()->forget('social_auth_intent');

        if ($provider === 'telegram') {
            return response(Socialite::driver('telegram')->redirect()->getContent(), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function redirectLink(Request $request, string $provider): RedirectResponse|Response|HttpResponse
    {
        $redirect = $this->validateProviderOrRedirect($request, $provider, 'profile.edit');
        if ($redirect) {
            return $redirect;
        }
        abort_unless($request->user(), 401);

        $request->session()->put('social_auth_intent', [
            'action' => 'link',
            'user_id' => $request->user()->id,
            'provider' => $provider,
        ]);

        if ($provider === 'telegram') {
            return response(Socialite::driver('telegram')->redirect()->getContent(), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $redirect = $this->validateProviderOrRedirect($request, $provider, 'login');
        if ($redirect) {
            return $redirect;
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'Не удалось войти через '.$this->providerLabel($provider));
        }

        $providerUserId = (string) $socialUser->getId();
        $providerData = [
            'name' => $socialUser->getName(),
            'nickname' => $socialUser->getNickname(),
            'avatar' => $socialUser->getAvatar(),
            'email' => $socialUser->getEmail(),
        ];

        $intent = $request->session()->pull('social_auth_intent');

        if ($intent && ($intent['action'] ?? '') === 'link' && ($intent['provider'] ?? '') === $provider) {
            return $this->handleLink($request, $intent['user_id'], $provider, $providerUserId, $providerData);
        }

        return $this->handleLogin($request, $provider, $providerUserId, $providerData);
    }

    private function handleLink(Request $request, int $userId, string $provider, string $providerUserId, array $providerData): RedirectResponse
    {
        $existing = SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($existing && (int) $existing->user_id !== $userId) {
            return redirect()->route('profile.edit')
                ->with('error', 'Этот аккаунт '.$this->providerLabel($provider).' уже привязан к другому пользователю.');
        }

        SocialAccount::updateOrCreate(
            [
                'user_id' => $userId,
                'provider' => $provider,
            ],
            [
                'provider_user_id' => $providerUserId,
                'provider_data' => $providerData,
            ]
        );

        return redirect()->route('profile.edit')
            ->with('status', $this->providerLabel($provider).' успешно привязан.');
    }

    private function handleLogin(Request $request, string $provider, string $providerUserId, array $providerData): RedirectResponse
    {
        $account = SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->with('user')
            ->first();

        if (! $account) {
            return redirect()->route('login')
                ->with('error', 'Сначала привяжите аккаунт '.$this->providerLabel($provider).' в настройках профиля.');
        }

        Auth::login($account->user, $request->boolean('remember', true));

        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home', '/profile'));
    }

    private function validateProviderOrRedirect(Request $request, string $provider, string $backRoute): ?RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        $configKey = $provider;
        $config = config("services.{$configKey}");
        if (empty($config['client_id'] ?? null) && empty($config['client_secret'] ?? null) && empty($config['bot'] ?? null)) {
            return redirect()->route($backRoute)->with('error', 'OAuth для '.$this->providerLabel($provider).' не настроен.');
        }

        return null;
    }

    public function unlink(Request $request, string $provider): RedirectResponse
    {
        $redirect = $this->validateProviderOrRedirect($request, $provider, 'profile.edit');
        if ($redirect) {
            return $redirect;
        }
        $user = $request->user();
        abort_unless($user, 401);

        $deleted = SocialAccount::where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();

        if (! $deleted) {
            throw ValidationException::withMessages(['provider' => 'Привязка не найдена.']);
        }

        return redirect()->route('profile.edit')
            ->with('status', $this->providerLabel($provider).' отвязан.');
    }

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            'vkontakte' => 'VK',
            'discord' => 'Discord',
            'telegram' => 'Telegram',
            'google' => 'Google',
            'max' => 'Max',
            default => Str::title($provider),
        };
    }
}
