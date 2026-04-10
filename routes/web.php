<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SAVController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Public Routes (Storefront)
|--------------------------------------------------------------------------
*/
Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/suivi', [StorefrontController::class, 'trackRepair'])->name('track');
Route::post('/suivi', [StorefrontController::class, 'trackRepairSearch'])->name('track.search');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
Route::post('/connexion', [AuthController::class, 'login'])->name('login.submit');
Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');
Route::get('/mot-de-passe-oublie', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/mot-de-passe-oublie', [AuthController::class, 'sendResetCode'])->name('password.reset.send');
Route::post('/mot-de-passe-confirmer', [AuthController::class, 'confirmReset'])->name('password.reset.confirm');

/*
|--------------------------------------------------------------------------
| Dashboard Routes (Authenticated + Shop required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.jwt', 'shop'])->prefix('dashboard')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Shop switching
    Route::post('/switch-shop', [ShopController::class, 'switchShop'])->name('shop.switch');

    // Réparations sur place
    Route::get('/reparations-place', [RepairController::class, 'createPlace'])->name('reparations.place');

    // Réparations sur RDV
    Route::get('/reparations-rdv', [RepairController::class, 'createRdv'])->name('reparations.rdv');

    // Enregistrer réparation (place ou rdv)
    Route::post('/reparations', [RepairController::class, 'store'])->name('reparations.store');

    // Liste des réparations
    Route::get('/liste-reparations', [RepairController::class, 'index'])->name('reparations.liste');
    Route::get('/liste-reparations/export-csv', [RepairController::class, 'exportCsv'])->name('reparations.export.csv');

    // Détail / Modifier / Supprimer réparation
    Route::get('/reparations/{id}', [RepairController::class, 'show'])->name('reparations.show');
    Route::put('/reparations/{id}', [RepairController::class, 'update'])->name('reparations.update');
    Route::delete('/reparations/{id}', [RepairController::class, 'destroy'])->name('reparations.destroy');
    Route::get('/reparations/{id}/recu', [RepairController::class, 'printReceipt'])->name('reparations.receipt');

    // Vente d'articles
    Route::get('/article', [ArticleController::class, 'index'])->name('article');
    Route::post('/article/vendre', [ArticleController::class, 'vendre'])->name('article.vendre');
    Route::delete('/article/annuler/{id}', [ArticleController::class, 'annuler'])->name('article.annuler');

    // SAV
    Route::get('/sav', [SAVController::class, 'index'])->name('sav.index');
    Route::get('/sav/lookup-repair', [SAVController::class, 'lookupRepair'])->name('sav.lookup-repair');
    Route::post('/sav', [SAVController::class, 'store'])->name('sav.store');
    Route::put('/sav/{id}', [SAVController::class, 'update'])->name('sav.update');
    Route::delete('/sav/{id}', [SAVController::class, 'destroy'])->name('sav.destroy');

    // Stocks (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::post('/stocks', [StockController::class, 'store'])->name('stocks.store');
        Route::put('/stocks/{id}', [StockController::class, 'update'])->name('stocks.update');
        Route::delete('/stocks/{id}', [StockController::class, 'destroy'])->name('stocks.destroy');
        Route::post('/stocks/{id}/reappro', [StockController::class, 'reapprovisionner'])->name('stocks.reappro');
        Route::get('/stocks/{id}/historique-reappro', [StockController::class, 'historiqueReappro'])->name('stocks.historique-reappro');
    });

    // Paramètres (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/parametres', [SettingsController::class, 'index'])->name('parametres');
        Route::post('/parametres', [SettingsController::class, 'update'])->name('parametres.update');
        Route::post('/utilisateurs', [AuthController::class, 'register'])->name('users.register');
        Route::put('/utilisateurs/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
        Route::delete('/utilisateurs/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/utilisateurs/export-csv', [UserController::class, 'exportCsv'])->name('users.export.csv');
    });

    // Shops management (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::post('/boutiques', [ShopController::class, 'store'])->name('shops.store');
        Route::put('/boutiques/{id}', [ShopController::class, 'update'])->name('shops.update');
        Route::delete('/boutiques/{id}', [ShopController::class, 'destroy'])->name('shops.destroy');
        Route::post('/boutiques/{id}/utilisateurs', [ShopController::class, 'addUser'])->name('shops.addUser');
        Route::delete('/boutiques/{id}/utilisateurs', [ShopController::class, 'removeUser'])->name('shops.removeUser');
    });

    // Profile
    Route::put('/profil', [UserController::class, 'updateProfile'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| API Routes (JWT auth for mobile/external)
|--------------------------------------------------------------------------
*/
Route::prefix('api')->group(function () {
    Route::post('/login', [AuthController::class, 'apiLogin']);
});
