<?php

use App\Models\Client;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?Vente $vente = null;

    public string $type = 'devis';
    public ?int $client_id = null;
    public string $date_vente;
    public string $remise = '0';
    public string $notes = '';

    /** @var array<int, array{produit_id: ?int, quantite: int, prix_unitaire: string}> */
    public array $lignes = [];

    public function mount(?Vente $vente = null): void
    {
        $this->date_vente = now()->format('Y-m-d');
        $this->type = request()->query('type', 'devis');

        if ($vente && $vente->exists) {
            if ($vente->statut !== 'brouillon') {
                $this->redirect(route('ventes.show', $vente), navigate: true);
                return;
            }

            $this->vente = $vente;
            $this->type = $vente->type;
            $this->client_id = $vente->client_id;
            $this->date_vente = $vente->date_vente->format('Y-m-d');
            $this->remise = (string) $vente->remise;
            $this->notes = (string) $vente->notes;
            $this->lignes = $vente->lignes->map(fn ($l) => [
                'produit_id' => $l->produit_id,
                'quantite' => $l->quantite,
                'prix_unitaire' => (string) $l->prix_unitaire,
            ])->all();
        }

        if (empty($this->lignes)) {
            $this->ajouterLigne();
        }
    }

    public function ajouterLigne(): void
    {
        $this->lignes[] = ['produit_id' => null, 'quantite' => 1, 'prix_unitaire' => '0'];
    }

    public function supprimerLigne(int $index): void
    {
        unset($this->lignes[$index]);
        $this->lignes = array_values($this->lignes);
        if (empty($this->lignes)) {
            $this->ajouterLigne();
        }
    }

    public function updatedLignes($value, $key): void
    {
        // key format: "{index}.produit_id"
        if (str_ends_with($key, '.produit_id')) {
            $index = (int) explode('.', $key)[0];
            $produit = Produit::find($this->lignes[$index]['produit_id'] ?? null);
            if ($produit) {
                $this->lignes[$index]['prix_unitaire'] = (string) $produit->prix_vente;
            }
        }
    }

    #[Computed]
    public function sousTotal(): float
    {
        return collect($this->lignes)->sum(fn ($l) => (float) ($l['prix_unitaire'] ?? 0) * (int) ($l['quantite'] ?? 0));
    }

    #[Computed]
    public function total(): float
    {
        return max(0, $this->sousTotal - (float) $this->remise);
    }

    public function enregistrer(): void
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'date_vente' => 'required|date',
            'remise' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () {
            $sousTotal = $this->sousTotal;
            $total = $this->total;

            if ($this->vente) {
                $this->vente->update([
                    'client_id' => $this->client_id,
                    'date_vente' => $this->date_vente,
                    'remise' => $this->remise,
                    'notes' => $this->notes,
                    'sous_total' => $sousTotal,
                    'total' => $total,
                ]);
                $this->vente->lignes()->delete();
                $vente = $this->vente;
            } else {
                $vente = Vente::create([
                    'numero' => Vente::genererNumero($this->type),
                    'type' => $this->type,
                    'statut' => 'brouillon',
                    'client_id' => $this->client_id,
                    'user_id' => auth()->id(),
                    'date_vente' => $this->date_vente,
                    'remise' => $this->remise,
                    'notes' => $this->notes,
                    'sous_total' => $sousTotal,
                    'total' => $total,
                ]);
            }

            foreach ($this->lignes as $ligne) {
                $produit = Produit::findOrFail($ligne['produit_id']);
                $vente->lignes()->create([
                    'produit_id' => $produit->id,
                    'produit_nom' => $produit->nom,
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'prix_achat_unitaire' => $produit->prix_achat,
                    'quantite' => $ligne['quantite'],
                    'sous_total' => $ligne['prix_unitaire'] * $ligne['quantite'],
                ]);
            }

            session()->flash('succes', __('Enregistré en brouillon.'));
            $this->redirect(route('ventes.show', $vente), navigate: true);
        });
    }
}; ?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $vente ? __('Modifier :n', ['n' => $vente->numero]) : __('Nouveau').' '.__($type) }}
        </h2>
    </x-slot:header>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Client') }} *</label>
                        <select wire:model="client_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                            <option value="">-- {{ __('Choisir un client') }} --</option>
                            @foreach (\App\Models\Client::orderBy('nom')->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->nom }}</option>
                            @endforeach
                        </select>
                        @error('client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Date') }} *</label>
                        <input type="date" wire:model="date_vente"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                        @error('date_vente') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ __('Articles') }}</h3>
                    <button type="button" wire:click="ajouterLigne" class="text-sm text-brand-blue-700 hover:underline">+ {{ __('Ajouter une ligne') }}</button>
                </div>

                <div class="space-y-3">
                    @foreach ($lignes as $index => $ligne)
                        <div class="grid grid-cols-12 gap-2 items-start" wire:key="ligne-{{ $index }}">
                            <div class="col-span-5">
                                <select wire:model.live="lignes.{{ $index }}.produit_id"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500 text-sm">
                                    <option value="">-- {{ __('Produit') }} --</option>
                                    @foreach (\App\Models\Produit::orderBy('nom')->get() as $p)
                                        <option value="{{ $p->id }}">{{ $p->nom }} ({{ $p->quantite_stock }} {{ $p->unite }} {{ __('dispo.') }})</option>
                                    @endforeach
                                </select>
                                @error("lignes.{$index}.produit_id") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <input type="number" min="1" wire:model.live.debounce.400ms="lignes.{{ $index }}.quantite" placeholder="{{ __('Qté') }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500 text-sm">
                            </div>
                            <div class="col-span-3">
                                <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="lignes.{{ $index }}.prix_unitaire" placeholder="{{ __('Prix unitaire') }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500 text-sm">
                            </div>
                            <div class="col-span-1 pt-2 text-sm text-gray-500 dark:text-gray-400 text-right">
                                {{ number_format(($ligne['prix_unitaire'] ?? 0) * ($ligne['quantite'] ?? 0), 0, ',', ' ') }}
                            </div>
                            <div class="col-span-1 text-right">
                                <button type="button" wire:click="supprimerLigne({{ $index }})" class="text-gray-400 hover:text-red-600 text-sm">&times;</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('lignes') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-2 max-w-xs ml-auto">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>{{ __('Sous-total') }}</span>
                        <span>{{ number_format($this->sousTotal, 0, ',', ' ') }} Ar</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('Remise') }}</span>
                        <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="remise" class="w-28 text-right rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500 text-sm">
                    </div>
                    <div class="flex justify-between text-base font-semibold text-gray-900 dark:text-gray-100">
                        <span>{{ __('Total') }}</span>
                        <span>{{ number_format($this->total, 0, ',', ' ') }} Ar</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Notes') }}</label>
                <textarea wire:model="notes" rows="2"
                          class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-blue-500 focus:ring-brand-blue-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('ventes.index', ['type' => $type]) }}" wire:navigate class="text-sm text-gray-500 hover:underline">{{ __('Annuler') }}</a>
                <button wire:click="enregistrer" class="inline-flex items-center px-4 py-2 bg-brand-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-blue-800">
                    {{ __('Enregistrer le brouillon') }}
                </button>
            </div>
        </div>
    </div>
</div>
