<?php

namespace App\Models;

use App;
use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class Group extends Model
{
    use PreventDemoModeChanges;

    protected $with = ['group_translations'];

    public function getTranslation($field = '', $lang = false)
    {
        $lang = $lang == false ? App::getLocale() : $lang;
        $group_translation = $this->group_translations->where('lang', $lang)->first();
        return $group_translation != null ? $group_translation->$field : $this->$field;
    }

    public function group_translations()
    {
        return $this->hasMany(GroupTranslation::class);
    }

    public function coverImage()
    {
        return $this->belongsTo(Upload::class, 'cover_image');
    }

    public function groupIcon()
    {
        return $this->belongsTo(Upload::class, 'icon');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_groups');
    }

    public function bannerImage()
    {
        return $this->belongsTo(Upload::class, 'banner');
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'parent_id');
    }

    public function childrenGroups()
    {
        return $this->hasMany(Group::class, 'parent_id')->with('groups');
    }

    public function parentGroup()
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }

    protected static function booted()
    {
        $clearCache = function () {
            Artisan::call('cache:clear');
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}
