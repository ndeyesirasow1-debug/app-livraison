<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $table = 'commandes';

    protected $fillable = [
        'client_id',
        'adresse_livraison',
        'telephone_destinataire',
        'frais_livraison',
        'montant_total',
        'statut',
        'zone_livraison_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function details()
    {
        return $this->hasMany(DetailCommande::class);
    }

    public function livraison()
    {
        return $this->hasOne(Livraison::class);
    }

    public function paiement()
    {
        return $this->hasOne(Paiement::class);
    }
}
