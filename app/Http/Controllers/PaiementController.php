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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commande_id' => 'required|integer|exists:commandes,id',
            'mode_paiement' => 'required|in:especes,mobile_money,carte,wave,orange_money',
            'reference_paiement' => 'nullable|string|max:255',
            'numero_telephone' => 'nullable|string|max:30',
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

        if ($commande->paiement()->whereIn('statut', ['paye', 'en_attente'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Un paiement existe déjà pour cette commande'], 409);
        }

        $modePaiement = $request->mode_paiement;
        $reference = $request->input('reference_paiement');
        $statut = in_array($modePaiement, ['wave', 'orange_money'], true)
            ? 'en_attente'
            : 'paye';

        if ($modePaiement === 'especes') {
            $reference = $reference ?? 'ESPECES-' . strtoupper(str()->random(8));
        }

        if ($modePaiement === 'mobile_money' && empty($reference)) {
            $reference = 'MOBILE-' . strtoupper(str()->random(8));
        }

        $paiement = Paiement::create([
            'commande_id' => $commande->id,
            'montant' => $commande->montant_total,
            'mode_paiement' => $modePaiement,
            'statut' => $statut,
            'reference_paiement' => $reference,
            'date_paiement' => $statut === 'paye' ? now() : null,
        ]);

        $message = match ($modePaiement) {
            'wave' => 'Paiement Wave enregistré et en attente de confirmation.',
            'orange_money' => 'Paiement Orange Money enregistré et en attente de confirmation.',
            'especes' => 'Paiement en espèces validé.',
            'mobile_money' => 'Paiement mobile enregistré.',
            default => 'Paiement enregistré.',
        };

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paiement,
            'provider_status' => in_array($modePaiement, ['wave', 'orange_money'], true) ? 'awaiting_provider_confirmation' : 'local_confirmation',
        ], 201);
    }

    public function confirm(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'reference_paiement' => 'nullable|string|max:255',
            'provider_status' => 'nullable|in:approved,rejected,pending',
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

        $providerStatus = $request->input('provider_status', 'approved');
        if ($providerStatus === 'rejected') {
            $paiement->statut = 'echoue';
            $paiement->reference_paiement = $request->input('reference_paiement', $paiement->reference_paiement);
            $paiement->save();

            return response()->json([
                'success' => false,
                'message' => 'Paiement rejeté par le fournisseur',
                'data' => $paiement,
            ], 400);
        }

        $paiement->statut = $providerStatus === 'pending' ? 'en_attente' : 'paye';
        $paiement->reference_paiement = $request->input('reference_paiement', $paiement->reference_paiement);
        $paiement->date_paiement = $paiement->statut === 'paye' ? now() : null;
        $paiement->save();

        return response()->json([
            'success' => true,
            'message' => $paiement->statut === 'paye' ? 'Paiement confirmé' : 'Paiement encore en attente de confirmation',
            'data' => $paiement,
        ]);
    }
}
