<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        $client = $user->client()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'client' => $client,
            ],
        ]);
    }

    public function storeProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:150',
            'telephone' => 'required|string|max:30',
            'email' => 'nullable|email',
            'adresse' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $client = $user->client()->firstOrNew(['user_id' => $user->id]);
        $client->fill($request->only([
            'nom',
            'telephone',
            'email',
            'adresse',
            'latitude',
            'longitude',
        ]));
        $client->user_id = $user->id;
        $client->save();

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil client enregistré',
            'data' => $client,
        ]);
    }
}
