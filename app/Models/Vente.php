<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Vente extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'type',
        'statut',
        'client_id',
        'user_id',
        'vente_origine_id',
        'date_vente',
        'sous_total',
        'remise',
        'total',
        'notes',
        'validee_at',
    ];

    protected $casts = [
        'date_vente' => 'date',
        'sous_total' => 'decimal:2',
        'remise' => 'decimal:2',
        'total' => 'decimal:2',
        'validee_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venteOrigine(): BelongsTo
    {
        return $this->belongsTo(Vente::class, 'vente_origine_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public static function genererNumero(string $type): string
    {
        $prefixes = ['devis' => 'DEV', 'commande' => 'CMD', 'facture' => 'FAC'];
        $prefixe = $prefixes[$type] ?? 'VTE';
        $annee = now()->format('Y');

        $dernier = static::where('type', $type)
            ->where('numero', 'like', "{$prefixe}-{$annee}-%")
            ->orderByDesc('id')
            ->value('numero');

        $suivant = 1;
        if ($dernier && preg_match('/-(\d+)$/', $dernier, $m)) {
            $suivant = (int) $m[1] + 1;
        }

        return sprintf('%s-%s-%04d', $prefixe, $annee, $suivant);
    }

    /**
     * Valide la vente : fige le statut et, pour une commande ou une facture,
     * décrémente le stock des produits concernés.
     */
    public function valider(): void
    {
        if ($this->statut !== 'brouillon') {
            throw new RuntimeException('Seule une vente en brouillon peut être validée.');
        }

        DB::transaction(function () {
            if (in_array($this->type, ['commande', 'facture'], true)) {
                foreach ($this->lignes as $ligne) {
                    $produit = $ligne->produit()->lockForUpdate()->first();

                    if ($produit->quantite_stock < $ligne->quantite) {
                        throw new RuntimeException("Stock insuffisant pour {$produit->nom} ({$produit->quantite_stock} {$produit->unite} disponible(s)).");
                    }

                    $produit->decrement('quantite_stock', $ligne->quantite);

                    $this->mouvementsStock()->create([
                        'produit_id' => $produit->id,
                        'type' => 'sortie',
                        'quantite' => $ligne->quantite,
                        'motif' => ucfirst($this->type).' '.$this->numero,
                        'user_id' => $this->user_id,
                    ]);
                }
            }

            $this->update(['statut' => 'validee', 'validee_at' => now()]);
        });
    }

    /**
     * Annule une vente déjà validée et restitue le stock si nécessaire.
     */
    public function annuler(): void
    {
        if ($this->statut !== 'validee') {
            throw new RuntimeException('Seule une vente validée peut être annulée.');
        }

        DB::transaction(function () {
            if (in_array($this->type, ['commande', 'facture'], true)) {
                foreach ($this->lignes as $ligne) {
                    $produit = $ligne->produit()->lockForUpdate()->first();
                    $produit->increment('quantite_stock', $ligne->quantite);

                    $this->mouvementsStock()->create([
                        'produit_id' => $produit->id,
                        'type' => 'entree',
                        'quantite' => $ligne->quantite,
                        'motif' => 'Annulation '.ucfirst($this->type).' '.$this->numero,
                        'user_id' => $this->user_id,
                    ]);
                }
            }

            $this->update(['statut' => 'annulee']);
        });
    }
}
