<?php

namespace App\Utility;

use App\Models\Group;

class GroupUtility
{
    public static function get_immediate_children($id, $with_trashed = false, $as_array = false)
    {
        $children = $with_trashed ? Group::where('parent_id', $id)->orderBy('order_level', 'desc')->get() : Group::where('parent_id', $id)->orderBy('order_level', 'desc')->get();
        $children = $as_array && !is_null($children) ? $children->toArray() : $children;

        return $children;
    }

    public static function get_immediate_children_ids($id, $with_trashed = false)
    {
        $children = GroupUtility::get_immediate_children($id, $with_trashed, true);

        return !empty($children) ? array_column($children, 'id') : array();
    }

    public static function get_immediate_children_count($id, $with_trashed = false)
    {
        return $with_trashed ? Group::where('parent_id', $id)->count() : Group::where('parent_id', $id)->count();
    }

    public static function flat_children($id, $with_trashed = false, $container = array())
    {
        $children = GroupUtility::get_immediate_children($id, $with_trashed, true);

        if (!empty($children)) {
            foreach ($children as $child) {
                $container[] = $child;
                $container = GroupUtility::flat_children($child['id'], $with_trashed, $container);
            }
        }

        return $container;
    }

    public static function children_ids($id, $with_trashed = false)
    {
        $children = GroupUtility::flat_children($id, $with_trashed = false);

        return !empty($children) ? array_column($children, 'id') : array();
    }

    public static function group_tree_ids($group, $group_ids)
    {
        foreach ($group->childrenGroups as $group) {
            $group_ids[] = $group->id;

            if (count($group->childrenGroups) > 0) {
                $group_ids = static::group_tree_ids($group, $group_ids);
            }
        }
        return $group_ids;
    }

    public static function move_children_to_parent($id)
    {
        $children_ids = GroupUtility::get_immediate_children_ids($id, true);

        $group = Group::where('id', $id)->first();

        GroupUtility::move_level_up($id);

        Group::whereIn('id', $children_ids)->update(['parent_id' => $group->parent_id]);
    }

    public static function move_level_up($id)
    {
        if (GroupUtility::get_immediate_children_ids($id, true) > 0) {
            foreach (GroupUtility::get_immediate_children_ids($id, true) as $value) {
                $group = Group::find($value);
                $group->level -= 1;
                $group->save();
                return GroupUtility::move_level_up($value);
            }
        }
    }

    public static function move_level_down($id)
    {
        if (GroupUtility::get_immediate_children_ids($id, true) > 0) {
            foreach (GroupUtility::get_immediate_children_ids($id, true) as $value) {
                $group = Group::find($value);
                $group->level += 1;
                $group->save();
                return GroupUtility::move_level_down($value);
            }
        }
    }

    public static function update_child_level($id)
    {
        $get_immediate_children_ids = GroupUtility::get_immediate_children_ids($id, true);
        if (count($get_immediate_children_ids) > 0) {
            $parent_group = Group::find($id);
            foreach ($get_immediate_children_ids as $value) {
                $group = Group::find($value);
                $group->level = $parent_group->level + 1;
                $group->save();
                GroupUtility::update_child_level($value);
            }
        }
    }

    public static function delete_group($id)
    {
        $group = Group::where('id', $id)->first();
        if (!is_null($group)) {
            GroupUtility::move_children_to_parent($group->id);
            $group->delete();
        }
    }
}
