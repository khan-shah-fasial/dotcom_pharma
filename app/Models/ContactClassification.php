<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class ContactClassification extends Model
{
    use PreventDemoModeChanges;

    public const KINDS = [
        'group' => null,
        'category' => 'group',
        'subcategory' => 'category',
        'type' => 'subcategory',
        'subject' => 'type',
        'industry' => null,
        'work_profile' => 'industry',
        'department' => null,
        'purpose' => null,
    ];

    public const KIND_LABELS = [
        'group' => 'Group',
        'category' => 'Category',
        'subcategory' => 'Subcategory',
        'type' => 'Type',
        'subject' => 'Subject',
        'industry' => 'Industry',
        'work_profile' => 'Work Profile',
        'department' => 'Department',
        'purpose' => 'Purpose',
    ];

    public const CONTACT_COLUMNS = [
        'group' => 'group_id',
        'category' => 'category_id',
        'subcategory' => 'subcategory_id',
        'type' => 'type_id',
        'subject' => 'subject_id',
        'industry' => 'industry_id',
        'work_profile' => 'work_profile_id',
        'department' => 'department_id',
        'purpose' => 'purpose_id',
    ];

    public const ROOT_KINDS = ['group', 'industry', 'department', 'purpose'];

    protected $fillable = ['parent_id', 'kind', 'name', 'status'];

    protected $casts = [
        'status' => 'integer',
        'parent_id' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOfKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    public function kindLabel(): string
    {
        return self::KIND_LABELS[$this->kind] ?? $this->kind;
    }

    public function requiredParentKind(): ?string
    {
        return self::KINDS[$this->kind] ?? null;
    }

    public function isUsedOnContacts(): bool
    {
        $column = self::CONTACT_COLUMNS[$this->kind] ?? null;

        if (!$column) {
            return false;
        }

        return DirectoryContact::query()->where($column, $this->id)->exists();
    }

    public static function parentKindFor(string $kind): ?string
    {
        return self::KINDS[$kind] ?? null;
    }
}
