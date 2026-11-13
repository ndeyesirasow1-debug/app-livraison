<?php

namespace Tests\Feature;

use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryApiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_login_load_products_and_create_an_order(): void
    {
        $user = User::factory()->create([
            'email' => 'client@example.test',
            'password' => bcrypt('secret123'),
            'role' => 'client',
        ]);

        $product = Produit::factory()->create([
            'nom' => 'Poulet braisé',
            'prix' => 4500,
            'stock' => 10,
            'statut' => 'actif',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk()->assertJsonPath('success', true);

        $token = $login->json('token');
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        $api = $this->withToken($token);

        $api->postJson('/api/clients/profile', [
            'nom' => 'Client Test',
            'telephone' => '650000000',
            'adresse' => 'Douala, Bonanjo',
        ])->assertOk()->assertJsonPath('success', true);

        $api->getJson('/api/produits')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.nom', 'Poulet braisé');

        $response = $api->postJson('/api/commandes', [
            'adresse_livraison' => 'Douala, Bonanjo',
            'telephone_destinataire' => '650000000',
            'items' => [[
                'produit_id' => $product->id,
                'quantite' => 2,
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statut', 'en_attente')
            ->assertJsonPath('data.details.0.produit_id', $product->id)
            ->assertJsonPath('data.details.0.quantite', 2)
            ->assertJsonPath('data.montant_total', '9500.00');

        $this->assertDatabaseHas('commandes', [
            'client_id' => $user->fresh()->client->id,
            'montant_total' => 9500,
        ]);
        $this->assertDatabaseHas('livraisons', [
            'commande_id' => $response->json('data.id'),
            'statut' => 'en_attente',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'commande',
        ]);
    }

    public function test_product_catalog_requires_authentication(): void
    {
        $this->getJson('/api/produits')->assertUnauthorized();
    }

    public function test_order_validation_rejects_an_empty_cart(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/commandes', [
            'adresse_livraison' => 'Douala',
            'items' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }
}
