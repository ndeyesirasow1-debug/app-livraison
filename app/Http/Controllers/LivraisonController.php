<?php

namespace App\Http\Controllers;

use App\Models\Livraison;
use App\Models\Notification;
use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LivraisonController extends Controller
{
    public function index(Request $request)
    {
        $query = Livraison::with(['commande', 'livreur']);
        $user = $request->user();

        if ($user->role === 'livreur') {
            $query->where('livreur_id', optional($user->livreur)->id);
        } elseif ($user->role === 'client') {
            $query->whereHas('commande.client', fn ($q) => $q->where('user_id', $user->id));
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
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $livraison = Livraison::find($id);
        if (! $livraison) {
            return response()->json(['success' => false, 'message' => 'Livraison introuvable'], 404);
        }

        $livraison->livreur_id = $request->integer('livreur_id');
        $livraison->statut = 'en_cours';
        $livraison->date_depart ??= now();
        $livraison->save();

        return response()->json(['success' => true, 'message' => 'Livraison assignée', 'data' => $livraison]);
    }

    /** Client's tracking endpoint; clients can only see their own order. */
    public function track(Request $request, int $commandeId)
    {
        $query = Livraison::with(['commande.client', 'livreur']);
        if ($request->user()->role === 'client') {
            $query->whereHas('commande.client', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        $livraison = $query->where('commande_id', $commandeId)->first();
        if (! $livraison) {
            return response()->json(['success' => false, 'message' => 'Suivi introuvable'], 404);
        }

        $position = $livraison->livreur_id
            ? \Illuminate\Support\Facades\DB::table('positions_livreurs')
                ->where('livreur_id', $livraison->livreur_id)
                ->orderByDesc('date_position')
                ->first()
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'commande_id' => $livraison->commande_id,
                'statut' => $livraison->statut,
                'date_depart' => $livraison->date_depart,
                'date_livraison' => $livraison->date_livraison,
                'commentaire' => $livraison->commentaire,
                'livreur' => $livraison->livreur ? [
                    'nom' => $livraison->livreur->nom,
                    'telephone' => $livraison->livreur->telephone,
                ] : null,
                'position' => $position ? [
                    'latitude' => (float) $position->latitude,
                    'longitude' => (float) $position->longitude,
                    'date_position' => $position->date_position,
                ] : ($livraison->livreur && $livraison->livreur->latitude !== null ? [
                    'latitude' => (float) $livraison->livreur->latitude,
                    'longitude' => (float) $livraison->livreur->longitude,
                    'date_position' => $livraison->livreur->updated_at,
                ] : null),
                'actualisation' => 'interrogation périodique de l’API; websocket non configuré',
            ],
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:en_attente,en_cours,livree,echec',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $livraison = Livraison::with('commande.client.user')->find($id);
        if (! $livraison) {
            return response()->json(['success' => false, 'message' => 'Livraison introuvable'], 404);
        }

        $user = $request->user();
        $isAdmin = in_array($user->role, ['admin', 'gestionnaire'], true);
        $isAssignedDriver = $user->role === 'livreur'
            && $user->livreur
            && $livraison->livreur_id === $user->livreur->id;

        if (! $isAdmin && ! $isAssignedDriver) {
            return response()->json(['success' => false, 'message' => 'Accès interdit'], 403);
        }

        $previousStatus = $livraison->statut;
        $livraison->statut = $request->statut;
        $livraison->commentaire = $request->commentaire ?? $livraison->commentaire;
        if ($request->statut === 'livree') {
            $livraison->date_livraison = now();
        }
        $livraison->save();

        $clientUser = $livraison->commande?->client?->user;
        if ($clientUser && $previousStatus !== $livraison->statut) {
            Notification::create([
                'user_id' => $clientUser->id,
                'type' => 'livraison',
                'titre' => 'Statut de livraison',
                'message' => 'La livraison est maintenant : '.$livraison->statut,
                'lu' => false,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Statut de livraison mis à jour', 'data' => $livraison]);
    }
}
