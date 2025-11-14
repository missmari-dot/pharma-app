<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        return $this->notificationService->notificationsUtilisateur($request->user());
    }

    public function marquerCommeLu($id, Request $request)
    {
        return $this->notificationService->marquerCommeLu($id, $request->user());
    }

    public function toutMarquerCommeLu(Request $request)
    {
        return $this->notificationService->toutMarquerCommeLu($request->user());
    }

    public function nonLues(Request $request)
    {
        return $this->notificationService->notificationsNonLues($request->user());
    }

    public function compter(Request $request)
    {
        return response()->json([
            'count' => $this->notificationService->compterNonLues($request->user())
        ]);
    }
}