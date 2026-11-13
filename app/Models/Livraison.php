<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    protected $table = 'livraisons';

    protected $fillable = [
        'commande_id',
        'livreur_id',
        'statut',
        'date_depart',
        'date_livraison',
        'commentaire',
    ];
}
