<?php

namespace App\Http\Controllers;

use App\Models\Livraison;
use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LivraisonController extends Controller
{
    public function index(Request $request)
    {
        $query = Livraison::query();

        if ($request->has('livreur_id')) {
            $query->where('livreur_id', $request->livreur_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->with(['commande', 'livreur'])->get(),
        ]);
    }

    public function assign(Request $request, $id)
    {
        $livraison = Livraison::find($id);

        if (! $livraison) {
            return response()->json([
                'success' => false,
                'message' => 'Livraison introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'livreur_id' => 'required|exists:livreurs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $livreur = Livreur::find($request->livreur_id);

        if ($livreur && $livreur->statut === 'inactif') {
            return response()->json([
                'success' => false,
                'message' => 'Livreur indisponible',
            ], 400);
        }

        $livraison->livreur_id = $request->livreur_id;
        $livraison->statut = 'en_cours';
        $livraison->save();

        return response()->json([
            'success' => true,
            'message' => 'Livraison assignée',
            'data' => $livraison,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $livraison = Livraison::find($id);

        if (! $livraison) {
            return response()->json([
                'success' => false,
                'message' => 'Livraison introuvable',
            ], 404);
        }

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

        $livraison->statut = $request->statut;
        $livraison->commentaire = $request->commentaire ?? $livraison->commentaire;

        if ($request->statut === 'livree') {
            $livraison->date_livraison = now();
        }

        $livraison->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut de livraison mis à jour',
            'data' => $livraison,
        ]);
    }
}
