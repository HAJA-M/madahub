<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@madahub.mg'],
            ['name' => 'Admin MadaHub', 'password' => Hash::make('password')]
        );

        $clients = collect([
            ['nom' => 'Rakoto Jean', 'type' => 'particulier', 'email' => 'rakoto.jean@example.mg', 'telephone' => '034 11 222 33', 'adresse' => 'Ankorondrano, Antananarivo'],
            ['nom' => 'Épicerie Vola', 'type' => 'entreprise', 'email' => 'contact@epicerie-vola.mg', 'telephone' => '032 44 555 66', 'adresse' => 'Analakely, Antananarivo'],
            ['nom' => 'Randria Sarobidy', 'type' => 'particulier', 'email' => 'randria.s@example.mg', 'telephone' => '033 77 888 99', 'adresse' => 'Mahamasina, Antananarivo'],
            ['nom' => 'Quincaillerie Tsiky', 'type' => 'entreprise', 'email' => 'tsiky@quincaillerie.mg', 'telephone' => '034 22 111 00', 'adresse' => 'Ambatolampy'],
        ])->map(fn ($c) => Client::firstOrCreate(['nom' => $c['nom']], $c + ['user_id' => $admin->id]));

        $produits = collect([
            ['nom' => 'Riz local 1kg', 'reference' => 'RIZ-001', 'unite' => 'kg', 'prix_achat' => 2800, 'prix_vente' => 3500, 'quantite_stock' => 300, 'seuil_alerte' => 20],
            ['nom' => 'Huile végétale 1L', 'reference' => 'HUI-001', 'unite' => 'litre', 'prix_achat' => 6500, 'prix_vente' => 8000, 'quantite_stock' => 150, 'seuil_alerte' => 10],
            ['nom' => 'Savon de Marseille', 'reference' => 'SAV-001', 'unite' => 'unité', 'prix_achat' => 1500, 'prix_vente' => 2200, 'quantite_stock' => 100, 'seuil_alerte' => 15],
            ['nom' => 'Sucre blanc 1kg', 'reference' => 'SUC-001', 'unite' => 'kg', 'prix_achat' => 3200, 'prix_vente' => 4000, 'quantite_stock' => 200, 'seuil_alerte' => 15],
            ['nom' => 'Charbon de bois 5kg', 'reference' => 'CHA-001', 'unite' => 'sac', 'prix_achat' => 4000, 'prix_vente' => 5500, 'quantite_stock' => 120, 'seuil_alerte' => 10],
        ])->map(fn ($p) => Produit::firstOrCreate(['reference' => $p['reference']], $p));

        if (Vente::count() > 0) {
            return;
        }

        // Quelques factures validées sur les derniers mois pour peupler le tableau de bord.
        foreach (range(3, 0) as $moisAvant) {
            $date = now()->subMonths($moisAvant)->startOfMonth()->addDays(rand(2, 20));

            foreach (range(1, rand(2, 4)) as $i) {
                $client = $clients->random();
                $lignesProduits = $produits->random(rand(1, 3));

                $facture = Vente::create([
                    'numero' => Vente::genererNumero('facture'),
                    'type' => 'facture',
                    'statut' => 'brouillon',
                    'client_id' => $client->id,
                    'user_id' => $admin->id,
                    'date_vente' => $date,
                    'sous_total' => 0,
                    'remise' => 0,
                    'total' => 0,
                ]);

                $sousTotal = 0;
                foreach ($lignesProduits as $produit) {
                    $quantite = rand(1, 6);
                    $sousTotalLigne = $produit->prix_vente * $quantite;
                    $sousTotal += $sousTotalLigne;

                    $facture->lignes()->create([
                        'produit_id' => $produit->id,
                        'produit_nom' => $produit->nom,
                        'prix_unitaire' => $produit->prix_vente,
                        'prix_achat_unitaire' => $produit->prix_achat,
                        'quantite' => $quantite,
                        'sous_total' => $sousTotalLigne,
                    ]);
                }

                $facture->update(['sous_total' => $sousTotal, 'total' => $sousTotal]);
                $facture->valider();
            }
        }

        // Un devis en attente pour illustrer le workflow.
        $devis = Vente::create([
            'numero' => Vente::genererNumero('devis'),
            'type' => 'devis',
            'statut' => 'brouillon',
            'client_id' => $clients->first()->id,
            'user_id' => $admin->id,
            'date_vente' => now(),
            'sous_total' => 0,
            'remise' => 0,
            'total' => 0,
        ]);
        $produit = $produits->first();
        $devis->lignes()->create([
            'produit_id' => $produit->id,
            'produit_nom' => $produit->nom,
            'prix_unitaire' => $produit->prix_vente,
            'prix_achat_unitaire' => $produit->prix_achat,
            'quantite' => 10,
            'sous_total' => $produit->prix_vente * 10,
        ]);
        $devis->update(['sous_total' => $produit->prix_vente * 10, 'total' => $produit->prix_vente * 10]);

        // Fait passer le savon sous le seuil d'alerte pour illustrer le module Stock.
        $savon = Produit::where('reference', 'SAV-001')->first();
        if ($savon && $savon->quantite_stock > $savon->seuil_alerte) {
            $quantiteRetiree = $savon->quantite_stock - $savon->seuil_alerte + 5;
            $savon->decrement('quantite_stock', $quantiteRetiree);
            $savon->mouvementsStock()->create([
                'type' => 'sortie',
                'quantite' => $quantiteRetiree,
                'motif' => 'Casse / démonstration',
                'user_id' => $admin->id,
            ]);
        }
    }
}
