<?php

use App\Models\LigneVente;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        $debutMois = now()->startOfMonth();
        $finMois = now()->endOfMonth();

        $facturesValideesMois = Vente::where('type', 'facture')
            ->where('statut', 'validee')
            ->whereBetween('date_vente', [$debutMois, $finMois]);

        $chiffreAffairesMois = (clone $facturesValideesMois)->sum('total');

        $beneficeMois = LigneVente::whereHas('vente', fn ($q) => $q
                ->where('type', 'facture')
                ->where('statut', 'validee')
                ->whereBetween('date_vente', [$debutMois, $finMois]))
            ->selectRaw('SUM((prix_unitaire - prix_achat_unitaire) * quantite) as marge_totale')
            ->value('marge_totale') ?? 0;

        $produitsPopulaires = LigneVente::whereHas('vente', fn ($q) => $q
                ->whereIn('type', ['commande', 'facture'])
                ->where('statut', 'validee'))
            ->selectRaw('produit_nom, SUM(quantite) as total_quantite, SUM(sous_total) as total_ventes')
            ->groupBy('produit_nom')
            ->orderByDesc('total_quantite')
            ->limit(5)
            ->get();

        $evolutionMensuelle = collect(range(5, 0))->map(function ($moisAvant) {
            $mois = now()->subMonths($moisAvant);
            $total = Vente::where('type', 'facture')
                ->where('statut', 'validee')
                ->whereYear('date_vente', $mois->year)
                ->whereMonth('date_vente', $mois->month)
                ->sum('total');

            return ['label' => Carbon::createFromDate($mois->year, $mois->month, 1)->translatedFormat('M Y'), 'total' => (float) $total];
        });

        $maxEvolution = max(1, $evolutionMensuelle->max('total'));

        return [
            'chiffreAffairesMois' => $chiffreAffairesMois,
            'beneficeMois' => $beneficeMois,
            'nombreVentesMois' => (clone $facturesValideesMois)->count(),
            'produitsEnAlerte' => Produit::whereColumn('quantite_stock', '<=', 'seuil_alerte')->count(),
            'produitsPopulaires' => $produitsPopulaires,
            'evolutionMensuelle' => $evolutionMensuelle,
            'maxEvolution' => $maxEvolution,
            'clientsCount' => \App\Models\Client::count(),
            'devisEnAttente' => Vente::where('type', 'devis')->where('statut', 'brouillon')->count(),
        ];
    }
}; ?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tableau de bord') }}
        </h2>
    </x-slot:header>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Chiffre d'affaires (ce mois)</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($chiffreAffairesMois, 0, ',', ' ') }} Ar</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Bénéfice estimé (ce mois)</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-blue-700">{{ number_format($beneficeMois, 0, ',', ' ') }} Ar</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Factures validées (ce mois)</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $nombreVentesMois }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Produits en alerte stock</p>
                    <p class="mt-1 text-2xl font-semibold {{ $produitsEnAlerte > 0 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100' }}">{{ $produitsEnAlerte }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Évolution du chiffre d'affaires (6 derniers mois)</h3>
                    <div class="flex items-end gap-4 h-48">
                        @foreach ($evolutionMensuelle as $point)
                            <div class="flex-1 flex flex-col items-center justify-end h-full gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $point['total'] > 0 ? number_format($point['total'] / 1000, 0) . 'k' : '' }}</span>
                                <div class="w-full bg-brand-blue-500 rounded-t-md" style="height: {{ max(4, ($point['total'] / $maxEvolution) * 100) }}%"></div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Produits populaires</h3>
                    @forelse ($produitsPopulaires as $produit)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $produit->produit_nom }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $produit->total_quantite }} vendus</p>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($produit->total_ventes, 0, ',', ' ') }} Ar</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune vente validée pour le moment.</p>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('clients.index') }}" wire:navigate class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 hover:shadow-md transition">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Clients</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $clientsCount }}</p>
                </a>
                <a href="{{ route('ventes.index', ['type' => 'devis']) }}" wire:navigate class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 hover:shadow-md transition">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Devis en attente</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $devisEnAttente }}</p>
                </a>
                <a href="{{ route('stock.index') }}" wire:navigate class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 hover:shadow-md transition">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Voir le stock</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">&rarr;</p>
                </a>
            </div>
        </div>
    </div>
</div>
