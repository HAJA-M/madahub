<?php

use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Vente $vente;

    public function mount(Vente $vente): void
    {
        $this->vente = $vente->load(['client', 'lignes.produit', 'venteOrigine']);
    }

    public function valider(): void
    {
        try {
            $this->vente->valider();
            session()->flash('succes', __('Vente validée. Le stock a été mis à jour.'));
        } catch (\Throwable $e) {
            session()->flash('erreur', $e->getMessage());
        }
        $this->vente->refresh();
    }

    public function annuler(): void
    {
        try {
            $this->vente->annuler();
            session()->flash('succes', __('Vente annulée. Le stock a été restitué.'));
        } catch (\Throwable $e) {
            session()->flash('erreur', $e->getMessage());
        }
        $this->vente->refresh();
    }

    public function supprimer(): void
    {
        if ($this->vente->statut !== 'brouillon') {
            session()->flash('erreur', __('Seul un brouillon peut être supprimé.'));
            return;
        }
        $type = $this->vente->type;
        $this->vente->delete();
        $this->redirect(route('ventes.index', ['type' => $type]), navigate: true);
    }

    public function convertir(string $typeCible): void
    {
        $nouvelleVente = DB::transaction(function () use ($typeCible) {
            $nouvelle = Vente::create([
                'numero' => Vente::genererNumero($typeCible),
                'type' => $typeCible,
                'statut' => 'brouillon',
                'client_id' => $this->vente->client_id,
                'user_id' => auth()->id(),
                'vente_origine_id' => $this->vente->id,
                'date_vente' => now()->format('Y-m-d'),
                'sous_total' => $this->vente->sous_total,
                'remise' => $this->vente->remise,
                'total' => $this->vente->total,
                'notes' => $this->vente->notes,
            ]);

            foreach ($this->vente->lignes as $ligne) {
                $nouvelle->lignes()->create([
                    'produit_id' => $ligne->produit_id,
                    'produit_nom' => $ligne->produit_nom,
                    'prix_unitaire' => $ligne->prix_unitaire,
                    'prix_achat_unitaire' => $ligne->prix_achat_unitaire,
                    'quantite' => $ligne->quantite,
                    'sous_total' => $ligne->sous_total,
                ]);
            }

            return $nouvelle;
        });

        session()->flash('succes', __(':type créé(e) à partir de :numero.', ['type' => __(ucfirst($typeCible)), 'numero' => $this->vente->numero]));
        $this->redirect(route('ventes.show', $nouvelleVente), navigate: true);
    }
}; ?>

<div>
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $vente->numero }}
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 capitalize">({{ __($vente->type) }})</span>
            </h2>
            <a href="{{ route('ventes.index', ['type' => $vente->type]) }}" wire:navigate class="text-sm text-gray-500 hover:underline">&larr; {{ __('Retour à la liste') }}</a>
        </div>
    </x-slot:header>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('succes'))
                <div class="p-3 rounded-md bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm">
                    {{ session('succes') }}
                </div>
            @endif
            @if (session('erreur'))
                <div class="p-3 rounded-md bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
                    {{ session('erreur') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Client') }}</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $vente->client->nom }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $vente->client->telephone }} {{ $vente->client->email }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Date') }}</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $vente->date_vente->format('d/m/Y') }}</p>
                        @php
                            $couleurs = ['brouillon' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', 'validee' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300', 'annulee' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'];
                        @endphp
                        <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs {{ $couleurs[$vente->statut] }}">
                            {{ __(['brouillon' => 'Brouillon', 'validee' => 'Validée', 'annulee' => 'Annulée'][$vente->statut]) }}
                        </span>
                    </div>
                </div>

                @if ($vente->venteOrigine)
                    <p class="mt-3 text-xs text-gray-400">{{ __('Généré depuis :n', ['n' => $vente->venteOrigine->numero]) }}</p>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Produit') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Qté') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Prix unitaire') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ __('Sous-total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($vente->lignes as $ligne)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $ligne->produit_nom }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-500 dark:text-gray-400">{{ $ligne->quantite }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-500 dark:text-gray-400">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }} Ar</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($ligne->sous_total, 0, ',', ' ') }} Ar</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-gray-100 dark:border-gray-700 p-4 space-y-1 max-w-xs ml-auto text-sm">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>{{ __('Sous-total') }}</span><span>{{ number_format($vente->sous_total, 0, ',', ' ') }} Ar</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>{{ __('Remise') }}</span><span>-{{ number_format($vente->remise, 0, ',', ' ') }} Ar</span>
                    </div>
                    <div class="flex justify-between text-base font-semibold text-gray-900 dark:text-gray-100">
                        <span>{{ __('Total') }}</span><span>{{ number_format($vente->total, 0, ',', ' ') }} Ar</span>
                    </div>
                </div>
            </div>

            @if ($vente->notes)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('Notes') }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $vente->notes }}</p>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                @if ($vente->statut === 'brouillon')
                    <a href="{{ route('ventes.edit', $vente) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50">
                        {{ __('Modifier') }}
                    </a>
                    <button wire:click="valider" wire:confirm="{{ __('Confirmer la validation ?') }} {{ in_array($vente->type, ['commande','facture']) ? __('Le stock sera décrémenté.') : '' }}"
                            class="inline-flex items-center px-4 py-2 bg-brand-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-blue-800">
                        {{ __('Valider') }}
                    </button>
                    <button wire:click="supprimer" wire:confirm="{{ __('Supprimer ce brouillon ?') }}" class="text-sm text-red-600 hover:underline">
                        {{ __('Supprimer') }}
                    </button>
                @elseif ($vente->statut === 'validee')
                    @if ($vente->type === 'devis')
                        <button wire:click="convertir('commande')" class="inline-flex items-center px-4 py-2 bg-brand-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-blue-800">
                            {{ __('Convertir en commande') }}
                        </button>
                        <button wire:click="convertir('facture')" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50">
                            {{ __('Convertir en facture') }}
                        </button>
                    @elseif ($vente->type === 'commande')
                        <button wire:click="convertir('facture')" class="inline-flex items-center px-4 py-2 bg-brand-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-blue-800">
                            {{ __('Facturer') }}
                        </button>
                    @endif
                    <button wire:click="annuler" wire:confirm="{{ __('Annuler cette vente ?') }} {{ in_array($vente->type, ['commande','facture']) ? __('Le stock sera restitué.') : '' }}" class="text-sm text-red-600 hover:underline">
                        {{ __('Annuler la vente') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
