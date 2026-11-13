<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailCommande extends Model
{
    protected $table = 'details_commandes';

    protected $fillable = [
        'commande_id',
        'produit_id',
        'nom_produit',
        'quantite',
        'prix_unitaire',
        'sous_total',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
