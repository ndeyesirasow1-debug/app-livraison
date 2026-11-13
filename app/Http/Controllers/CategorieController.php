<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategorieController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Categorie::withCount('produits')->get(),
        ]);
    }

    public function show($id)
    {
        $categorie = Categorie::with('produits')->find($id);

        if (! $categorie) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $categorie,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $categorie = Categorie::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée',
            'data' => $categorie,
        ], 201);
    }
}
