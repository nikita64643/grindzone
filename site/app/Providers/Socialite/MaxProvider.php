<?php

namespace App\Providers\Socialite;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

/**
 * Max (мессенджер VK) — авторизация через VK ID.
 * Регистрация приложения: https://id.vk.com/
 */
class MaxProvider extends AbstractProvider
{
    protected $usesPKCE = true;

    protected $scopeSeparator = ' ';

    protected $scopes = ['vkid.personal_info', 'email'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://id.vk.ru/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://id.vk.ru/oauth2/auth';
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->post('https://id.vk.ru/oauth2/user_info', [
            RequestOptions::FORM_PARAMS => [
                'access_token' => $token,
                'client_id' => $this->clientId,
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $user = $data['user'] ?? $data;

        return [
            'id' => $user['user_id'] ?? $user['id'] ?? null,
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar'] ?? null,
        ];
    }

    protected function mapUserToObject(array $user): User
    {
        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: null;

        return (new User)->setRaw($user)->map([
            'id' => (string) ($user['id'] ?? $user['user_id'] ?? ''),
            'name' => $name,
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar'] ?? null,
        ]);
    }

    protected function getTokenFields($code): array
    {
        $fields = parent::getTokenFields($code);
        $fields['grant_type'] = 'authorization_code';

        return $fields;
    }
}
