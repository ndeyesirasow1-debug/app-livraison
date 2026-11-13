<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaiementController extends Controller
{
    public function index(Request $request)
    {
        $query = Paiement::query();

        if ($request->has('commande_id')) {
            $query->where('commande_id', $request->commande_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commande_id' => 'required|exists:commandes,id',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:especes,mobile_money,carte,autre',
            'reference_paiement' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $paiement = Paiement::create([
            'commande_id' => $request->commande_id,
            'montant' => $request->montant,
            'mode_paiement' => $request->mode_paiement,
            'statut' => 'paye',
            'reference_paiement' => $request->reference_paiement,
            'date_paiement' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré',
            'data' => $paiement,
        ], 201);
    }
}
