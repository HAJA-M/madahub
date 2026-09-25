<?php

use App\Models\Vente;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $type = 'devis';

    #[Url]
    public string $recherche = '';

    public function with(): array
    {
        return [
            'ventes' => Vente::query()
                ->with('client')
                ->where('type', $this->type)
                ->when($this->recherche, fn ($q) => $q->where(function ($q) {
                    $q->where('numero', 'like', "%{$this->recherche}%")
                        ->orWhereHas('client', fn ($q) => $q->where('nom', 'like', "%{$this->recherche}%"));
                }))
                ->orderByDesc('id')
                ->paginate(10),
        ];
    }

    public function changerType(string $type): void
    {
        $this->type = $type;
        $this->resetPage();
    }

    public function updatedRecherche(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Ventes') }}
            </h2>
            <a href="{{ route('ventes.create', ['type' => $type]) }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-brand-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-blue-800 focus:outline-none transition">
                + {{ __('Nouveau') }} {{ __(['devis' => 'devis', 'commande' => 'commande', 'facture' => 'facture'][$type]) }}
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

            <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <div class="inline-flex rounded-md shadow-sm">
                    @foreach (['devis' => 'Devis', 'commande' => 'Commandes', 'facture' => 'Factures'] as $value => $label)
                        <button wire:click="changerType('{{ $value }}')"
                                class="px-4 py-2 text-sm font-medium border border-gray-200 dark:border-gray-700 first:rounded-l-md last:rounded-r-md -ml-px first:ml-0
                                {{ $type === $value ? 'bg-brand-blue-700 text-white border-brand-blue-700' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                            {{ __($label) }}
                        </button>
                    @endforeach
                </div>
                <input type="text" wire:model.live.debounce.300ms="recherche"
                       placeholder="{{ __('Rechercher un numéro ou un client...') }}"
                       class="sm:w-72 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Numéro') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Client') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Total') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Statut') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($ventes as $vente)
                            <tr wire:key="vente-{{ $vente->id }}">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $vente->numero }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $vente->client->nom }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $vente->date_vente->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($vente->total, 0, ',', ' ') }} Ar</td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $couleurs = ['brouillon' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', 'validee' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300', 'annulee' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs {{ $couleurs[$vente->statut] }}">
                                        {{ __(['brouillon' => 'Brouillon', 'validee' => 'Validée', 'annulee' => 'Annulée'][$vente->statut]) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="{{ route('ventes.show', $vente) }}" wire:navigate class="text-brand-blue-700 hover:underline">{{ __('Voir') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Aucun élément pour le moment.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">
                    {{ $ventes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
