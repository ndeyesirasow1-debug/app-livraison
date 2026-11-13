<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Produit> */
class ProduitFactory extends Factory
{
    protected $model = \App\Models\Produit::class;

    public function definition(): array
    {
        return [
            'categorie_id' => null,
            'nom' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'prix' => fake()->numberBetween(500, 10000),
            'stock' => fake()->numberBetween(1, 100),
            'statut' => 'actif',
            'image_url' => null,
        ];
    }
}
