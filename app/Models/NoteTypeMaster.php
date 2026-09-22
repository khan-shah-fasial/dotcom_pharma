<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NoteTypeMaster extends Model
{
    protected $table = 'note_types';

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('note_types');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public static function activeOptions()
    {
        if (! static::tableReady()) {
            return collect();
        }

        return static::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug(trim($name), '_');
        if ($base === '') {
            $base = 'type';
        }
        $base = Str::limit($base, 50, '');

        $slug = $base;
        $i = 2;
        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $suffix = '_' . $i;
            $slug = Str::limit($base, 50 - strlen($suffix), '') . $suffix;
            $i++;
        }

        return $slug;
    }

    public function notesCount(): int
    {
        return Note::query()->where('note_type', $this->slug)->count();
    }
}
