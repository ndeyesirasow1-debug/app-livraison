<?php

namespace App\Http\Controllers;

use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LivreurController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Livreur::with('user')->get(),
        ]);
    }

    public function me(Request $request)
    {
        $livreur = $request->user()->livreur()->first();

        return response()->json([
            'success' => true,
            'data' => $livreur,
        ]);
    }

    public function storeProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:150',
            'telephone' => 'required|string|max:30',
            'moyen_transport' => 'nullable|string|max:50',
            'statut' => 'nullable|in:disponible,en_livraison,inactif',
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
        $livreur = $user->livreur()->firstOrNew(['user_id' => $user->id]);
        $livreur->fill($request->only([
            'nom',
            'telephone',
            'moyen_transport',
            'statut',
            'latitude',
            'longitude',
        ]));
        $livreur->user_id = $user->id;
        $livreur->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil livreur enregistré',
            'data' => $livreur,
        ]);
    }

    public function setDisponibilite(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:disponible,en_livraison,inactif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $livreur = Livreur::find($id);

        if (! $livreur) {
            return response()->json([
                'success' => false,
                'message' => 'Livreur introuvable',
            ], 404);
        }

        $livreur->statut = $request->statut;
        $livreur->save();

        return response()->json([
            'success' => true,
            'message' => 'Disponibilité mise à jour',
            'data' => $livreur,
        ]);
    }

    public function updatePosition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $livreur = $request->user()->livreur()->first();

        if (! $livreur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil livreur absent',
            ], 404);
        }

        $livreur->latitude = $request->latitude;
        $livreur->longitude = $request->longitude;
        $livreur->save();

        return response()->json([
            'success' => true,
            'message' => 'Position mise à jour',
            'data' => $livreur,
        ]);
    }
}
