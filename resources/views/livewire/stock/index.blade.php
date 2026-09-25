<?php

use App\Models\MouvementStock;
use App\Models\Produit;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public ?int $produitAjustementId = null;
    public string $ajustementType = 'entree';
    public string $ajustementQuantite = '1';
    public string $ajustementMotif = '';

    public function with(): array
    {
        return [
            'produitsEnAlerte' => Produit::whereColumn('quantite_stock', '<=', 'seuil_alerte')->orderBy('nom')->get(),
            'produits' => Produit::orderBy('nom')->get(['id', 'nom', 'reference', 'unite', 'quantite_stock', 'seuil_alerte']),
            'mouvements' => MouvementStock::with(['produit', 'vente'])->latest()->paginate(12),
        ];
    }

    public function ouvrirAjustement(int $produitId): void
    {
        $this->produitAjustementId = $produitId;
        $this->ajustementType = 'entree';
        $this->ajustementQuantite = '1';
        $this->ajustementMotif = '';
    }

    public function fermerAjustement(): void
    {
        $this->produitAjustementId = null;
    }

    public function enregistrerAjustement(): void
    {
        $this->validate([
            'ajustementType' => 'required|in:entree,sortie',
            'ajustementQuantite' => 'required|integer|min:1',
            'ajustementMotif' => 'nullable|string|max:255',
        ]);

        $produit = Produit::findOrFail($this->produitAjustementId);
        $quantite = (int) $this->ajustementQuantite;

        if ($this->ajustementType === 'sortie' && $quantite > $produit->quantite_stock) {
            $this->addError('ajustementQuantite', 'Quantité supérieure au stock disponible ('.$produit->quantite_stock.').');
            return;
        }

        if ($this->ajustementType === 'entree') {
            $produit->increment('quantite_stock', $quantite);
        } else {
            $produit->decrement('quantite_stock', $quantite);
        }

        MouvementStock::create([
            'produit_id' => $produit->id,
            'type' => $this->ajustementType,
            'quantite' => $quantite,
            'motif' => $this->ajustementMotif ?: 'Ajustement manuel',
            'user_id' => auth()->id(),
        ]);

        $this->produitAjustementId = null;
        session()->flash('succes', 'Stock ajusté pour '.$produit->nom.'.');
    }
}; ?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Stock') }}
        </h2>
    </x-slot:header>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('succes'))
                <div class="p-3 rounded-md bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm">
                    {{ session('succes') }}
                </div>
            @endif

            @if ($produitsEnAlerte->isNotEmpty())
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <p class="text-sm font-semibold text-red-700 dark:text-red-300 mb-2">
                        {{ $produitsEnAlerte->count() }} produit(s) sous le seuil d'alerte
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($produitsEnAlerte as $p)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                {{ $p->nom }} — {{ $p->quantite_stock }} {{ $p->unite }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Niveaux de stock</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Produit</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantité</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($produits as $produit)
                                <tr wire:key="stock-produit-{{ $produit->id }}">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $produit->nom }}</td>
                                    <td class="px-4 py-2 text-sm text-right {{ $produit->quantite_stock <= $produit->seuil_alerte ? 'text-red-600 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $produit->quantite_stock }} {{ $produit->unite }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <button wire:click="ouvrirAjustement({{ $produit->id }})" class="text-xs text-emerald-600 hover:underline">Ajuster</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Historique des mouvements</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Produit</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qté</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($mouvements as $mouvement)
                                <tr wire:key="mvt-{{ $mouvement->id }}">
                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $mouvement->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $mouvement->produit?->nom }}
                                        <div class="text-xs text-gray-400">{{ $mouvement->motif }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        @if ($mouvement->type === 'entree')
                                            <span class="text-green-600">Entrée</span>
                                        @else
                                            <span class="text-red-600">Sortie</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-500 dark:text-gray-400">{{ $mouvement->quantite }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Aucun mouvement.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-4">
                        {{ $mouvements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($produitAjustementId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click.self="fermerAjustement">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200">Ajuster le stock</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type de mouvement</label>
                    <select wire:model="ajustementType" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="entree">Entrée (réapprovisionnement)</option>
                        <option value="sortie">Sortie (perte, casse...)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantité</label>
                    <input type="number" min="1" wire:model="ajustementQuantite"
                           class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('ajustementQuantite') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motif</label>
                    <input type="text" wire:model="ajustementMotif" placeholder="Réapprovisionnement fournisseur..."
                           class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="fermerAjustement" class="text-sm text-gray-500 hover:underline">Annuler</button>
                    <button wire:click="enregistrerAjustement" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700">
                        Valider
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
