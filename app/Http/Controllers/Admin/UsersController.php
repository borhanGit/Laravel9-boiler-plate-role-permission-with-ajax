<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\DataTables;

class UsersController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.users.index');
    }

    public function getAll()
   {
      
      $users = User::all();
      return Datatables::of($users)
        ->addColumn('file_path', function ($users) {
           return "<img src='" . asset($users->file_path) . "' class='img-thumbnail' width='50px'>";
        })
        ->addColumn('role', function ($user) {
           return '<label class="badge badge-secondary">' . ucfirst($user->roles->pluck('title')->implode(' , ')) . '</label>';
        })
        ->addColumn('status', function ($users) {
           return $users->status ? '<label class="badge badge-success">Active</label>' : '<label class="badge badge-danger">Inactive</label>';
        })
        ->addColumn('action', function ($user)  {
           $html = '<div class="btn-group">';
           $html .= '<a data-toggle="tooltip" '  . '  id="' . $user->id . '" class="btn btn-xs btn-info mr-1 edit" title="Edit"><i class="fa fa-edit"></i> </a>';
           $html .= '<a data-toggle="tooltip" '  . ' id="' . $user->id . '" class="btn btn-xs btn-danger mr-1 delete" title="Delete"><i class="fa fa-trash"></i> </a>';
           $html .= '</div>';
           return $html;
        })
        ->rawColumns(['action', 'file_path', 'status', 'role'])
        ->addIndexColumn()
        ->make(true);
   }

    public function create(Request $request)
    {
        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
               $roles = Role::all();
               $view = View::make('admin.users.create', compact('roles'))->render();
               return response()->json(['html' => $view]);
            
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }
    }

    public function store(StoreUserRequest $request)
    {
       

        if ($request->ajax()) {
            // Setup the validator
            $rules = [
              'name' => 'required',
              'email' => 'required|email|unique:users,email',
              'password' => 'required|same:confirm-password',
              'photo' => 'image|max:2024|mimes:jpeg,jpg,png'
            ];
   
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
               return response()->json([
                 'type' => 'error',
                 'errors' => $validator->getMessageBag()->toArray()
               ]);
            } else {
   
               $file_path = "assets/images/users/default.png";
   
               if ($request->hasFile('photo')) {
                  if ($request->file('photo')->isValid()) {
                     $destinationPath = public_path('assets/images/users/');
                     $extension = $request->file('photo')->getClientOriginalExtension();
                     $fileName = time() . '.' . $extension;
                     $file_path = 'assets/images/users/' . $fileName;
                     $request->file('photo')->move($destinationPath, $fileName);
                  } else {
                     return response()->json([
                       'type' => 'error',
                       'message' => "<div class='alert alert-warning'>Please! File is not valid</div>"
                     ]);
                  }
               }
   
               DB::beginTransaction();
               try {
                  $data = $request->all();
                  $data['file_path'] = $file_path;
                  $user = User::create($request->all());
   
                  // generate role
                  $roles = $request->input('roles');
                  if (isset($roles)) {
                     $user->roles()->sync($roles);
                  }
   
                  DB::commit();
                  return response()->json(['type' => 'success', 'message' => "Successfully Created"]);
   
               } catch (\Exception $e) {
                  DB::rollback();
                  return response()->json(['type' => 'error', 'message' => $e->getMessage()]);
               }
   
            }
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }

    }

    public function edit(Request $request,User $user)
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
       
        if ($request->ajax()) {
              $user->load('roles');
               $roles = Role::all(); //Get all roles
               $view = View::make('admin.users.edit', compact('user', 'roles'))->render();
               return response()->json(['html' => $view]);
           
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }

    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->all());
        $user->roles()->sync($request->input('roles', []));

        if ($request->ajax()) {
   
            $rules = [
              'name' => 'required',
              'email' => 'required|email|unique:users,email,' . $user->id,
              'photo' => 'image|max:2024|mimes:jpeg,jpg,png'
            ];
   
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
               return response()->json([
                 'type' => 'error',
                 'errors' => $validator->getMessageBag()->toArray()
               ]);
            } else {
   
               $file_path = $request->input('SelectedFileName');;
   
               if ($request->hasFile('photo')) {
                  if ($request->file('photo')->isValid()) {
                     $destinationPath = public_path('assets/images/users/');
                     $extension = $request->file('photo')->getClientOriginalExtension(); // getting image extension
                     $fileName = time() . '.' . $extension;
                     $file_path = 'assets/images/users/' . $fileName;
                     $request->file('photo')->move($destinationPath, $fileName);
                  } else {
                     return response()->json([
                       'type' => 'error',
                       'message' => "<div class='alert alert-warning'>Please! File is not valid</div>"
                     ]);
                  }
               }
   
               DB::beginTransaction();
               try {
                  $user->update($request->all());
   
                  $roles = $request->input('roles');
                  if (isset($roles)) {
                     $user->roles()->sync($roles);  //If one or more role is selected associate user to roles
                  } else {
                     $user->roles()->detach(); //If no role is selected remove exisiting role associated to a user
                  }
   
                  DB::commit();
                  return response()->json(['type' => 'success', 'message' => "Successfully Updated"]);
   
               } catch (\Exception $e) {
                  DB::rollback();
                  return response()->json(['type' => 'error', 'message' => $e->getMessage()]);
               }
   
            }
         } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
         }
    }

    public function show(User $user)
    {
        abort_if(Gate::denies('user_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user->load('roles');

        return view('admin.users.show', compact('user'));
    }

    public function destroy(Request $request,User $user)
    {
        abort_if(Gate::denies('user_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
            $user->delete();
            return response()->json(['type' => 'success', 'message' => "Successfully Deleted"]);
        
      } else {
         return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
      }
    }


}
