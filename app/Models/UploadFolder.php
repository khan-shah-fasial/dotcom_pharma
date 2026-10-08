<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class UploadFolder extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'user_id',
    ];

    public static function ready(): bool
    {
        return Schema::hasTable('upload_folders') && Schema::hasColumn('uploads', 'folder_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function breadcrumb(): array
    {
        $crumbs = [];
        $seen = [];
        $current = $this;

        while ($current && !isset($seen[$current->id])) {
            $seen[$current->id] = true;
            array_unshift($crumbs, $current);
            $current = $current->parent_id ? static::find($current->parent_id) : null;
        }

        return $crumbs;
    }

    /**
     * @return array<int, array{id:int,name:string,depth:int}>
     */
    public static function flatTree(): array
    {
        $grouped = static::query()->orderBy('name')->get()->groupBy(function ($folder) {
            return $folder->parent_id ?: 0;
        });

        $out = [];
        $walk = function ($parentId, $depth) use (&$walk, $grouped, &$out) {
            foreach ($grouped->get($parentId, collect()) as $folder) {
                $out[] = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'depth' => $depth,
                ];
                $walk($folder->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    public function containsFolder(int $folderId): bool
    {
        $seen = [];
        $current = $folderId;

        while ($current) {
            if ($current === (int) $this->id) {
                return true;
            }
            if (isset($seen[$current])) {
                return true;
            }
            $seen[$current] = true;
            $current = static::query()->where('id', $current)->value('parent_id');
            $current = $current ? (int) $current : 0;
        }

        return false;
    }
}
