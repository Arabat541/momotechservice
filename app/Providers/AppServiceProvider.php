<?php

namespace App\Providers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.dashboard', function ($view) {
            $userId = session('user_id');

            if (!$userId) {
                $view->with('notifications', collect());
                $view->with('notifCount', 0);
                return;
            }

            $user   = User::find($userId);
            $shopId = session('current_shop_id');

            if (!$user) {
                $view->with('notifications', collect());
                $view->with('notifCount', 0);
                return;
            }

            $service       = app(NotificationService::class);
            $allNotifs     = $service->getNotificationsForUser($user, $shopId);
            $notifCount    = $allNotifs->count();
            $notifications = $allNotifs->take(5);

            $view->with(compact('notifications', 'notifCount'));
        });
    }
}
