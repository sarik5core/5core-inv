<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    // public function boot(): void
    // {
    //     View::composer('*', function ($view) {
    //         $permissions = [];
    //         if (Auth::check()) {
    //             $permissions = Permission::where('user_id', Auth::id())->value('permissions') ?? [];
    //         }
    //         $view->with('permissions', $permissions);
    //     });
    // }

        public function boot()
        {
            Blade::if('canDo', function ($permissionKey, $action = 'can_view') {
                return auth()->check() && auth()->user()->hasPermission($permissionKey, $action);
            });
        }


}
