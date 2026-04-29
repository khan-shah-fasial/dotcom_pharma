<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SurepassDigilockerService
{
    public function initiate(string $aadhaarNo, string $state, string $redirectUrl): array
    {
        $url = config('services.surepass.digilocker.initiate_url');
        $token = config('services.surepass.token');

        if (!$url || !$token) {
            throw new RuntimeException('Surepass DigiLocker configuration is missing.');
        }

        $body = $this->post($url, $token, [
            'data' => [
                'signup_flow' => true,
                'redirect_url' => $redirectUrl,
            ],
        ]);

        $authorizationUrl = $this->firstValue($body, ['data.url', 'data.redirect_url', 'data.authorization_url', 'url']);
        $clientId = $this->firstValue($body, ['data.client_id', 'client_id']);

        if (!$authorizationUrl || !$clientId) {
            throw new RuntimeException($this->messageFrom($body, 'Unable to start DigiLocker verification.'));
        }

        return [
            'authorization_url' => $authorizationUrl,
            'session_id' => $clientId,
            'client_id' => $clientId,
            'raw_status' => $this->firstValue($body, ['status', 'success', 'message_code']),
        ];
    }

    public function downloadAadhaar(string $clientId): array
    {
        $url = $this->downloadAadhaarUrl($clientId);
        $token = config('services.surepass.token');

        if (!$url || !$token) {
            throw new RuntimeException('Surepass DigiLocker configuration is missing.');
        }

        $body = $this->get($url, $token);
        $normalized = $this->normalizeAadhaarData($body);

        if (empty(array_filter($normalized))) {
            throw new RuntimeException($this->messageFrom($body, 'Unable to fetch Aadhaar details from DigiLocker.'));
        }

        return $normalized;
    }

    public function normalizeAadhaarData(array $body): array
    {
        $xmlData = Arr::get($body, 'data.aadhaar_xml_data')
            ?: Arr::get($body, 'data.aadhaar_data')
            ?: Arr::get($body, 'aadhaar_xml_data')
            ?: Arr::get($body, 'aadhaar_data')
            ?: Arr::get($body, 'data', []);

        if (is_object($xmlData)) {
            $xmlData = json_decode(json_encode($xmlData), true) ?: [];
        }

        if (!is_array($xmlData)) {
            $xmlData = [];
        }

        $address = Arr::get($xmlData, 'address', []);
        if (is_object($address)) {
            $address = json_decode(json_encode($address), true) ?: [];
        }

        if (!is_array($address)) {
            $address = [];
        }

        $pick = fn (array $source, array $keys) => $this->firstValue($source, $keys);

        $house = $pick($address, ['house']);
        $street = $pick($address, ['street']);
        $landmark = $pick($address, ['landmark', 'lm']);
        $locality = $pick($address, ['loc', 'locality']);
        $village = $pick($address, ['vtc', 'village']);
        $post = $pick($address, ['po', 'post_office']);
        $district = $pick($address, ['dist', 'district']);
        $state = $pick($address, ['state']);
        $country = $pick($address, ['country']);
        $zip = $pick($xmlData, ['zip', 'pincode', 'pin_code', 'postal_code']);

        $addressText = $pick($xmlData, ['full_address', 'address_text']);
        $addressParts = array_filter([$house, $street, $locality, $village, $district, $state, $country]);

        return [
            'aadhaar_no' => preg_replace('/\D+/', '', (string) $pick($xmlData, ['aadhaar_number', 'id_number', 'uid'])) ?: null,
            'masked_aadhaar' => $pick($xmlData, ['masked_aadhaar']),
            'name' => $pick($xmlData, ['full_name', 'name']),
            'father_name' => $pick($xmlData, ['care_of', 'father_name']),
            'dob' => $this->normalizeDate($pick($xmlData, ['dob', 'date_of_birth'])),
            'gender' => $pick($xmlData, ['gender']),
            'profile_image' => $pick($xmlData, ['profile_image']),
            'street_add_first_personal' => $addressText ?: implode(', ', $addressParts),
            'locality_land_mark_personal' => $landmark ?: $locality,
            'village_personal' => $village ?: $locality,
            'post_personal' => $post,
            'pincode_personal' => preg_replace('/\D+/', '', (string) $zip) ?: null,
            'district_personal' => $district,
            'state_name' => $state,
            'city_name' => $village ?: $locality,
            'country_name' => $country,
        ];
    }

    private function post(string $url, string $token, array $payload): array
    {
        try {
            return Http::timeout(30)
                ->connectTimeout(10)
                ->acceptJson()
                ->withToken($token)
                ->post($url, $payload)
                ->throw()
                ->json() ?? [];
        } catch (RequestException $e) {
            $body = $e->response?->json();
            if (is_array($body)) {
                throw new RuntimeException($this->messageFrom($body, 'Surepass DigiLocker request failed.'));
            }

            throw new RuntimeException('Surepass DigiLocker request failed.');
        }
    }

    private function get(string $url, string $token): array
    {
        try {
            return Http::timeout(30)
                ->connectTimeout(10)
                ->acceptJson()
                ->withToken($token)
                ->get($url)
                ->throw()
                ->json() ?? [];
        } catch (RequestException $e) {
            $body = $e->response?->json();
            if (is_array($body)) {
                throw new RuntimeException($this->messageFrom($body, 'Surepass Aadhaar download failed.'));
            }

            throw new RuntimeException('Surepass Aadhaar download failed.');
        }
    }

    private function downloadAadhaarUrl(string $clientId): string
    {
        $base = config('services.surepass.digilocker.download_aadhaar_url');
        if (!$base) {
            $initializeUrl = config('services.surepass.digilocker.initiate_url');
            $parts = parse_url($initializeUrl ?: '');
            if (empty($parts['scheme']) || empty($parts['host'])) {
                return '';
            }

            $base = $parts['scheme'] . '://' . $parts['host'] . '/api/v1/digilocker/download-aadhaar';
        }

        return rtrim($base, '/') . '/' . rawurlencode($clientId);
    }

    private function firstValue(array $body, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = Arr::get($body, $path);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function messageFrom(array $body, string $fallback): string
    {
        return $this->firstValue($body, ['message', 'error', 'error.message', 'data.message']) ?: $fallback;
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }
}
