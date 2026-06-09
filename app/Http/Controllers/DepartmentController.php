<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User;
use app\Services\AuditLogService;
class DepartmentController extends Controller
{
    public function index(){
        $departments = Department::orderBy('department_name')->paginate(10);
        return view('departments.index', compact('departments'));
    }

    public function create(){
        return view('departments.create', ['department' => new Department()]);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'department_name' => 'required|string|max:255|unique:departments,department_name',
        ]);

        $department = Department::create($validated);

        // AuditLogService::logDepartmentCreation($department);

        return redirect()->route('departments.index')->with('success', 'Department created successfully!');
    }

    public function show(Department $department){
        return view('departments.show', compact('department'));
    }

    public function edit(Department $department){
        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department){
        $validated = $request->validate([
            'department_name' => 'required|string|max:255|unique:departments,department_name,' . $department->id,
        ]);

        $department->update($validated);

        // AuditLogService::logDepartmentUpdate($department);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully!');
    }

    public function destroy(Department $department){
        $department->delete();

        // AuditLogService::logDepartmentDeletion($department);

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully!');
    }

    public function users(Department $department){
        $users = $department->users()->orderBy('name')->paginate(10);
        return view('departments.users', compact('department', 'users'));
    }

}
