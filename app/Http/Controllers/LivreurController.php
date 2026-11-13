<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LivreurController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => \App\Models\Livreur::with('user')->get()]);
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'data' => $request->user()->livreur()->first()]);
    }

    public function storeProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:150',
            'telephone' => 'required|string|max:30',
            'moyen_transport' => 'nullable|string|max:50',
            'statut' => 'nullable|in:disponible,en_livraison,inactif',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $user = $request->user();
        $livreur = $user->livreur()->firstOrNew(['user_id' => $user->id]);
        $livreur->fill($request->only(['nom', 'telephone', 'moyen_transport', 'statut', 'latitude', 'longitude']));
        $livreur->user_id = $user->id;
        $livreur->save();

        return response()->json(['success' => true, 'message' => 'Profil livreur enregistré', 'data' => $livreur]);
    }

    public function setDisponibilite(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['statut' => 'required|in:disponible,en_livraison,inactif']);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $livreur = \App\Models\Livreur::find($id);
        if (! $livreur) return response()->json(['success' => false, 'message' => 'Livreur introuvable'], 404);
        $livreur->statut = $request->statut;
        $livreur->save();

        return response()->json(['success' => true, 'message' => 'Disponibilité mise à jour', 'data' => $livreur]);
    }

    public function updatePosition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $livreur = $request->user()->livreur()->first();
        if (! $livreur) return response()->json(['success' => false, 'message' => 'Profil livreur absent'], 404);

        $hasActiveDelivery = $livreur->livraisons()->whereIn('statut', ['en_attente', 'en_cours'])->exists();
        if (! $hasActiveDelivery) {
            return response()->json(['success' => false, 'message' => 'Aucune livraison active assignée'], 403);
        }

        DB::transaction(function () use ($livreur, $request) {
            $livreur->latitude = $request->latitude;
            $livreur->longitude = $request->longitude;
            $livreur->save();

            DB::table('positions_livreurs')->insert([
                'livreur_id' => $livreur->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'date_position' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Position mise à jour',
            'data' => ['latitude' => (float) $livreur->latitude, 'longitude' => (float) $livreur->longitude],
        ]);
    }
}
