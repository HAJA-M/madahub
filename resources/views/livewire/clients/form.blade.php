<?php

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?Client $client = null;

    public string $nom = '';
    public string $type = 'particulier';
    public string $email = '';
    public string $telephone = '';
    public string $adresse = '';
    public string $notes = '';

    public function mount(?Client $client = null): void
    {
        if ($client && $client->exists) {
            $this->client = $client;
            $this->nom = $client->nom;
            $this->type = $client->type;
            $this->email = (string) $client->email;
            $this->telephone = (string) $client->telephone;
            $this->adresse = (string) $client->adresse;
            $this->notes = (string) $client->notes;
        }
    }

    public function enregistrer(): void
    {
        $donnees = $this->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:particulier,entreprise',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($this->client) {
            $this->client->update($donnees);
        } else {
            $donnees['user_id'] = auth()->id();
            Client::create($donnees);
        }

        session()->flash('succes', 'Client enregistré.');
        $this->redirect(route('clients.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $client ? 'Modifier le client' : 'Nouveau client' }}
        </h2>
    </x-slot:header>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form wire:submit="enregistrer" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom *</label>
                    <input type="text" wire:model="nom"
                           class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('nom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                    <select wire:model="type" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="particulier">Particulier</option>
                        <option value="entreprise">Entreprise</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" wire:model="email"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                        <input type="text" wire:model="telephone" placeholder="034 xx xxx xx"
                               class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse</label>
                    <input type="text" wire:model="adresse"
                           class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea wire:model="notes" rows="3"
                              class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('clients.index') }}" wire:navigate class="text-sm text-gray-500 hover:underline">Annuler</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
