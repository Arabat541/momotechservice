<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $role   = session('user_role', 'caissiere');
        $shopId = session('current_shop_id');
        $type   = $request->query('type');

        $query = Notification::orderByDesc('created_at');

        if ($role !== 'patron') {
            $query->forShop($shopId)->forRole('caissiere');
        }

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate(20)->withQueryString();

        // $notifCount doit être passé explicitement : le View Composer l'injecte
        // dans layouts.dashboard mais les @section enfants sont évalués avant
        // que le composer ne s'exécute.
        $unreadQuery = Notification::unread();
        if ($role !== 'patron') {
            $unreadQuery->forShop($shopId)->forRole('caissiere');
        }
        $notifCount = $unreadQuery->count();

        return view('notifications.index', compact('notifications', 'type', 'notifCount'));
    }

    public function markRead(Request $request, Notification $notification)
    {
        $role   = session('user_role', 'caissiere');
        $shopId = session('current_shop_id');
        if ($role !== 'patron' && $notification->shop_id !== $shopId) {
            abort(403);
        }

        $notification->update(['lu_at' => now()]);
        Cache::forget('notifications_' . session('user_id'));

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function markAllRead(Request $request)
    {
        $role   = session('user_role', 'caissiere');
        $shopId = session('current_shop_id');

        $query = Notification::unread();

        if ($role !== 'patron') {
            $query->forShop($shopId)->forRole('caissiere');
        }

        $query->update(['lu_at' => now()]);
        Cache::forget('notifications_' . session('user_id'));

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    public function destroy(Request $request, Notification $notification)
    {
        $role   = session('user_role', 'caissiere');
        $shopId = session('current_shop_id');
        if ($role !== 'patron' && $notification->shop_id !== $shopId) {
            abort(403);
        }

        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }
}
