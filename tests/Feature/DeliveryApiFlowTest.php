<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeliveryApiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_fetch_products(): void
    {
        $user = User::factory()->create([
            'email' => 'client@demo.com',
            'password' => Hash::make('secret123'),
            'role' => 'client',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'client@demo.com',
            'password' => 'secret123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['email' => 'client@demo.com']);
        $this->assertNotNull($user->fresh());
    }
}
