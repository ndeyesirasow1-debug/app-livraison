<?php

namespace App\Http\Controllers;

use App\Models\Livraison;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LivraisonController extends Controller
{
    public function index(Request $request)
    {
        $query = Livraison::with(['commande', 'livreur']);

        if ($request->user()->role === 'livreur') {
            $query->where('livreur_id', optional($request->user()->livreur)->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    public function assign(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'livreur_id' => 'required|exists:livreurs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $livraison = Livraison::find($id);
        if (! $livraison) {
            return response()->json([
                'success' => false,
                'message' => 'Livraison introuvable',
            ], 404);
        }

        $livraison->livreur_id = $request->livreur_id;
        $livraison->statut = 'en_cours';
        $livraison->date_depart = now();
        $livraison->save();

        return response()->json([
            'success' => true,
            'message' => 'Livraison assignée',
            'data' => $livraison,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:en_attente,en_cours,livree,echec',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $livraison = Livraison::find($id);
        if (! $livraison) {
            return response()->json([
                'success' => false,
                'message' => 'Livraison introuvable',
            ], 404);
        }

        $livraison->statut = $request->statut;
        $livraison->commentaire = $request->commentaire ?? $livraison->commentaire;

        if ($request->statut === 'livree') {
            $livraison->date_livraison = now();
        }

        $livraison->save();

        if ($livraison->commande && $livraison->commande->client && $livraison->commande->client->user) {
            Notification::create([
                'user_id' => $livraison->commande->client->user->id,
                'type' => 'livraison',
                'titre' => 'Statut de livraison',
                'message' => 'La livraison est maintenant : ' . $request->statut,
                'lu' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut de livraison mis à jour',
            'data' => $livraison,
        ]);
    }
}
