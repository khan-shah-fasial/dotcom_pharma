<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadDepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_leads'])->only('index');
        $this->middleware(['permission:add_lead'])->only(['storeCategory', 'storeDepartment']);
        $this->middleware(['permission:edit_lead'])->only(['editCategory', 'updateCategory', 'updateCategoryStatus', 'editDepartment', 'updateDepartment', 'updateDepartmentStatus']);
        $this->middleware(['permission:delete_lead'])->only(['destroyCategory', 'destroyDepartment']);
    }

    public function index()
    {
        $categories = DepartmentCategory::withCount('departments')->orderBy('name')->get();
        $departments = Department::with('category')->orderBy('name')->get();

        return view('backend.leads.masters.departments.index', compact('categories', 'departments'));
    }

    public function storeCategory(Request $request)
    {
        $data = $this->validateCategory($request);
        DepartmentCategory::create($data);

        flash(translate('Department category has been added successfully'))->success();
        return redirect()->route('lead-departments.index');
    }

    public function editCategory(DepartmentCategory $category)
    {
        return view('backend.leads.masters.departments.edit_category', compact('category'));
    }

    public function updateCategory(Request $request, DepartmentCategory $category)
    {
        $category->update($this->validateCategory($request, $category));

        flash(translate('Department category has been updated successfully'))->success();
        return redirect()->route('lead-departments.index');
    }

    public function destroyCategory(DepartmentCategory $category)
    {
        if ($category->departments()->exists()) {
            flash(translate('Department category cannot be deleted because departments exist'))->warning();
            return back();
        }

        $category->delete();

        flash(translate('Department category has been deleted successfully'))->success();
        return redirect()->route('lead-departments.index');
    }

    public function updateCategoryStatus(Request $request)
    {
        $category = DepartmentCategory::findOrFail($request->id);
        $category->status = (int) $request->status === 1 ? 1 : 0;

        return $category->save() ? 1 : 0;
    }

    public function storeDepartment(Request $request)
    {
        $data = $this->validateDepartment($request);
        Department::create($data);

        flash(translate('Department has been added successfully'))->success();
        return redirect()->route('lead-departments.index');
    }

    public function editDepartment(Department $department)
    {
        $categories = DepartmentCategory::orderBy('name')->get();

        return view('backend.leads.masters.departments.edit_department', compact('department', 'categories'));
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $department->update($this->validateDepartment($request, $department));

        flash(translate('Department has been updated successfully'))->success();
        return redirect()->route('lead-departments.index');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->leads()->exists()) {
            flash(translate('Department cannot be deleted because leads are assigned to it'))->warning();
            return back();
        }

        $department->delete();

        flash(translate('Department has been deleted successfully'))->success();
        return redirect()->route('lead-departments.index');
    }

    public function updateDepartmentStatus(Request $request)
    {
        $department = Department::findOrFail($request->id);
        $department->status = (int) $request->status === 1 ? 1 : 0;

        return $department->save() ? 1 : 0;
    }

    protected function validateCategory(Request $request, ?DepartmentCategory $category = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('department_categories', 'name')->ignore($category?->id),
            ],
            'status' => 'required|in:0,1',
        ]);

        $data['name'] = trim($data['name']);
        $data['status'] = (int) $data['status'];

        return $data;
    }

    protected function validateDepartment(Request $request, ?Department $department = null): array
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:department_categories,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where(fn ($query) => $query->where('category_id', $request->category_id))
                    ->ignore($department?->id),
            ],
            'status' => 'required|in:0,1',
        ]);

        $data['name'] = trim($data['name']);
        $data['status'] = (int) $data['status'];

        return $data;
    }
}
