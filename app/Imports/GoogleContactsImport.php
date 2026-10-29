<?php

namespace App\Imports;

use App\Models\Country;
use App\Models\DirectoryContact;
use App\Models\State;
use App\Models\Upload;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleContactsImport
{
    private int $created = 0;

    private int $skipped = 0;

    private array $messages = [];

    private array $takenEmails = [];

    private array $takenPhones = [];

    private array $countriesByName = [];

    private array $countriesByCode = [];

    private array $statesByCountry = [];

    private bool $hasRegion = false;

    private bool $requireIdentity = false;

    public function import(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            $this->reject(translate('This file could not be read. Export a Google CSV or vCard from Google Contacts.'));
        }

        $this->importContents($contents, $extension);
    }

    private function importContents(string $contents, string $extension): void
    {
        $extension = strtolower($extension);
        if (!in_array($extension, ['csv', 'vcf', 'txt'], true)) {
            $this->reject(translate('Upload a Google CSV or vCard file.'));
        }

        $contents = $this->toUtf8($contents);
        $parsed = $this->isVcard($contents)
            ? $this->parseVcards($contents)
            : $this->parseCsv($contents);

        $this->loadLookups();

        foreach ($parsed as $contact) {
            $this->storeParsedContact($contact);
        }
    }

    public function begin(bool $requireIdentity = false): void
    {
        $this->requireIdentity = $requireIdentity;
        $this->loadLookups();
    }

    public function addParsed(array $contact): void
    {
        $this->storeParsedContact($contact);
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }

    public function messages(): array
    {
        return $this->messages;
    }

    private function storeParsedContact(array $parsed): void
    {
        $label = $parsed['label'];
        $noteLines = $parsed['note_lines'];
        $email = null;
        $emailKeys = [];

        foreach ($parsed['emails'] ?? [] as $emailRow) {
            $value = trim((string) $emailRow['value']);
            if ($value === '') {
                continue;
            }
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $noteLines[] = ($emailRow['label'] !== '' ? $emailRow['label'] . ' email: ' : 'Email: ') . $value;
                continue;
            }
            $emailKeys[strtolower($value)] = $value;
        }

        $emailKeyList = array_keys($emailKeys);
        if ($emailKeyList) {
            $email = $emailKeys[$emailKeyList[0]];
            foreach (array_slice($emailKeyList, 1) as $extraKey) {
                $noteLines[] = 'Email: ' . $emailKeys[$extraKey];
            }
        }

        $phones = [];
        foreach ($parsed['phones'] ?? [] as $phoneRow) {
            $raw = trim((string) $phoneRow['value']);
            if ($raw === '') {
                continue;
            }
            $stored = $this->normalizePhone($raw);
            if ($stored === null) {
                $noteLines[] = ($phoneRow['label'] !== '' ? $phoneRow['label'] : 'Phone') . ': ' . $raw;
                continue;
            }
            $phones[] = [
                'kind' => $this->phoneKind($phoneRow['label']),
                'label' => $phoneRow['label'] !== '' ? $phoneRow['label'] : 'Phone',
                'stored' => $stored,
            ];
        }
        $phones = $this->uniquePhoneRows($phones);

        $phoneKeys = [];
        foreach ($phones as $phoneRow) {
            $key = $this->phoneKey($phoneRow['stored']);
            if ($key) {
                $phoneKeys[$key] = $key;
            }
        }
        $phoneKeys = array_values($phoneKeys);

        if ($this->requireIdentity && !$emailKeyList && !$phoneKeys) {
            $this->skip($label, translate('skipped because no email or phone number was found'));
            return;
        }

        if ($this->emailKeysTaken($emailKeyList) || $this->phoneKeysTaken($phoneKeys)) {
            $this->skip($label, translate('skipped because the email or phone number is already used'));
            return;
        }

        $whatsapp = $this->pullPhone($phones, 'whatsapp');
        $phone = $this->pullPhone($phones, 'mobile')
            ?? $this->pullPhone($phones, 'home')
            ?? $this->pullPhone($phones, 'work')
            ?? $this->pullPhone($phones, 'other');
        $alternate = $this->pullPhone($phones, 'mobile')
            ?? $this->pullPhone($phones, 'home')
            ?? $this->pullPhone($phones, 'work')
            ?? $this->pullPhone($phones, 'other');

        foreach ($phones as $remaining) {
            $noteLines[] = $remaining['label'] . ': ' . $remaining['stored'];
        }

        $name = trim((string) $parsed['name']);
        if ($name === '') {
            $name = trim((string) $parsed['company_name']);
        }
        if ($name === '' && $email) {
            $name = $email;
        }
        if ($name === '' && $phone) {
            $name = $phone;
        }
        if ($name === '' && $whatsapp) {
            $name = $whatsapp;
        }
        if ($name === '' && $alternate) {
            $name = $alternate;
        }
        if ($name === '') {
            $this->skip($label, translate('skipped because no name, email, or phone was found'));
            return;
        }

        $social = [];
        foreach ($parsed['websites'] as $website) {
            $value = trim((string) $website['value']);
            if ($value === '') {
                continue;
            }
            $platform = $this->socialPlatform((string) $website['label'], $value);
            if ($platform) {
                $social[] = [
                    'key' => $platform,
                    'value' => mb_substr($value, 0, 500),
                ];
                continue;
            }
            $siteLabel = trim((string) $website['label']) !== '' ? trim((string) $website['label']) : 'Website';
            $noteLines[] = $siteLabel . ': ' . $value;
        }

        if (!empty($parsed['photo_url'])) {
            $noteLines[] = 'Photo: ' . $parsed['photo_url'];
        }

        $countryName = trim((string) $parsed['country']);
        $countryId = $this->countryId($countryName);
        $regionName = trim((string) $parsed['region']);
        $stateId = $this->stateId($countryId, $regionName);

        $address = trim((string) $parsed['street']);
        if ($countryId === null && $countryName !== '' && !str_contains(strtolower($address), strtolower($countryName))) {
            $address = trim($address . ($address !== '' ? ', ' : '') . $countryName);
        }
        if (mb_strlen($address) > 500) {
            $noteLines[] = 'Address: ' . $address;
            $address = mb_substr($address, 0, 500);
        }

        if ($stateId === null && $regionName !== '' && !$this->hasRegion) {
            $noteLines[] = 'Region: ' . $regionName;
        }

        $notes = $this->joinNotes($noteLines);
        $tags = $this->cleanTags($parsed['tags']);

        $attributes = [
            'name' => mb_substr($name, 0, 255),
            'company_name' => $this->clip($parsed['company_name'], 255),
            'designation' => $this->clip($parsed['designation'], 255),
            'email' => $email ? mb_substr($email, 0, 255) : null,
            'phone' => $phone,
            'alternate_mobile_number' => $alternate,
            'whatsapp_number' => $whatsapp,
            'social_media_ids' => $social ?: null,
            'address' => $address !== '' ? $address : null,
            'country_id' => $countryId,
            'state_id' => $stateId,
            'district' => $this->clip($parsed['city'], 255),
            'post' => $this->clip($parsed['postal'], 100),
            'notes' => $notes,
            'tags' => $tags,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];

        if ($this->hasRegion) {
            $attributes['region'] = $stateId === null ? $this->clip($regionName, 255) : null;
        }

        try {
            $contact = DirectoryContact::create($attributes);
            $contact->contact_no = 'CN-' . str_pad((string) $contact->id, 5, '0', STR_PAD_LEFT);
            $contact->save();
        } catch (QueryException $exception) {
            if ($this->isDuplicateKey($exception)) {
                $this->skip($label, translate('skipped because the email or phone number is already used'));
                return;
            }
            throw $exception;
        }

        $this->rememberIdentity($emailKeyList, $phoneKeys);
        $this->attachPhoto($contact, $parsed);
        $this->created++;
    }

    private function attachPhoto(DirectoryContact $contact, array $parsed): void
    {
        if (empty($parsed['photo_binary']) || empty($parsed['photo_extension'])) {
            return;
        }

        $uploadId = $this->storePhoto($parsed['photo_binary'], $parsed['photo_extension']);
        if (!$uploadId) {
            $contact->notes = $this->joinNotes(array_filter([
                (string) $contact->notes,
                'Photo could not be read.',
            ]));
            $contact->save();
            return;
        }

        $contact->photo = $uploadId;
        $contact->save();
    }

    private function parseCsv(string $contents): array
    {
        $records = $this->csvRecords($contents);
        if (!$records) {
            $this->reject(translate('This file could not be read. Export a Google CSV or vCard from Google Contacts.'));
        }

        $headerRow = array_shift($records);
        $headerKeys = [];
        foreach ($headerRow as $index => $header) {
            $headerKeys[$index] = Str::slug(trim((string) $header), '_');
        }

        if (!$this->headerKeysLookLikeGoogle($headerKeys)) {
            $this->reject(translate('This file could not be read. Export a Google CSV or vCard from Google Contacts.'));
        }

        $parsed = [];
        foreach ($records as $index => $record) {
            if ($record === [null] || $record === ['']) {
                continue;
            }
            $row = [];
            $hasValue = false;
            foreach ($headerKeys as $column => $key) {
                if ($key === '') {
                    continue;
                }
                $value = trim((string) ($record[$column] ?? ''));
                $row[$key] = $value;
                if ($value !== '') {
                    $hasValue = true;
                }
            }
            if (!$hasValue) {
                continue;
            }
            $parsed[] = $this->fromCsvRow($row, 'Row ' . ($index + 2));
        }

        return $parsed;
    }

    private function fromCsvRow(array $row, string $label): array
    {
        $name = $this->cell($row, 'name');
        if ($name === '') {
            $name = trim(implode(' ', array_filter([
                $this->cell($row, 'name_prefix'),
                $this->cell($row, 'given_name'),
                $this->cell($row, 'additional_name'),
                $this->cell($row, 'family_name'),
                $this->cell($row, 'name_suffix'),
            ])));
        }

        $company = $this->cell($row, 'organization_1_name');
        $designation = $this->cell($row, 'organization_1_title');
        $notes = [];

        $this->addNote($notes, 'Department', $this->cell($row, 'organization_1_department'));
        $this->addNote($notes, 'Job description', $this->cell($row, 'organization_1_job_description'));
        $this->addNote($notes, 'Nickname', $this->cell($row, 'nickname'));
        $this->addNote($notes, 'Short name', $this->cell($row, 'short_name'));
        $this->addNote($notes, 'Maiden name', $this->cell($row, 'maiden_name'));
        $this->addNote($notes, 'Birthday', $this->cell($row, 'birthday'));
        $this->addNote($notes, 'Occupation', $this->cell($row, 'occupation'));
        $this->addNote($notes, 'Hobby', $this->cell($row, 'hobby'));
        $this->addNote($notes, 'Location', $this->cell($row, 'location'));
        if ($this->cell($row, 'notes') !== '') {
            $notes[] = $this->cell($row, 'notes');
        }

        foreach ([2, 3, 4, 5] as $orgIndex) {
            $orgName = $this->cell($row, 'organization_' . $orgIndex . '_name');
            $orgTitle = $this->cell($row, 'organization_' . $orgIndex . '_title');
            $orgDepartment = $this->cell($row, 'organization_' . $orgIndex . '_department');
            if ($orgName !== '' || $orgTitle !== '' || $orgDepartment !== '') {
                $notes[] = trim('Organization: ' . implode(', ', array_filter([$orgName, $orgTitle, $orgDepartment])));
            }
        }

        $address = $this->csvAddress($row, 1);
        foreach (range(2, 5) as $addressIndex) {
            $extra = $this->csvAddress($row, $addressIndex);
            if ($extra['street'] !== '' || $extra['city'] !== '' || $extra['region'] !== '' || $extra['country'] !== '') {
                $notes[] = 'Address: ' . implode(', ', array_filter([
                    $extra['street'],
                    $extra['city'],
                    $extra['region'],
                    $extra['postal'],
                    $extra['country'],
                ]));
            }
        }

        $this->addPairedNotes($notes, $row, ['custom_field'], 'Custom');
        $this->addPairedNotes($notes, $row, ['relation'], 'Relation');
        $this->addPairedNotes($notes, $row, ['event'], 'Event');

        $photo = $this->cell($row, 'photo');
        $photoUrl = null;
        if ($photo !== '') {
            if (filter_var($photo, FILTER_VALIDATE_URL)) {
                $photoUrl = $photo;
            } else {
                $notes[] = 'Photo: ' . $photo;
            }
        }

        return $this->blankParsed($label, [
            'name' => $name,
            'company_name' => $company,
            'designation' => $designation,
            'emails' => $this->csvPairs($row, ['e_mail', 'email']),
            'phones' => $this->csvPairs($row, ['phone']),
            'websites' => $this->csvPairs($row, ['website']),
            'street' => $address['street'],
            'city' => $address['city'],
            'region' => $address['region'],
            'postal' => $address['postal'],
            'country' => $address['country'],
            'tags' => $this->csvTags($this->cell($row, 'group_membership')),
            'note_lines' => $notes,
            'photo_url' => $photoUrl,
        ]);
    }

    private function csvAddress(array $row, int $index): array
    {
        $prefix = 'address_' . $index . '_';
        $formatted = $this->cell($row, $prefix . 'formatted');
        $street = $formatted !== '' ? $formatted : trim(implode(', ', array_filter([
            $this->cell($row, $prefix . 'street'),
            $this->cell($row, $prefix . 'extended_address'),
            $this->cell($row, $prefix . 'po_box'),
        ])));

        return [
            'street' => $street,
            'city' => $this->cell($row, $prefix . 'city'),
            'region' => $this->cell($row, $prefix . 'region'),
            'postal' => $this->cell($row, $prefix . 'postal_code'),
            'country' => $this->cell($row, $prefix . 'country'),
        ];
    }

    private function csvPairs(array $row, array $prefixes): array
    {
        $types = $this->numberedValues($row, $prefixes, 'type');
        $values = $this->numberedValues($row, $prefixes, 'value');
        $indexes = array_unique(array_merge(array_keys($types), array_keys($values)));
        sort($indexes);

        $pairs = [];
        foreach ($indexes as $index) {
            $value = $values[$index] ?? '';
            if ($value === '') {
                continue;
            }
            $pairs[] = [
                'label' => $types[$index] ?? '',
                'value' => $value,
            ];
        }

        return $pairs;
    }

    private function numberedValues(array $row, array $prefixes, string $slot): array
    {
        $found = [];
        foreach ($row as $key => $value) {
            foreach ($prefixes as $prefix) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '_(\d+)_' . preg_quote($slot, '/') . '$/', (string) $key, $matches)) {
                    $found[(int) $matches[1]] = trim((string) $value);
                }
            }
        }
        ksort($found);

        return $found;
    }

    private function addPairedNotes(array &$notes, array $row, array $prefixes, string $fallback): void
    {
        foreach ($this->csvPairs($row, $prefixes) as $pair) {
            $label = $pair['label'] !== '' ? $pair['label'] : $fallback;
            $notes[] = $label . ': ' . $pair['value'];
        }
    }

    private function csvTags(string $membership): array
    {
        if ($membership === '') {
            return [];
        }

        $tags = [];
        foreach (preg_split('/\s*:::\s*/', $membership) ?: [] as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    private function parseVcards(string $contents): array
    {
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $contents = preg_replace("/=\n[ \t]/", '', $contents) ?? $contents;
        $contents = preg_replace("/\n[ \t]/", '', $contents) ?? $contents;

        if (!preg_match_all('/BEGIN:VCARD\s*(.*?)\s*END:VCARD/si', $contents, $matches)) {
            $this->reject(translate('This file could not be read. Export a Google CSV or vCard from Google Contacts.'));
        }

        $parsed = [];
        foreach ($matches[1] as $index => $body) {
            $properties = $this->vcardProperties($body);
            if (!$properties) {
                continue;
            }
            $parsed[] = $this->fromVcard($properties, 'Card ' . ($index + 1));
        }

        return $parsed;
    }

    private function vcardProperties(string $body): array
    {
        $properties = [];
        foreach (explode("\n", $body) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }

            $left = substr($line, 0, $colon);
            $rawValue = substr($line, $colon + 1);
            $segments = explode(';', $left);
            $nameToken = (string) array_shift($segments);
            $group = null;
            if (str_contains($nameToken, '.')) {
                [$group, $nameToken] = explode('.', $nameToken, 2);
                $group = strtolower($group);
            }

            $params = [];
            foreach ($segments as $segment) {
                if (str_contains($segment, '=')) {
                    [$paramKey, $paramValue] = explode('=', $segment, 2);
                    $params[strtoupper(trim($paramKey))][] = trim($paramValue, "\" \t");
                } elseif (trim($segment) !== '') {
                    $params['TYPE'][] = trim($segment);
                }
            }

            $encoding = strtoupper(implode(' ', $params['ENCODING'] ?? []));
            $isBase64 = str_contains($encoding, 'BASE64') || $encoding === 'B';
            if (!$isBase64 && (str_contains($encoding, 'QUOTED-PRINTABLE') || $encoding === 'Q')) {
                $rawValue = quoted_printable_decode($rawValue);
            }

            $charset = strtoupper($params['CHARSET'][0] ?? 'UTF-8');
            if (!$isBase64 && !in_array($charset, ['UTF-8', 'UTF8', 'US-ASCII', 'ASCII', ''], true)) {
                $converted = @mb_convert_encoding($rawValue, 'UTF-8', $charset);
                if (is_string($converted)) {
                    $rawValue = $converted;
                }
            }

            $types = [];
            foreach ($params['TYPE'] ?? [] as $type) {
                foreach (explode(',', $type) as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $types[] = $part;
                    }
                }
            }

            $properties[] = [
                'name' => strtoupper($nameToken),
                'group' => $group,
                'types' => $types,
                'value' => $rawValue,
                'base64' => $isBase64,
                'params' => $params,
            ];
        }

        return $properties;
    }

    private function fromVcard(array $properties, string $label): array
    {
        $groupLabels = [];
        foreach ($properties as $property) {
            if ($property['name'] === 'X-ABLABEL' && $property['group']) {
                $groupLabels[$property['group']] = trim($this->unescape($property['value']));
            }
        }

        $name = '';
        $company = '';
        $designation = '';
        $notes = [];
        $emails = [];
        $phones = [];
        $websites = [];
        $tags = [];
        $address = $this->emptyAddress();
        $addressChosen = false;
        $photo = ['binary' => null, 'extension' => null];
        $photoUrl = null;

        foreach ($properties as $property) {
            $text = $property['base64'] ? '' : trim($this->unescape($property['value']));
            $itemLabel = $this->propertyLabel($property, $groupLabels);

            switch ($property['name']) {
                case 'FN':
                    if ($name === '' && $text !== '') {
                        $name = $text;
                    }
                    break;
                case 'N':
                    if ($name === '') {
                        $name = $this->nameFromStructured($property['value']);
                    }
                    break;
                case 'NICKNAME':
                    $this->addNote($notes, 'Nickname', $text);
                    break;
                case 'ORG':
                    $parts = $this->splitStructured($property['value']);
                    if ($company === '' && !empty($parts[0])) {
                        $company = $parts[0];
                        $this->addNote($notes, 'Department', $parts[1] ?? '');
                    } else {
                        $notes[] = 'Organization: ' . implode(', ', array_filter($parts));
                    }
                    break;
                case 'TITLE':
                    if ($designation === '') {
                        $designation = $text;
                    } else {
                        $this->addNote($notes, 'Title', $text);
                    }
                    break;
                case 'ROLE':
                    $this->addNote($notes, 'Role', $text);
                    break;
                case 'TEL':
                    $phoneValue = preg_replace('/^tel:/i', '', $text) ?? $text;
                    if (trim($phoneValue) !== '') {
                        $phones[] = ['label' => $itemLabel, 'value' => trim($phoneValue)];
                    }
                    break;
                case 'EMAIL':
                    $emailValue = preg_replace('/^mailto:/i', '', $text) ?? $text;
                    if (trim($emailValue) !== '') {
                        $emails[] = ['label' => $itemLabel, 'value' => trim($emailValue)];
                    }
                    break;
                case 'ADR':
                    $mapped = $this->vcardAddress($property['value']);
                    if (!$addressChosen && $this->addressHasData($mapped)) {
                        $address = $mapped;
                        $addressChosen = true;
                    } elseif ($this->addressHasData($mapped)) {
                        $notes[] = 'Address: ' . implode(', ', array_filter([
                            $mapped['street'],
                            $mapped['city'],
                            $mapped['region'],
                            $mapped['postal'],
                            $mapped['country'],
                        ]));
                    }
                    break;
                case 'URL':
                case 'X-SOCIALPROFILE':
                case 'IMPP':
                    if ($text !== '') {
                        $websites[] = ['label' => $itemLabel !== '' ? $itemLabel : ($property['types'][0] ?? ''), 'value' => $text];
                    }
                    break;
                case 'NOTE':
                    if ($text !== '') {
                        $notes[] = $text;
                    }
                    break;
                case 'CATEGORIES':
                    foreach ($this->splitList($property['value']) as $tag) {
                        $tags[] = $tag;
                    }
                    break;
                case 'BDAY':
                    $this->addNote($notes, 'Birthday', $this->formatVcardDate($text));
                    break;
                case 'X-ABDATE':
                    $this->addNote($notes, $itemLabel !== '' ? $itemLabel : 'Date', $this->formatVcardDate($text));
                    break;
                case 'X-ABRELATEDNAMES':
                    $this->addNote($notes, $itemLabel !== '' ? $itemLabel : 'Related', $text);
                    break;
                case 'PHOTO':
                    if ($photo['binary'] || $photoUrl) {
                        $notes[] = 'Additional photo omitted.';
                        break;
                    }
                    $read = $this->readPhoto($property);
                    if (!empty($read['url'])) {
                        $photoUrl = $read['url'];
                    } elseif (!empty($read['binary'])) {
                        $photo = $read;
                    } else {
                        $notes[] = 'Photo could not be read.';
                    }
                    break;
                default:
                    if ($this->keepExtraVcardProperty($property, $text)) {
                        $notes[] = $property['name'] . ': ' . $text;
                    }
                    break;
            }
        }

        return $this->blankParsed($label, [
            'name' => $name,
            'company_name' => $company,
            'designation' => $designation,
            'emails' => $emails,
            'phones' => $phones,
            'websites' => $websites,
            'street' => $address['street'],
            'city' => $address['city'],
            'region' => $address['region'],
            'postal' => $address['postal'],
            'country' => $address['country'],
            'tags' => $tags,
            'note_lines' => $notes,
            'photo_binary' => $photo['binary'],
            'photo_extension' => $photo['extension'],
            'photo_url' => $photoUrl,
        ]);
    }

    private function keepExtraVcardProperty(array $property, string $text): bool
    {
        if ($text === '' || $property['base64']) {
            return false;
        }

        $ignored = [
            'VERSION', 'PRODID', 'UID', 'REV', 'END', 'BEGIN', 'LOGO', 'KEY', 'SOUND',
            'GEO', 'TZ', 'CLASS', 'MAILER', 'PROFILE', 'SOURCE', 'NAME', 'SORT-STRING',
            'X-ABLABEL', 'FN', 'N',
        ];

        if (in_array($property['name'], $ignored, true)) {
            return false;
        }

        return mb_strlen($text) <= 500;
    }

    private function vcardAddress(string $value): array
    {
        $parts = $this->splitStructured($value);
        $street = trim(implode(', ', array_filter([
            $parts[2] ?? '',
            $parts[1] ?? '',
            $parts[0] ?? '',
        ])));

        return [
            'street' => $street,
            'city' => trim((string) ($parts[3] ?? '')),
            'region' => trim((string) ($parts[4] ?? '')),
            'postal' => trim((string) ($parts[5] ?? '')),
            'country' => trim((string) ($parts[6] ?? '')),
        ];
    }

    private function nameFromStructured(string $value): string
    {
        $parts = $this->splitStructured($value);

        return trim(implode(' ', array_filter([
            $parts[3] ?? '',
            $parts[1] ?? '',
            $parts[2] ?? '',
            $parts[0] ?? '',
            $parts[4] ?? '',
        ])));
    }

    private function readPhoto(array $property): array
    {
        $value = trim($property['value']);
        if ($value === '') {
            return [];
        }

        if (preg_match('#^https?://#i', $value) || strtoupper($property['params']['VALUE'][0] ?? '') === 'URI') {
            return ['url' => $this->unescape($value)];
        }

        if (preg_match('#^data:image/([a-zA-Z0-9.+-]+);base64,(.+)$#s', $value, $matches)) {
            $binary = base64_decode($matches[2], true);

            return $this->photoPayload(is_string($binary) ? $binary : '');
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $value) ?? '', true);

        return $this->photoPayload(is_string($binary) ? $binary : '');
    }

    private function photoPayload(string $binary): array
    {
        $extension = $this->imageExtension($binary);
        if ($extension === null) {
            return [];
        }

        return ['binary' => $binary, 'extension' => $extension];
    }

    private function imageExtension(string $binary): ?string
    {
        if ($binary === '' || strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'jpg';
        }
        if (str_starts_with($binary, "\x89PNG")) {
            return 'png';
        }
        if (str_starts_with($binary, 'GIF8')) {
            return 'gif';
        }
        if (strlen($binary) > 12 && str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
            return 'webp';
        }

        return null;
    }

    private function storePhoto(string $binary, string $extension): ?int
    {
        if (!in_array($extension, ['jpg', 'png', 'gif', 'webp'], true)) {
            return null;
        }

        $path = 'uploads/all/' . date('Y/m') . '/' . Str::random(40) . '.' . $extension;
        Storage::disk('local')->put($path, $binary);

        $upload = new Upload();
        $upload->file_original_name = 'google-contact-photo';
        $upload->extension = $extension;
        $upload->file_size = strlen($binary);
        $upload->user_id = auth()->id();
        $upload->type = 'image';
        $upload->file_name = $path;
        $upload->disk = 'local';
        $upload->save();

        return (int) $upload->id;
    }

    private function loadLookups(): void
    {
        $this->hasRegion = Schema::hasColumn('directory_contacts', 'region');

        $countries = Country::query()->get(['id', 'name', 'code']);
        foreach ($countries as $country) {
            $this->countriesByName[strtolower(trim((string) $country->name))] = (int) $country->id;
            $code = strtolower(trim((string) $country->code));
            if ($code !== '') {
                $this->countriesByCode[$code] = (int) $country->id;
            }
        }

        $states = State::query()->get(['id', 'name', 'country_id']);
        foreach ($states as $state) {
            $key = (int) $state->country_id . '|' . strtolower(trim((string) $state->name));
            $this->statesByCountry[$key] = (int) $state->id;
        }

        DirectoryContact::query()
            ->select(['email', 'phone', 'alternate_mobile_number', 'whatsapp_number', 'notes'])
            ->orderBy('id')
            ->chunk(500, function ($contacts) {
                foreach ($contacts as $contact) {
                    if ($contact->email) {
                        $this->takenEmails[strtolower($contact->email)] = true;
                    }
                    foreach (['phone', 'alternate_mobile_number', 'whatsapp_number'] as $column) {
                        $key = $this->phoneKey($contact->{$column});
                        if ($key) {
                            $this->rememberPhoneKey($key);
                        }
                    }
                    $this->rememberIdentitiesInNotes((string) $contact->notes);
                }
            });
    }

    private function countryId(string $name): ?int
    {
        $key = strtolower(trim($name));
        if ($key === '') {
            return null;
        }
        if (isset($this->countriesByName[$key])) {
            return $this->countriesByName[$key];
        }
        if (isset($this->countriesByCode[$key])) {
            return $this->countriesByCode[$key];
        }

        $aliases = [
            'united states of america' => 'united states',
            'usa' => 'united states',
            'u.s.a.' => 'united states',
            'u.s.' => 'united states',
            'uk' => 'united kingdom',
            'u.k.' => 'united kingdom',
            'great britain' => 'united kingdom',
            'uae' => 'united arab emirates',
            'u.a.e.' => 'united arab emirates',
            'russia' => 'russian federation',
            'south korea' => 'korea, republic of',
            'czech republic' => 'czechia',
        ];
        $alias = $aliases[$key] ?? null;
        if ($alias && isset($this->countriesByName[$alias])) {
            return $this->countriesByName[$alias];
        }

        return null;
    }

    private function stateId(?int $countryId, string $region): ?int
    {
        if (!$countryId || trim($region) === '') {
            return null;
        }

        $key = $countryId . '|' . strtolower(trim($region));

        return $this->statesByCountry[$key] ?? null;
    }

    private function headerKeysLookLikeGoogle(array $keys): bool
    {
        $keys = array_map(fn ($key) => strtolower((string) $key), $keys);
        foreach (['given_name', 'phone_1_value', 'e_mail_1_value', 'email_1_value', 'organization_1_name'] as $needle) {
            if (in_array($needle, $keys, true)) {
                return true;
            }
        }

        return false;
    }

    private function csvRecords(string $contents): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $records = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $records[] = $row;
        }
        fclose($handle);

        return $records;
    }

    private function isVcard(string $contents): bool
    {
        $sample = ltrim(substr($contents, 0, 1000), "\xEF\xBB\xBF \t\r\n");

        return strncasecmp($sample, 'BEGIN:VCARD', 11) === 0;
    }

    private function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, "\xFF\xFE") || str_starts_with($contents, "\xFE\xFF")) {
            $converted = @mb_convert_encoding($contents, 'UTF-8', 'UTF-16');
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            return substr($contents, 3);
        }

        return $contents;
    }

    private function normalizePhone(string $raw): ?string
    {
        $raw = trim(preg_replace('/^tel:/i', '', trim($raw)) ?? '');
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^\+?[0-9\s().-]{7,20}$/', $raw)) {
            return $raw;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $compact = (str_starts_with($raw, '+') ? '+' : '') . $digits;
        if (preg_match('/^\+?[0-9]{7,20}$/', $compact)) {
            return $compact;
        }

        return null;
    }

    private function phoneKey(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits === '' ? null : $digits;
    }

    private function phoneKind(string $label): string
    {
        $label = strtolower($label);
        if (str_contains($label, 'whatsapp') || str_contains($label, 'whats app')) {
            return 'whatsapp';
        }
        if (str_contains($label, 'cell') || str_contains($label, 'mobile') || str_contains($label, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($label, 'home')) {
            return 'home';
        }
        if (str_contains($label, 'work')) {
            return 'work';
        }

        return 'other';
    }

    private function pullPhone(array &$phones, string $kind): ?string
    {
        foreach ($phones as $index => $phone) {
            if ($phone['kind'] === $kind) {
                unset($phones[$index]);

                return $phone['stored'];
            }
        }

        return null;
    }

    private function uniquePhoneRows(array $phones): array
    {
        $unique = [];
        $seen = [];
        foreach ($phones as $phone) {
            $key = $this->phoneKey($phone['stored'] ?? null);
            if (!$key) {
                continue;
            }
            $bucket = strlen($key) >= 10 ? substr($key, -10) : $key;
            if (isset($seen[$bucket])) {
                continue;
            }
            $seen[$bucket] = true;
            $unique[] = $phone;
        }

        return $unique;
    }

    private function rememberIdentitiesInNotes(string $notes): void
    {
        if ($notes === '') {
            return;
        }

        if (preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $notes, $matches)) {
            foreach ($matches[0] as $found) {
                $this->takenEmails[strtolower($found)] = true;
            }
        }

        foreach (preg_split('/\R/', $notes) ?: [] as $line) {
            if (!preg_match('/:\s*(\+?[0-9\s().-]{7,20})$/', trim((string) $line), $match)) {
                continue;
            }
            $key = $this->phoneKey($match[1]);
            if ($key) {
                $this->rememberPhoneKey($key);
            }
        }
    }

    private function emailKeysTaken(array $keys): bool
    {
        foreach ($keys as $key) {
            if (isset($this->takenEmails[$key])) {
                return true;
            }
        }

        return false;
    }

    private function phoneKeysTaken(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->phoneKeyIsTaken($key)) {
                return true;
            }
        }

        return false;
    }

    private function phoneKeyIsTaken(string $key): bool
    {
        if (isset($this->takenPhones[$key])) {
            return true;
        }

        if (strlen($key) < 10) {
            return false;
        }

        return isset($this->takenPhones['suffix:' . substr($key, -10)]);
    }

    private function rememberPhoneKey(string $key): void
    {
        $this->takenPhones[$key] = true;
        if (strlen($key) >= 10) {
            $this->takenPhones['suffix:' . substr($key, -10)] = true;
        }
    }

    private function rememberIdentity(array $emailKeys, array $phoneKeys): void
    {
        foreach ($emailKeys as $emailKey) {
            $this->takenEmails[$emailKey] = true;
        }
        foreach ($phoneKeys as $key) {
            $this->rememberPhoneKey($key);
        }
    }

    private function socialPlatform(string $label, string $value): ?string
    {
        $haystack = strtolower($label . ' ' . $value);
        $platforms = [
            'instagram' => 'Instagram',
            'insta' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
            'telegram' => 'Telegram',
            'x.com' => 'X',
        ];

        foreach ($platforms as $needle => $platform) {
            if (str_contains($haystack, $needle)) {
                return $platform;
            }
        }

        return null;
    }

    private function cleanTags(array $tags): ?array
    {
        $clean = [];
        $seen = [];
        foreach ($tags as $tag) {
            $tag = trim((string) $tag);
            if ($tag === '' || str_starts_with($tag, '*') || strcasecmp($tag, 'myContacts') === 0) {
                continue;
            }
            $key = strtolower($tag);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = mb_substr($tag, 0, 100);
            if (count($clean) >= 30) {
                break;
            }
        }

        return $clean ?: null;
    }

    private function joinNotes(array $lines): ?string
    {
        $notes = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $notes[] = $line;
            }
        }
        if (!$notes) {
            return null;
        }

        return mb_substr(implode("\n", $notes), 0, 60000);
    }

    private function addNote(array &$notes, string $label, string $value): void
    {
        $value = trim($value);
        if ($value !== '') {
            $notes[] = $label . ': ' . $value;
        }
    }

    private function formatVcardDate(string $value): string
    {
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }

        return $value;
    }

    private function propertyLabel(array $property, array $groupLabels): string
    {
        if ($property['group'] && isset($groupLabels[$property['group']])) {
            return $groupLabels[$property['group']];
        }

        return implode(', ', $property['types']);
    }

    private function splitStructured(string $value): array
    {
        return $this->splitEscaped($value, ';');
    }

    private function splitList(string $value): array
    {
        return array_values(array_filter($this->splitEscaped($value, ','), fn ($part) => $part !== ''));
    }

    private function splitEscaped(string $value, string $delimiter): array
    {
        $parts = [];
        $current = '';
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            if ($value[$i] === '\\' && $i + 1 < $length) {
                $current .= $value[$i] . $value[$i + 1];
                $i++;
                continue;
            }
            if ($value[$i] === $delimiter) {
                $parts[] = trim($this->unescape($current));
                $current = '';
                continue;
            }
            $current .= $value[$i];
        }
        $parts[] = trim($this->unescape($current));

        return $parts;
    }

    private function unescape(string $value): string
    {
        $out = '';
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            if ($value[$i] === '\\' && $i + 1 < $length) {
                $next = $value[++$i];
                $out .= match (strtolower($next)) {
                    'n' => "\n",
                    ',' => ',',
                    ';' => ';',
                    '\\' => '\\',
                    default => $next,
                };
                continue;
            }
            $out .= $value[$i];
        }

        return $out;
    }

    private function cell(array $row, string $key): string
    {
        return trim((string) ($row[$key] ?? ''));
    }

    private function clip(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function blankParsed(string $label, array $values): array
    {
        return array_merge([
            'label' => $label,
            'name' => '',
            'company_name' => '',
            'designation' => '',
            'emails' => [],
            'phones' => [],
            'websites' => [],
            'street' => '',
            'city' => '',
            'region' => '',
            'postal' => '',
            'country' => '',
            'tags' => [],
            'note_lines' => [],
            'photo_binary' => null,
            'photo_extension' => null,
            'photo_url' => null,
        ], $values, ['label' => $label]);
    }

    private function emptyAddress(): array
    {
        return [
            'street' => '',
            'city' => '',
            'region' => '',
            'postal' => '',
            'country' => '',
        ];
    }

    private function addressHasData(array $address): bool
    {
        foreach ($address as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? '';

        return $sqlState === '23000' || str_contains($exception->getMessage(), 'Integrity constraint');
    }

    private function skip(string $label, string $reason): void
    {
        $this->skipped++;
        $this->messages[] = $label . ': ' . $reason;
    }

    private function reject(string $message): void
    {
        throw ValidationException::withMessages([
            'bulk_file' => $message,
        ]);
    }
}
