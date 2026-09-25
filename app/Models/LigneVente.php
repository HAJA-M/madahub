<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneVente extends Model
{
    use HasFactory;

    protected $fillable = [
        'vente_id',
        'produit_id',
        'produit_nom',
        'prix_unitaire',
        'prix_achat_unitaire',
        'quantite',
        'sous_total',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'prix_achat_unitaire' => 'decimal:2',
        'quantite' => 'integer',
        'sous_total' => 'decimal:2',
    ];

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function getMargeAttribute(): float
    {
        return ((float) $this->prix_unitaire - (float) $this->prix_achat_unitaire) * $this->quantite;
    }
}
