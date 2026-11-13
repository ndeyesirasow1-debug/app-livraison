<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livreur extends Model
{
    protected $table = 'livreurs';

    protected $fillable = [
        'user_id',
        'nom',
        'telephone',
        'moyen_transport',
        'statut',
        'latitude',
        'longitude',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function livraisons()
    {
        return $this->hasMany(Livraison::class);
    }
}
