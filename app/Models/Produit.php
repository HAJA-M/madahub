<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'reference',
        'description',
        'unite',
        'prix_achat',
        'prix_vente',
        'quantite_stock',
        'seuil_alerte',
    ];

    protected $casts = [
        'prix_achat' => 'decimal:2',
        'prix_vente' => 'decimal:2',
        'quantite_stock' => 'integer',
        'seuil_alerte' => 'integer',
    ];

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function ligneVentes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    public function getEnAlerteAttribute(): bool
    {
        return $this->quantite_stock <= $this->seuil_alerte;
    }

    public function getMargeAttribute(): float
    {
        return (float) $this->prix_vente - (float) $this->prix_achat;
    }
}
