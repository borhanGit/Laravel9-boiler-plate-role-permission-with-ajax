<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyRoleRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('role_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return view('admin.roles.index');
    }
    public function getAll(Request $request)
    {
      if ($request->ajax()) {

         $role = Role::get();
         return Datatables::of($role)
           ->addColumn('action', function ($role)  {
              if ($role->title != 'Admin') {
                 $html = '<div class="btn-group">';
                 $html .= '<a data-toggle="tooltip" '  . '  id="' . $role->id . '" class="btn btn-xs btn-primary mr-1 edit" title="Edit"><i class="fa fa-edit"></i> </a>';
                 $html .= '<a data-toggle="tooltip" '  . ' id="' . $role->id . '" class="btn btn-xs btn-danger mr-1 delete" title="Delete"><i class="fa fa-trash"></i> </a>';
                 $html .= '</div>';
                 return $html;
              }
              return "<a class='btn btn-danger'>Disabled</a>";
           })
           ->rawColumns(['action'])
           ->addIndexColumn()
           ->make(true);
      } else {
         return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
      }
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('role_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
               $permission = Permission::get();
               $view = View::make('admin.roles.create', compact('permission'))->render();
               return response()->json(['html' => $view]);
            
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }
    }

    public function store(StoreRoleRequest $request)
    {
        if ($request->ajax()) {
            // Setup the validator
            $rules = [
              'title' => 'required|unique:roles',
              'permissions' => 'required',
            ];
   
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
               return response()->json([
                 'type' => 'error',
                 'errors' => $validator->getMessageBag()->toArray()
               ]);
            } else {
               $role = Role::create(['title' => $request->input('title')]);
               $permissions = explode(",", $request->input('permissions'));
               $role->permissions()->sync($permissions,);
               return response()->json(['type' => 'success', 'message' => "Successfully Created"]);
            }
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }    
    }

    public function edit(Request $request,Role $role)
    {
        abort_if(Gate::denies('role_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
               $permissions = Permission::all();
               $view = View::make('admin.roles.edit', compact('role', 'permissions'))->render();
               return response()->json(['html' => $view]);
            
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        if ($request->ajax()) {
            // Setup the validator
            $rules = [
              'title' => 'required|unique:roles,title,' . $role->id,
              'permissions' => 'required',
            ];
   
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
               return response()->json([
                 'type' => 'error',
                 'errors' => $validator->getMessageBag()->toArray()
               ]);
            } else {
   
                $role->update($request->all());
                $permissions = $request->input('permissions');
              
   
               if (isset($permissions)) {
                  //If one or more role is selected associate user to roles
                  $role->permissions()->sync($permissions);
               } else {
                  //If no role is selected remove exisiting permissions associated to a role
                  $p_all = Permission::all();//Get all permissions
                  foreach ($p_all as $p) {
                     $role->revokePermissionTo($p); //Remove all permissions associated with role
                  }
               }
               return response()->json(['type' => 'success', 'message' => "Successfully Created"]);
            }
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         } 

    }

    public function show(Role $role)
    {
        abort_if(Gate::denies('role_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role->load('permissions');

        return view('admin.roles.show', compact('role'));
    }

    public function destroy(Request $request,Role $role)
    {
        abort_if(Gate::denies('role_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
               $role->delete();
               return response()->json(['type' => 'success', 'message' => "Successfully Deleted"]);
           
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }
       

        
    }

    
}
