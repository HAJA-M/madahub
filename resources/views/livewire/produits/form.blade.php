<?php

use App\Models\Produit;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?Produit $produit = null;

    public string $nom = '';
    public string $reference = '';
    public string $description = '';
    public string $unite = 'unité';
    public string $prix_achat = '0';
    public string $prix_vente = '0';
    public string $quantite_stock = '0';
    public string $seuil_alerte = '5';

    public function mount(?Produit $produit = null): void
    {
        if ($produit && $produit->exists) {
            $this->produit = $produit;
            $this->nom = $produit->nom;
            $this->reference = $produit->reference;
            $this->description = (string) $produit->description;
            $this->unite = $produit->unite;
            $this->prix_achat = (string) $produit->prix_achat;
            $this->prix_vente = (string) $produit->prix_vente;
            $this->quantite_stock = (string) $produit->quantite_stock;
            $this->seuil_alerte = (string) $produit->seuil_alerte;
        }
    }

    public function enregistrer(): void
    {
        $donnees = $this->validate([
            'nom' => 'required|string|max:255',
            'reference' => ['required', 'string', 'max:100', Rule::unique('produits', 'reference')->ignore($this->produit?->id)],
            'description' => 'nullable|string|max:2000',
            'unite' => 'required|string|max:50',
            'prix_achat' => 'required|numeric|min:0',
            'prix_vente' => 'required|numeric|min:0',
            'quantite_stock' => 'required|integer|min:0',
            'seuil_alerte' => 'required|integer|min:0',
        ]);

        if ($this->produit) {
            $this->produit->update($donnees);
        } else {
            Produit::create($donnees);
        }

        session()->flash('succes', 'Produit enregistré.');
        $this->redirect(route('produits.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $produit ? 'Modifier le produit' : 'Nouveau produit' }}
        </h2>
    </x-slot:header>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form wire:submit="enregistrer" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom *</label>
                        <input type="text" wire:model="nom"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                        @error('nom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Référence (SKU) *</label>
                        <input type="text" wire:model="reference"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                        @error('reference') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea wire:model="description" rows="2"
                              class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prix d'achat (Ar) *</label>
                        <input type="number" step="0.01" wire:model="prix_achat"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                        @error('prix_achat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prix de vente (Ar) *</label>
                        <input type="number" step="0.01" wire:model="prix_vente"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                        @error('prix_vente') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unité</label>
                        <input type="text" wire:model="unite"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantité en stock *</label>
                        <input type="number" wire:model="quantite_stock" {{ $produit ? 'disabled' : '' }}
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500 disabled:opacity-50">
                        @error('quantite_stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @if ($produit)
                            <p class="mt-1 text-xs text-gray-400">Ajustez le stock depuis la page Stock.</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seuil d'alerte *</label>
                        <input type="number" wire:model="seuil_alerte"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                        @error('seuil_alerte') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('produits.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">Annuler</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-brand-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-blue-800">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
