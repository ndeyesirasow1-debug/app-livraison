<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->notifications()->orderByDesc('created_at')->get(),
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification introuvable',
            ], 404);
        }

        $notification->lu = true;
        $notification->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
            'data' => $notification,
        ]);
    }
}
