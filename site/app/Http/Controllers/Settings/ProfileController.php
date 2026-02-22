<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $linked = $user->socialAccounts()->get(['provider', 'provider_data'])->keyBy('provider');
        $providers = [
            'vkontakte' => ['name' => 'VK', 'color' => '#4C75A3'],
            'discord' => ['name' => 'Discord', 'color' => '#5865F2'],
            'telegram' => ['name' => 'Telegram', 'color' => '#26A5E4'],
            'google' => ['name' => 'Google', 'color' => '#4285F4'],
            'max' => ['name' => 'Max', 'color' => '#07C160'],
        ];
        $socialAccounts = collect($providers)->map(function ($info, $provider) use ($linked) {
            $account = $linked->get($provider);
            return [
                'provider' => $provider,
                'name' => $info['name'],
                'color' => $info['color'],
                'linked' => (bool) $account,
                'display_name' => $account?->provider_data['name'] ?? $account?->provider_data['nickname'] ?? null,
            ];
        })->values()->all();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'socialAccounts' => $socialAccounts,
            'socialError' => $request->session()->get('error'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
