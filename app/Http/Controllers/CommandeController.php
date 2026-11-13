<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\Livraison;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommandeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Commande::with(['client', 'details', 'livraison', 'paiement']);

        if ($user->role === 'client') {
            $query->where('client_id', optional($user->client)->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'adresse_livraison' => 'required|string',
            'telephone_destinataire' => 'nullable|string|max:30',
            'items' => 'required|array|min:1',
            'items.*.produit_id' => 'required|exists:produits,id',
            'items.*.quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $client = $user->client()->first();

        if (! $client) {
            return response()->json([
                'success' => false,
                'message' => 'Profil client introuvable',
            ], 404);
        }

        $commande = Commande::create([
            'client_id' => $client->id,
            'adresse_livraison' => $request->adresse_livraison,
            'telephone_destinataire' => $request->telephone_destinataire,
            'frais_livraison' => 500,
            'montant_total' => 0,
            'statut' => 'en_attente',
        ]);

        $total = 0;
        foreach ($request->items as $item) {
            $produit = \App\Models\Produit::find($item['produit_id']);
            $qte = (int) $item['quantite'];
            $sousTotal = $produit->prix * $qte;
            $total += $sousTotal;

            DetailCommande::create([
                'commande_id' => $commande->id,
                'produit_id' => $produit->id,
                'nom_produit' => $produit->nom,
                'quantite' => $qte,
                'prix_unitaire' => $produit->prix,
                'sous_total' => $sousTotal,
            ]);
        }

        $commande->montant_total = $total + $commande->frais_livraison;
        $commande->save();

        Livraison::create([
            'commande_id' => $commande->id,
            'statut' => 'en_attente',
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'commande',
            'titre' => 'Commande créée',
            'message' => 'Votre commande a bien été enregistrée.',
            'lu' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commande créée',
            'data' => $commande->load(['details', 'livraison', 'paiement']),
        ], 201);
    }
}
