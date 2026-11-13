<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Repas', 'description' => 'Meals et plats rapides'],
            ['nom' => 'Boissons', 'description' => 'Boissons fraîches et énergisantes'],
            ['nom' => 'Desserts', 'description' => 'Petits desserts sucrés'],
        ];

        foreach ($categories as $categorieData) {
            $categorie = Categorie::firstOrCreate($categorieData);

            if ($categorie->nom === 'Repas') {
                Produit::firstOrCreate([
                    'nom' => 'Poulet braisé',
                ], [
                    'categorie_id' => $categorie->id,
                    'description' => 'Poulet braisé avec accompagnement',
                    'prix' => 4500,
                    'stock' => 25,
                    'statut' => 'actif',
                ]);
            }

            if ($categorie->nom === 'Boissons') {
                Produit::firstOrCreate([
                    'nom' => 'Jus frais',
                ], [
                    'categorie_id' => $categorie->id,
                    'description' => 'Jus naturel du jour',
                    'prix' => 1200,
                    'stock' => 50,
                    'statut' => 'actif',
                ]);
            }

            if ($categorie->nom === 'Desserts') {
                Produit::firstOrCreate([
                    'nom' => 'Beignet',
                ], [
                    'categorie_id' => $categorie->id,
                    'description' => 'Beignet doux et croustillant',
                    'prix' => 1000,
                    'stock' => 30,
                    'statut' => 'actif',
                ]);
            }
        }
    }
}
