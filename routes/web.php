<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::get('langue/{locale}', function (string $locale) {
    if (in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('dashboard', 'dashboard')->name('dashboard');

    Volt::route('clients', 'clients.index')->name('clients.index');
    Volt::route('clients/creer', 'clients.form')->name('clients.create');
    Volt::route('clients/{client}/modifier', 'clients.form')->name('clients.edit');

    Volt::route('produits', 'produits.index')->name('produits.index');
    Volt::route('produits/creer', 'produits.form')->name('produits.create');
    Volt::route('produits/{produit}/modifier', 'produits.form')->name('produits.edit');

    Volt::route('stock', 'stock.index')->name('stock.index');

    Volt::route('ventes', 'ventes.index')->name('ventes.index');
    Volt::route('ventes/creer', 'ventes.form')->name('ventes.create');
    Volt::route('ventes/{vente}', 'ventes.show')->name('ventes.show');
    Volt::route('ventes/{vente}/modifier', 'ventes.form')->name('ventes.edit');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
