<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $table = 'paiements';

    protected $fillable = [
        'commande_id',
        'montant',
        'mode_paiement',
        'statut',
        'reference_paiement',
        'date_paiement',
    ];
}
