<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Notification;

class NotificationOwnership
{
    public function handle($request, Closure $next)
    {
        $notificationId = $request->route('id');
        $user = $request->user();
        
        $notification = Notification::find($notificationId);
        
        if (!$notification || $notification->user_id !== $user->id) {
            return response()->json(['message' => 'Notification non trouvée'], 404);
        }
        
        return $next($request);
    }
}