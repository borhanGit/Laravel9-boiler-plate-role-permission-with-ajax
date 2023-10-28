<?php

Route::redirect('/', '/login');
Route::get('/home', function () {
    if (session('status')) {
        return redirect()->route('admin.home')->with('status', session('status'));
    }

    return redirect()->route('admin.home');
});

Auth::routes(['register' => false]);

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin', 'middleware' => ['auth']], function () {
    Route::get('/', 'HomeController@index')->name('home');
    // Permissions
    Route::resource('permissions', 'PermissionsController');
    Route::get('allPermissions', 'PermissionsController@getAll')->name('allPermissions');

    // Roles
    Route::resource('roles', 'RolesController');
    Route::get('allRoles', 'RolesController@getAll')->name('allRoles');

    // Users
    Route::resource('users', 'UsersController');
    Route::get('/allUser', 'UsersController@getAll')->name('allUser.users');

});
Route::group(['prefix' => 'profile', 'as' => 'profile.', 'namespace' => 'Auth', 'middleware' => ['auth']], function () {
    // Change password
    if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php'))) {
        Route::get('/profile-change', 'ChangePasswordController@profile')->name('profile');
        Route::get('/edit_profile', 'ChangePasswordController@edit')->name('edit');
        Route::patch('/edit_profile', 'ChangePasswordController@update')->name('update');

        Route::get('/change_password', 'ChangePasswordController@change_password')->name('password_change');
        Route::patch('/change_password', 'ChangePasswordController@update_password')->name('change_password');
    }
});
