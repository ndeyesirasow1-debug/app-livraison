<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommandeController extends Controller
{
    public function index(Request $request)
    {
        $query = Commande::query();

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json([
            'success' => true,
            'data' => $query->with(['details', 'livraison', 'paiement'])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'adresse_livraison' => 'required|string',
            'details' => 'required|array|min:1',
            'details.*.produit_id' => 'required|exists:produits,id',
            'details.*.quantite' => 'required|integer|min:1',
            'details.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $montantTotal = 0;

            foreach ($request->details as $item) {
                $produit = Produit::findOrFail($item['produit_id']);

                if ($produit->stock < $item['quantite']) {
                    throw new \Exception('Stock insuffisant pour '.$produit->nom);
                }

                $montantTotal += $item['quantite'] * $item['prix_unitaire'];
            }

            $commande = Commande::create([
                'client_id' => $request->client_id,
                'adresse_livraison' => $request->adresse_livraison,
                'telephone_destinataire' => $request->telephone_destinataire,
                'frais_livraison' => $request->frais_livraison ?? 0,
                'montant_total' => $montantTotal + ($request->frais_livraison ?? 0),
                'statut' => 'en_attente',
            ]);

            foreach ($request->details as $item) {
                $produit = Produit::findOrFail($item['produit_id']);

                DetailCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $produit->id,
                    'nom_produit' => $produit->nom,
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'sous_total' => $item['quantite'] * $item['prix_unitaire'],
                ]);

                $produit->stock -= $item['quantite'];
                $produit->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande créée',
                'data' => $commande->load('details'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
