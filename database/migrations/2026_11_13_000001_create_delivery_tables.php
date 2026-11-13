<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();
            $table->string('nom');
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });

        Schema::create('livreurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();
            $table->string('nom');
            $table->string('telephone');
            $table->string('moyen_transport')->nullable();
            $table->enum('statut', ['disponible', 'en_livraison', 'inactif'])->default('disponible');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('prix', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->enum('statut', ['actif', 'inactif', 'rupture'])->default('actif');
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('zones_livraison', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('ville');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('rayon_km', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict');
            $table->text('adresse_livraison');
            $table->string('telephone_destinataire')->nullable();
            $table->decimal('frais_livraison', 12, 2)->default(0);
            $table->decimal('montant_total', 12, 2)->default(0);
            $table->enum('statut', ['en_attente', 'confirmee', 'en_preparation', 'en_livraison', 'livree', 'annulee', 'echec'])->default('en_attente');
            $table->foreignId('zone_livraison_id')->nullable()->constrained('zones_livraison')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('details_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->foreignId('produit_id')->nullable()->constrained('produits')->nullOnDelete();
            $table->string('nom_produit');
            $table->unsignedInteger('quantite')->default(1);
            $table->decimal('prix_unitaire', 12, 2)->default(0);
            $table->decimal('sous_total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->unique()->onDelete('restrict');
            $table->foreignId('livreur_id')->nullable()->constrained('livreurs')->nullOnDelete();
            $table->enum('statut', ['en_attente', 'en_cours', 'livree', 'echec'])->default('en_attente');
            $table->dateTime('date_depart')->nullable();
            $table->dateTime('date_livraison')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->decimal('montant', 12, 2);
            $table->enum('mode_paiement', ['especes', 'mobile_money', 'carte', 'autre'])->default('mobile_money');
            $table->enum('statut', ['en_attente', 'paye', 'echoue', 'annule'])->default('en_attente');
            $table->string('reference_paiement')->nullable();
            $table->timestamp('date_paiement')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('livraisons');
        Schema::dropIfExists('details_commandes');
        Schema::dropIfExists('commandes');
        Schema::dropIfExists('zones_livraison');
        Schema::dropIfExists('produits');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('livreurs');
        Schema::dropIfExists('clients');
    }
};
