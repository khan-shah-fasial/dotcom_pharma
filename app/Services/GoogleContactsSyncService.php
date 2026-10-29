<?php

namespace App\Services;

use App\Imports\GoogleContactsImport;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleContactsSyncService
{
    private const TOKEN_SETTING = 'google_contacts_refresh_token';

    public function credentialsConfigured(): bool
    {
        $clientId = trim((string) config('services.google_contacts.client_id'));
        $clientSecret = trim((string) config('services.google_contacts.client_secret'));
        if ($clientId === '' || $clientSecret === '') {
            return false;
        }

        return !str_contains(strtolower($clientId), 'placeholder')
            && !str_contains(strtolower($clientSecret), 'placeholder');
    }

    public function connected(): bool
    {
        return $this->refreshToken() !== null;
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('services.google_contacts.redirect'));
        if ($configured !== '') {
            return $configured;
        }

        return route('contact-directory.google.callback');
    }

    public function authorizationUrl(): string
    {
        $this->assertCredentials();
        $state = Str::random(40);
        session(['google_contacts_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.google_contacts.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/contacts.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    public function storeAuthorizationCode(string $code, string $state): void
    {
        $this->assertCredentials();
        $expected = (string) session()->pull('google_contacts_oauth_state');
        if ($code === '' || $expected === '' || !hash_equals($expected, $state)) {
            throw new RuntimeException(translate('Google Contacts connection could not be verified. Try connecting again.'));
        }

        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google_contacts.client_id'),
            'client_secret' => config('services.google_contacts.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException($this->safeGoogleMessage(
                $response->json(),
                translate('Google did not accept the connection. Check the client id, secret, and redirect URI.')
            ));
        }

        $refreshToken = (string) $response->json('refresh_token');
        if ($refreshToken === '') {
            throw new RuntimeException(translate('Google did not return a refresh token. Remove this app from the Google account permissions and connect again.'));
        }

        $this->storeRefreshToken($refreshToken);
    }

    public function disconnect(): void
    {
        BusinessSetting::query()->where('type', self::TOKEN_SETTING)->delete();
        Cache::forget('business_settings');
    }

    public function sync(): GoogleContactsImport
    {
        $this->assertCredentials();
        if (!$this->connected()) {
            throw new RuntimeException(translate('Connect a Google account before syncing contacts.'));
        }

        @set_time_limit(300);
        $token = $this->accessToken();
        $import = new GoogleContactsImport();
        $import->begin(true);

        $pageToken = null;
        do {
            $query = [
                'personFields' => 'names,emailAddresses,phoneNumbers,organizations,addresses,biographies,urls',
                'pageSize' => 1000,
                'sources' => 'READ_SOURCE_TYPE_CONTACT',
            ];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $response = Http::withToken($token)
                ->timeout(60)
                ->acceptJson()
                ->get('https://people.googleapis.com/v1/people/me/connections', $query);

            if ($response->status() === 401) {
                $this->disconnect();
                throw new RuntimeException(translate('Google Contacts access expired. Connect the Google account again.'));
            }
            if (!$response->successful()) {
                throw new RuntimeException($this->safeGoogleMessage(
                    $response->json(),
                    translate('Google Contacts could not be read. Enable the People API for this app.')
                ));
            }

            foreach ($response->json('connections') ?? [] as $person) {
                if (is_array($person)) {
                    $import->addParsed($this->mapPerson($person));
                }
            }

            $pageToken = $response->json('nextPageToken');
        } while (is_string($pageToken) && $pageToken !== '');

        return $import;
    }

    private function mapPerson(array $person): array
    {
        $nameRow = is_array($person['names'][0] ?? null) ? $person['names'][0] : [];
        $name = trim((string) ($nameRow['displayName'] ?? ''));
        if ($name === '') {
            $name = trim(trim((string) ($nameRow['givenName'] ?? '')) . ' ' . trim((string) ($nameRow['familyName'] ?? '')));
        }

        $org = is_array($person['organizations'][0] ?? null) ? $person['organizations'][0] : [];
        $address = is_array($person['addresses'][0] ?? null) ? $person['addresses'][0] : [];
        $notes = [];

        foreach ($person['biographies'] ?? [] as $biography) {
            if (!is_array($biography)) {
                continue;
            }
            $value = trim((string) ($biography['value'] ?? ''));
            if ($value !== '') {
                $notes[] = $value;
            }
        }

        $department = trim((string) ($org['department'] ?? ''));
        if ($department !== '') {
            $notes[] = 'Department: ' . $department;
        }

        $street = trim(implode(', ', array_filter([
            trim((string) ($address['streetAddress'] ?? '')),
            trim((string) ($address['extendedAddress'] ?? '')),
        ])));

        return [
            'label' => $name !== '' ? $name : translate('Google contact'),
            'name' => $name,
            'company_name' => trim((string) ($org['name'] ?? '')),
            'designation' => trim((string) ($org['title'] ?? '')),
            'emails' => $this->pairs($person['emailAddresses'] ?? []),
            'phones' => $this->pairs($person['phoneNumbers'] ?? []),
            'websites' => $this->pairs($person['urls'] ?? []),
            'street' => $street,
            'city' => trim((string) ($address['city'] ?? '')),
            'region' => trim((string) ($address['region'] ?? '')),
            'postal' => trim((string) ($address['postalCode'] ?? '')),
            'country' => trim((string) ($address['country'] ?? $address['countryCode'] ?? '')),
            'tags' => [],
            'note_lines' => $notes,
            'photo_binary' => null,
            'photo_extension' => null,
            'photo_url' => null,
        ];
    }

    private function pairs(array $rows): array
    {
        $pairs = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $pairs[] = [
                'label' => trim((string) ($row['formattedType'] ?? $row['type'] ?? '')),
                'value' => $value,
            ];
        }

        return $pairs;
    }

    private function accessToken(): string
    {
        $refreshToken = $this->refreshToken();
        if ($refreshToken === null) {
            throw new RuntimeException(translate('Connect a Google account before syncing contacts.'));
        }

        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google_contacts.client_id'),
            'client_secret' => config('services.google_contacts.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            $error = (string) data_get($response->json(), 'error');
            if ($error === 'invalid_grant') {
                $this->disconnect();
                throw new RuntimeException(translate('Google Contacts access expired. Connect the Google account again.'));
            }
            throw new RuntimeException($this->safeGoogleMessage(
                $response->json(),
                translate('Google did not accept the connection. Check the client id, secret, and redirect URI.')
            ));
        }

        $accessToken = (string) $response->json('access_token');
        if ($accessToken === '') {
            throw new RuntimeException(translate('Google did not return an access token.'));
        }

        return $accessToken;
    }

    private function refreshToken(): ?string
    {
        $stored = get_setting(self::TOKEN_SETTING);
        if (!is_string($stored) || $stored === '') {
            return null;
        }

        try {
            $token = Crypt::decryptString($stored);
        } catch (Throwable $exception) {
            return null;
        }

        return $token !== '' ? $token : null;
    }

    private function storeRefreshToken(string $refreshToken): void
    {
        BusinessSetting::updateOrCreate(
            ['type' => self::TOKEN_SETTING],
            ['value' => Crypt::encryptString($refreshToken), 'lang' => null]
        );
        Cache::forget('business_settings');
    }

    private function assertCredentials(): void
    {
        if (!$this->credentialsConfigured()) {
            throw new RuntimeException(translate('Add the Google Contacts client id and secret before connecting an account.'));
        }
    }

    private function safeGoogleMessage(?array $body, string $fallback): string
    {
        $message = data_get($body, 'error.message') ?? data_get($body, 'error_description');
        if (!is_string($message)) {
            return $fallback;
        }

        $message = trim($message);
        if ($message === '' || strlen($message) > 240 || preg_match('/token|secret|authorization/i', $message)) {
            return $fallback;
        }

        return $message;
    }
}
