<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CompanyFile extends Model
{
    public const OWNER_COMPANY = 'company';

    public const OWNER_BILLING = 'billing';

    public const KIND_CERTIFICATE = 'certificate';

    public const KIND_DOCUMENT = 'document';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'kind',
        'name',
        'valid_until',
        'upload_id',
        'sort_order',
    ];

    protected $casts = [
        'valid_until' => 'date',
    ];

    public static function tableReady(): bool
    {
        return Schema::hasTable('company_files');
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    public static function normalizeRows($rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $validUntil = trim((string) ($row['valid_until'] ?? ''));
            $uploadId = trim((string) ($row['upload_id'] ?? ''));

            if ($name === '' && $validUntil === '' && $uploadId === '') {
                continue;
            }

            $clean[] = [
                'name' => $name === '' ? null : $name,
                'valid_until' => $validUntil === '' ? null : $validUntil,
                'upload_id' => $uploadId === '' ? null : $uploadId,
            ];
        }

        return $clean;
    }

    public static function replaceFor(string $ownerType, int $ownerId, array $certificates, array $documents): void
    {
        if (!static::tableReady()) {
            return;
        }

        static::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->delete();

        foreach ([
            self::KIND_CERTIFICATE => $certificates,
            self::KIND_DOCUMENT => $documents,
        ] as $kind => $rows) {
            foreach (array_values($rows) as $index => $row) {
                static::create([
                    'owner_type' => $ownerType,
                    'owner_id' => $ownerId,
                    'kind' => $kind,
                    'name' => $row['name'],
                    'valid_until' => $row['valid_until'] ?? null,
                    'upload_id' => $row['upload_id'],
                    'sort_order' => $index,
                ]);
            }
        }
    }

    public function toViewerArray(): array
    {
        $upload = $this->upload;
        $extension = strtolower((string) optional($upload)->extension);
        $uploadType = (string) optional($upload)->type;
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        if ($uploadType === 'image' || in_array($extension, $imageExtensions, true)) {
            $preview = 'image';
        } elseif ($extension === 'pdf') {
            $preview = 'pdf';
        } else {
            $preview = 'file';
        }

        return [
            'name' => $this->name,
            'valid_until' => optional($this->valid_until)->format('d M Y'),
            'url' => $upload ? uploaded_asset($upload->id) : '',
            'preview' => $preview,
            'extension' => $extension,
        ];
    }
}
