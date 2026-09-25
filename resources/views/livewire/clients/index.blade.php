<?php

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $recherche = '';

    public ?int $confirmationSuppression = null;

    public function with(): array
    {
        return [
            'clients' => Client::query()
                ->when($this->recherche, fn ($q) => $q->where(function ($q) {
                    $q->where('nom', 'like', "%{$this->recherche}%")
                        ->orWhere('email', 'like', "%{$this->recherche}%")
                        ->orWhere('telephone', 'like', "%{$this->recherche}%");
                }))
                ->withCount('ventes')
                ->orderByDesc('id')
                ->paginate(10),
        ];
    }

    public function updatedRecherche(): void
    {
        $this->resetPage();
    }

    public function demanderSuppression(int $id): void
    {
        $this->confirmationSuppression = $id;
    }

    public function supprimer(int $id): void
    {
        Client::findOrFail($id)->delete();
        $this->confirmationSuppression = null;
        session()->flash('succes', 'Client supprimé.');
    }
}; ?>

<div>
    <x-slot:header>
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Clients') }}
            </h2>
            <a href="{{ route('clients.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:outline-none transition">
                + Nouveau client
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

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                <input type="text" wire:model.live.debounce.300ms="recherche"
                       placeholder="Rechercher un client (nom, email, téléphone)..."
                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nom</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ventes</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($clients as $client)
                            <tr wire:key="client-{{ $client->id }}">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $client->nom }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ $client->type }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $client->email }}
                                    @if ($client->email && $client->telephone) <br> @endif
                                    {{ $client->telephone }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $client->ventes_count }}</td>
                                <td class="px-4 py-3 text-right text-sm space-x-2 whitespace-nowrap">
                                    <a href="{{ route('clients.edit', $client) }}" wire:navigate class="text-emerald-600 hover:underline">Modifier</a>
                                    @if ($confirmationSuppression === $client->id)
                                        <button wire:click="supprimer({{ $client->id }})" class="text-red-600 hover:underline">Confirmer ?</button>
                                        <button wire:click="$set('confirmationSuppression', null)" class="text-gray-400 hover:underline">Annuler</button>
                                    @else
                                        <button wire:click="demanderSuppression({{ $client->id }})" class="text-gray-400 hover:text-red-600">Supprimer</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Aucun client pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">
                    {{ $clients->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
