<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('type');
                $table->string('titre');
                $table->text('message');
                $table->boolean('lu')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('historique_commandes')) {
            Schema::create('historique_commandes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
                $table->string('ancien_statut');
                $table->string('nouveau_statut');
                $table->text('commentaire')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('historique_livraisons')) {
            Schema::create('historique_livraisons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('livraison_id')->constrained('livraisons')->onDelete('cascade');
                $table->string('ancien_statut');
                $table->string('nouveau_statut');
                $table->text('commentaire')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('positions_livreurs')) {
            Schema::create('positions_livreurs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('livreur_id')->constrained('livreurs')->onDelete('cascade');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->dateTime('date_position')->useCurrent();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('positions_livreurs');
        Schema::dropIfExists('historique_livraisons');
        Schema::dropIfExists('historique_commandes');
        Schema::dropIfExists('notifications');
    }
};
