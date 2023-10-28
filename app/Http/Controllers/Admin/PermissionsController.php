<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyPermissionRequest;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\DataTables;

class PermissionsController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('permission_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        
        return view('admin.permissions.index' );
    }

    public function getAll()
    {
 
       $permissions = Permission::all();
       return DataTables::of($permissions)
         ->addColumn('action', function ($permissions)  {
            $html = '<div class="btn-group">';
            $html .= '<a data-toggle="tooltip" ' . '  id="' . $permissions->id . '" class="btn btn-xs btn-info mr-1 edit" title="Edit"><i class="fa fa-edit"></i> </a>';
             $html .= '<a data-toggle="tooltip" '  . ' id="' . $permissions->id . '" class="btn btn-xs btn-danger mr-1 delete" title="Delete"><i class="fa fa-trash"></i> </a>';
            $html .= '</div>';
            return $html;
         })
         ->rawColumns(['action'])
         ->addIndexColumn()
         ->make(true);
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('permission_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
               $view = View::make('admin.permissions.create')->render();
               return response()->json(['html' => $view]);
           
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }

        return view('admin.permissions.create');
    }

    public function store(StorePermissionRequest $request)
    {
        if ($request->ajax()) {
            // Setup the validator
            $rules = [
              'title' => 'required|unique:permissions'
            ];
   
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
               return response()->json([
                 'type' => 'error',
                 'errors' => $validator->getMessageBag()->toArray()
               ]);
            } else {
               Permission::Create($request->all());
               return response()->json(['type' => 'success', 'message' => "Successfully Created"]);
            }
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }

    }

    public function edit(Request $request,Permission $permission)
    {
        abort_if(Gate::denies('permission_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
               $view = View::make('admin.permissions.edit', compact('permission'))->render();
               return response()->json(['html' => $view]);
            
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        if ($request->ajax()) {
            // Setup the validator
            Permission::findOrFail($permission->id);
   
            $rules = [
              'title' => 'required|unique:permissions,title,' . $permission->id
            ];
   
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
               return response()->json([
                 'type' => 'error',
                 'errors' => $validator->getMessageBag()->toArray()
               ]);
            } else {
               $permission->title = $request->title;
               $permission->save();
               return response()->json(['type' => 'success', 'message' => "Successfully Updated"]);
            }
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }
    }

    public function show(Permission $permission)
    {
        abort_if(Gate::denies('permission_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.permissions.show', compact('permission'));
    }

    public function destroy(Request $request,Permission $permission)
    {
        abort_if(Gate::denies('permission_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
               $permission->delete();
               return response()->json(['type' => 'success', 'message' => "Successfully Deleted"]);            
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }
    }

    
}
