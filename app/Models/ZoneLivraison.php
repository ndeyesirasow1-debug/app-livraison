<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneLivraison extends Model
{
    protected $table = 'zones_livraison';

    protected $fillable = [
        'nom',
        'ville',
        'latitude',
        'longitude',
        'rayon_km',
    ];
}
