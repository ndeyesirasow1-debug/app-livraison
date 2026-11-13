<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        $query = Produit::query();

        if ($request->has('search')) {
            $query->where('nom', 'like', '%'.$request->search.'%');
        }

        if ($request->has('categorie')) {
            $query->where('categorie_id', $request->categorie);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(15),
        ]);
    }

    public function show($id)
    {
        $produit = Produit::find($id);

        if (! $produit) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $produit,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categorie_id' => 'nullable|exists:categories,id',
            'nom' => 'required|string|max:150',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'statut' => 'nullable|in:actif,inactif,rupture',
            'image_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $produit = Produit::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Produit créé',
            'data' => $produit,
        ], 201);
    }
}
