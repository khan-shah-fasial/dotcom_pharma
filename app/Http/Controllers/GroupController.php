<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTranslation;
use App\Models\Product;
use App\Utility\GroupUtility;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function __construct()
    {
        // Reuse category permissions for managing groups
        $this->middleware(['permission:view_product_categories'])->only('index');
        $this->middleware(['permission:add_product_category'])->only('create');
        $this->middleware(['permission:edit_product_category'])->only('edit');
        $this->middleware(['permission:delete_product_category'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $groups = Group::orderBy('order_level', 'desc');
        if ($request->has('search')) {
            $sort_search = $request->search;
            $groups = $groups->where('name', 'like', '%' . $sort_search . '%');
        }
        $groups = $groups->paginate(15);
        return view('backend.product.groups.index', compact('groups', 'sort_search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $groups = Group::where('parent_id', 0)
            ->where('digital', 0)
            ->with('childrenGroups')
            ->get();

        return view('backend.product.groups.create', compact('groups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $group = new Group;
        $group->name = $request->name;
        $group->order_level = 0;
        if ($request->order_level != null) {
            $group->order_level = $request->order_level;
        }
        $group->digital = $request->digital;
        $group->banner = $request->banner;
        $group->icon = $request->icon;
        $group->cover_image = $request->cover_image;
        $group->meta_title = $request->meta_title;
        $group->meta_description = $request->meta_description;

        if ($request->parent_id != "0") {
            $group->parent_id = $request->parent_id;

            $parent = Group::find($request->parent_id);
            $group->level = $parent->level + 1;
        }

        if ($request->slug != null) {
            $group->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
        } else {
            $group->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5);
        }
        if ($request->commision_rate != null) {
            $group->commision_rate = $request->commision_rate;
        }

        $group->save();

        $group_translation = GroupTranslation::firstOrNew(['lang' => env('DEFAULT_LANGUAGE'), 'group_id' => $group->id]);
        $group_translation->name = $request->name;
        $group_translation->save();

        flash(translate('Group has been inserted successfully'))->success();
        return redirect()->route('groups.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $lang = $request->lang;
        $group = Group::findOrFail($id);
        $groups = Group::where('parent_id', 0)
            ->where('digital', $group->digital)
            ->with(['childrenGroups' => function ($query) use ($group) {
                $query->whereNotIn('id', GroupUtility::children_ids($group->id, true))
                    ->where('id', '!=', $group->id);
            }])
            ->orderBy('name', 'asc')
            ->get();

        return view('backend.product.groups.edit', compact('group', 'groups', 'lang'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        if ($request->lang == env("DEFAULT_LANGUAGE")) {
            $group->name = $request->name;
        }
        if ($request->order_level != null) {
            $group->order_level = $request->order_level;
        }
        $group->digital = $request->digital;
        $group->banner = $request->banner;
        $group->icon = $request->icon;
        $group->cover_image = $request->cover_image;
        $group->meta_title = $request->meta_title;
        $group->meta_description = $request->meta_description;

        $previous_level = $group->level;

        if ($request->parent_id != "0") {
            $group->parent_id = $request->parent_id;

            $parent = Group::find($request->parent_id);
            $group->level = $parent->level + 1;
        } else {
            $group->parent_id = 0;
            $group->level = 0;
        }

        if ($request->slug != null) {
            $group->slug = strtolower($request->slug);
        } else {
            $group->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5);
        }

        if ($request->commision_rate != null) {
            $group->commision_rate = $request->commision_rate;
        }

        $group->save();

        GroupUtility::update_child_level($group->id);

        $group_translation = GroupTranslation::firstOrNew(['lang' => $request->lang, 'group_id' => $group->id]);
        $group_translation->name = $request->name;
        $group_translation->save();

        Cache::forget('featured_groups');
        flash(translate('Group has been updated successfully'))->success();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        foreach ($group->group_translations as $group_translation) {
            $group_translation->delete();
        }

        foreach (Product::where('group_id', $group->id)->get() as $product) {
            $product->group_id = null;
            $product->save();
        }

        GroupUtility::delete_group($id);
        Cache::forget('featured_groups');

        flash(translate('Group has been deleted successfully'))->success();
        return redirect()->route('groups.index');
    }

    public function updateFeatured(Request $request)
    {
        $group = Group::findOrFail($request->id);
        $group->featured = $request->status;
        $group->save();
        Cache::forget('featured_groups');
        return 1;
    }

    public function groupsByType(Request $request)
    {
        $groups = Group::where('parent_id', 0)
            ->where('digital', $request->digital)
            ->with('childrenGroups')
            ->get();

        return view('backend.product.groups.groups_option', compact('groups'));
    }
}
