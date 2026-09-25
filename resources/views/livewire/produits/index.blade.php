<?php

use App\Models\Produit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $recherche = '';

    #[Url]
    public bool $alerteUniquement = false;

    public ?int $confirmationSuppression = null;

    public function with(): array
    {
        return [
            'produits' => Produit::query()
                ->when($this->recherche, fn ($q) => $q->where(function ($q) {
                    $q->where('nom', 'like', "%{$this->recherche}%")
                        ->orWhere('reference', 'like', "%{$this->recherche}%");
                }))
                ->when($this->alerteUniquement, fn ($q) => $q->whereColumn('quantite_stock', '<=', 'seuil_alerte'))
                ->orderBy('nom')
                ->paginate(10),
        ];
    }

    public function updatedRecherche(): void
    {
        $this->resetPage();
    }

    public function updatedAlerteUniquement(): void
    {
        $this->resetPage();
    }

    public function demanderSuppression(int $id): void
    {
        $this->confirmationSuppression = $id;
    }

    public function supprimer(int $id): void
    {
        Produit::findOrFail($id)->delete();
        $this->confirmationSuppression = null;
        session()->flash('succes', 'Produit supprimé.');
    }
}; ?>

<div>
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Produits') }}
            </h2>
            <a href="{{ route('produits.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:outline-none transition">
                + Nouveau produit
            </a>
        </div>
    </x-slot:header>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('succes'))
                <div class="p-3 rounded-md bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm">
                    {{ session('succes') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-col sm:flex-row gap-3">
                <input type="text" wire:model.live.debounce.300ms="recherche"
                       placeholder="Rechercher un produit (nom, référence)..."
                       class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="alerteUniquement" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    Alertes stock uniquement
                </label>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Référence</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nom</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Prix vente</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Stock</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($produits as $produit)
                            <tr wire:key="produit-{{ $produit->id }}">
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $produit->reference }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $produit->nom }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ number_format($produit->prix_vente, 0, ',', ' ') }} Ar</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <span class="{{ $produit->en_alerte ? 'text-red-600 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $produit->quantite_stock }} {{ $produit->unite }}
                                    </span>
                                    @if ($produit->en_alerte)
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">alerte</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm space-x-2 whitespace-nowrap">
                                    <a href="{{ route('produits.edit', $produit) }}" wire:navigate class="text-emerald-600 hover:underline">Modifier</a>
                                    @if ($confirmationSuppression === $produit->id)
                                        <button wire:click="supprimer({{ $produit->id }})" class="text-red-600 hover:underline">Confirmer ?</button>
                                        <button wire:click="$set('confirmationSuppression', null)" class="text-gray-400 hover:underline">Annuler</button>
                                    @else
                                        <button wire:click="demanderSuppression({{ $produit->id }})" class="text-gray-400 hover:text-red-600">Supprimer</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Aucun produit pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">
                    {{ $produits->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
