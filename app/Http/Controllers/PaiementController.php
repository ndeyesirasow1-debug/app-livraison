<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaiementController extends Controller
{
    public function index(Request $request)
    {
        $query = Paiement::with('commande');

        if ($request->user()->role === 'client') {
            $query->whereHas('commande.client', fn ($clientQuery) =>
                $clientQuery->where('user_id', $request->user()->id)
            );
        }

        if ($request->filled('commande_id')) {
            $query->where('commande_id', $request->integer('commande_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    /** Create a payment attempt. This does not claim that a provider charged the customer. */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commande_id' => 'required|integer|exists:commandes,id',
            'mode_paiement' => 'required|in:especes,mobile_money,carte,autre',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $commande = \App\Models\Commande::with('client')->findOrFail($request->integer('commande_id'));
        $user = $request->user();

        if ($user->role === 'client' && $commande->client?->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Commande introuvable'], 404);
        }

        if ($commande->paiement()->where('statut', 'paye')->exists()) {
            return response()->json(['success' => false, 'message' => 'Cette commande est déjà payée'], 409);
        }

        $paiement = Paiement::create([
            'commande_id' => $commande->id,
            'montant' => $commande->montant_total,
            'mode_paiement' => $request->mode_paiement,
            'statut' => 'en_attente',
            'reference_paiement' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de paiement enregistrée. Le paiement reste en attente de confirmation.',
            'data' => $paiement,
            'provider_configured' => false,
        ], 201);
    }

    /** Admin/manager calls this only after verifying payment with the actual provider or receipt. */
    public function confirm(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'reference_paiement' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $paiement = Paiement::find($id);
        if (! $paiement) {
            return response()->json(['success' => false, 'message' => 'Paiement introuvable'], 404);
        }

        if ($paiement->statut !== 'en_attente') {
            return response()->json(['success' => false, 'message' => 'Ce paiement n’est plus en attente'], 409);
        }

        $paiement->statut = 'paye';
        $paiement->reference_paiement = $request->input('reference_paiement');
        $paiement->date_paiement = now();
        $paiement->save();

        return response()->json([
            'success' => true,
            'message' => 'Paiement confirmé après vérification',
            'data' => $paiement,
        ]);
    }
}
