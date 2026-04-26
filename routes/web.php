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
use App\Http\Controllers\DataPrivacyController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\QrLabelController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\RelanceController;
use App\Http\Controllers\RepairPhotoController;
use App\Http\Controllers\PanneTemplateController;
use App\Http\Controllers\AbandonController;
use App\Http\Controllers\MarginController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TechnicianSkillController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\TwoFactorController;

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
// 2FA verification (no auth.jwt required — user just passed password check)
Route::get('/2fa',  [TwoFactorController::class, 'verify'])->name('two-factor.verify');
Route::post('/2fa', [TwoFactorController::class, 'verifySubmit'])->name('two-factor.verify.submit');

Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');
Route::get('/mot-de-passe-oublie', [AuthController::class, 'showResetPassword'])->name('password.reset');

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/connexion', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/mot-de-passe-oublie', [AuthController::class, 'sendResetCode'])->name('password.reset.send');
    Route::post('/mot-de-passe-confirmer', [AuthController::class, 'confirmReset'])->name('password.reset.confirm');
});

/*
|--------------------------------------------------------------------------
| Dashboard Routes (Authenticated + Shop required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.jwt', 'shop'])->prefix('dashboard')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Shop switching
    Route::post('/switch-shop', [ShopController::class, 'switchShop'])->name('shop.switch');

    // Création réparations (caissière uniquement)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/reparations-place', [RepairController::class, 'createPlace'])->name('reparations.place');
        Route::get('/reparations-rdv', [RepairController::class, 'createRdv'])->name('reparations.rdv');
        Route::post('/reparations', [RepairController::class, 'store'])->name('reparations.store');
        Route::get('/liste-reparations/export-csv', [RepairController::class, 'exportCsv'])->name('reparations.export.csv');
        Route::put('/reparations/{id}', [RepairController::class, 'update'])->name('reparations.update');
        Route::delete('/reparations/{id}', [RepairController::class, 'destroy'])->name('reparations.destroy');
        Route::get('/reparations/{id}/recu', [RepairController::class, 'printReceipt'])->name('reparations.receipt');
    });

    // Liste + détail (tous les rôles — réparateur doit voir ses réparations)
    Route::get('/liste-reparations', [RepairController::class, 'index'])->name('reparations.liste');
    Route::get('/reparations/{id}', [RepairController::class, 'show'])->name('reparations.show');

    // Diagnostic technique (réparateur exclusivement)
    Route::middleware(['role:reparateur,patron'])->group(function () {
        Route::put('/reparations/{id}/diagnostic', [RepairController::class, 'updateDiagnostic'])->name('reparations.diagnostic');
    });

    // Vente d'articles (caissière uniquement)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/article', [ArticleController::class, 'index'])->name('article');
        Route::post('/article/vendre', [ArticleController::class, 'vendre'])->name('article.vendre');
        Route::delete('/article/annuler/{id}', [ArticleController::class, 'annuler'])->name('article.annuler');
    });

    // SAV (caissière uniquement)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/sav', [SAVController::class, 'index'])->name('sav.index');
        Route::get('/sav/lookup-repair', [SAVController::class, 'lookupRepair'])->name('sav.lookup-repair');
        Route::post('/sav', [SAVController::class, 'store'])->name('sav.store');
        Route::put('/sav/{id}', [SAVController::class, 'update'])->name('sav.update');
        Route::delete('/sav/{id}', [SAVController::class, 'destroy'])->name('sav.destroy');
    });

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

    // Clients (caissière uniquement)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{id}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('/clients/{id}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{id}', [ClientController::class, 'update'])->name('clients.update');
        Route::post('/clients/{id}/remboursement', [ClientController::class, 'remboursement'])->name('clients.remboursement');
    });
    Route::middleware(['role:patron'])->group(function () {
        Route::post('/clients/{id}/lier-compte', [ClientController::class, 'lierCompte'])->name('clients.lier-compte');
        Route::post('/clients/{id}/delier-compte', [ClientController::class, 'delierCompte'])->name('clients.delier-compte');
    });

    // Caisse (caissiere + patron)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/caisse', [CashSessionController::class, 'index'])->name('caisse.index');
        Route::get('/caisse/{id}', [CashSessionController::class, 'show'])->name('caisse.show');
        Route::post('/caisse/{id}/fermer', [CashSessionController::class, 'fermer'])->name('caisse.fermer');
        Route::get('/caisse/{id}/z-report', [CashSessionController::class, 'zReport'])->name('caisse.z-report');
    });

    // Ouverture de caisse (caissiere uniquement)
    Route::middleware(['role:caissiere'])->group(function () {
        Route::post('/caisse/ouvrir', [CashSessionController::class, 'ouvrir'])->name('caisse.ouvrir');
    });

    // Fournisseurs (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/fournisseurs', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/fournisseurs/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/fournisseurs', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/fournisseurs/{id}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::get('/fournisseurs/{id}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/fournisseurs/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/fournisseurs/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    // Factures fournisseurs (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/factures-fournisseurs', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoices.index');
        Route::get('/factures-fournisseurs/create', [PurchaseInvoiceController::class, 'create'])->name('purchase-invoices.create');
        Route::post('/factures-fournisseurs', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoices.store');
        Route::get('/factures-fournisseurs/{id}', [PurchaseInvoiceController::class, 'show'])->name('purchase-invoices.show');
        Route::post('/factures-fournisseurs/{id}/paiement', [PurchaseInvoiceController::class, 'paiement'])->name('purchase-invoices.paiement');
        Route::get('/factures-fournisseurs/{id}/imprimer', [PurchaseInvoiceController::class, 'print'])->name('purchase-invoices.print');
    });

    // Factures (caissière uniquement)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/factures', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/reparations/{repairId}/facture', [InvoiceController::class, 'creerDepuisReparation'])->name('invoices.create-from-repair');
        Route::get('/factures/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/factures/{id}/paiement', [InvoiceController::class, 'paiementFinal'])->name('invoices.paiement');
        Route::get('/factures/{id}/imprimer', [InvoiceController::class, 'print'])->name('invoices.print');
    });

    // Crédit revendeurs (caissière uniquement)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/credits', [CreditController::class, 'index'])->name('credit.index');
    });

    // Profile
    Route::put('/profil', [UserController::class, 'updateProfile'])->name('profile.update');

    // RGPD — anonymisation données client (patron uniquement)
    Route::middleware(['role:patron'])->group(function () {
        Route::post('/rgpd/anonymiser', [DataPrivacyController::class, 'anonymize'])->name('rgpd.anonymize');
    });

    // Inventaires (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/inventaires', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventaires/ouvrir', [InventoryController::class, 'ouvrir'])->name('inventory.ouvrir');
        Route::get('/inventaires/{id}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::post('/inventaires/{sessionId}/lignes/{lineId}', [InventoryController::class, 'saisir'])->name('inventory.saisir');
        Route::post('/inventaires/{id}/cloturer', [InventoryController::class, 'cloturer'])->name('inventory.cloturer');
        Route::get('/inventaires/{id}/rapport', [InventoryController::class, 'rapport'])->name('inventory.rapport');
    });

    // Bons de commande fournisseurs (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/bons-commande', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/bons-commande/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/bons-commande', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/bons-commande/{id}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/bons-commande/{id}/envoyer', [PurchaseOrderController::class, 'envoyer'])->name('purchase-orders.envoyer');
        Route::post('/bons-commande/{id}/reception', [PurchaseOrderController::class, 'reception'])->name('purchase-orders.reception');
        Route::post('/bons-commande/{id}/annuler', [PurchaseOrderController::class, 'annuler'])->name('purchase-orders.annuler');
        Route::get('/bons-commande/{id}/imprimer', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
    });

    // Dashboard analytique (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/analytique', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

    // Dashboard revendeur
    Route::get('/clients/{id}/dashboard', [ClientController::class, 'dashboard'])->name('clients.dashboard');

    // Planning technicien (patron + caissiere)
    Route::get('/planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::post('/planning/{repairId}/assigner', [PlanningController::class, 'assigner'])->name('planning.assigner');
    });

    // Garanties pièces
    Route::get('/garanties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::post('/garanties', [WarrantyController::class, 'store'])->name('warranties.store');
    Route::get('/garanties/{id}', [WarrantyController::class, 'show'])->name('warranties.show');
    Route::post('/garanties/{id}/utiliser', [WarrantyController::class, 'utiliser'])->name('warranties.utiliser');
    Route::get('/garanties/{id}/imprimer', [WarrantyController::class, 'print'])->name('warranties.print');

    // Étiquettes QR code (caissière uniquement)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/reparations/{id}/etiquette', [QrLabelController::class, 'repair'])->name('qr.repair');
        Route::post('/reparations/etiquettes-lot', [QrLabelController::class, 'repairsBatch'])->name('qr.batch');
    });

    // Config SMS (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::post('/parametres/sms', [SettingsController::class, 'updateSms'])->name('parametres.sms');
    });

    // Catalogue pannes par modèle d'appareil (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/catalogue-pannes', [PanneTemplateController::class, 'index'])->name('panne-templates.index');
        Route::post('/catalogue-pannes/modeles', [PanneTemplateController::class, 'storeModel'])->name('panne-templates.store-model');
        Route::delete('/catalogue-pannes/modeles/{id}', [PanneTemplateController::class, 'destroyModel'])->name('panne-templates.destroy-model');
        Route::post('/catalogue-pannes/modeles/{modelId}/pannes', [PanneTemplateController::class, 'storeTemplate'])->name('panne-templates.store-template');
        Route::delete('/catalogue-pannes/pannes/{templateId}', [PanneTemplateController::class, 'destroyTemplate'])->name('panne-templates.destroy-template');
    });
    Route::get('/catalogue-pannes/modeles/{modelId}/pannes', [PanneTemplateController::class, 'apiTemplates'])->name('panne-templates.api');

    // 2FA (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/2fa/gestion', [TwoFactorController::class, 'show'])->name('two-factor.show');
        Route::post('/2fa/activer', [TwoFactorController::class, 'activate'])->name('two-factor.activate');
        Route::post('/2fa/confirmer', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
        Route::post('/2fa/desactiver', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    });

    // Remboursement / avoir (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::post('/factures/{id}/rembourser', [RefundController::class, 'rembourser'])->name('invoices.rembourser');
    });

    // Compétences technicien (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/competences', [TechnicianSkillController::class, 'index'])->name('skills.index');
        Route::post('/competences', [TechnicianSkillController::class, 'store'])->name('skills.store');
        Route::delete('/competences/{id}', [TechnicianSkillController::class, 'destroy'])->name('skills.destroy');
    });

    // Exports CSV (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/export/{module}', [ExportController::class, 'export'])->name('export.module')
            ->where('module', 'factures-fournisseurs|credits|inventaires|transferts');
    });

    // Rapport de marge (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/rapport-marge', [MarginController::class, 'index'])->name('margin.index');
    });

    // Appareils non récupérés (patron only)
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/abandons', [AbandonController::class, 'index'])->name('abandons.index');
        Route::post('/abandons/{id}/mettre-en-vente', [AbandonController::class, 'mettreEnVente'])->name('abandons.mettre-en-vente');
        Route::post('/abandons/{id}/date-limite', [AbandonController::class, 'setDateLimite'])->name('abandons.date-limite');
    });

    // Relances automatiques (caissiere + patron)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/relances', [RelanceController::class, 'index'])->name('relances.index');
        Route::post('/relances/{id}/relancer', [RelanceController::class, 'relancer'])->name('relances.relancer');
    });

    // Photos réparation (stockées dans storage/app/repairs — accès authentifié uniquement)
    Route::post('/reparations/{id}/photos', [RepairPhotoController::class, 'store'])->name('repair-photos.store');
    Route::get('/photos/{photoId}', [RepairPhotoController::class, 'serve'])->name('repair-photos.serve');
    Route::delete('/photos/{photoId}', [RepairPhotoController::class, 'destroy'])->name('repair-photos.destroy');

    // Transferts intra-boutique (caissière uniquement)
    Route::middleware(['role:caissiere,patron'])->group(function () {
        Route::get('/transferts', [StockTransferController::class, 'index'])->name('transfers.index');
        Route::get('/transferts/{id}', [StockTransferController::class, 'show'])->name('transfers.show');
        Route::post('/transferts/{id}/valider-envoi', [StockTransferController::class, 'validerEnvoi'])->name('transfers.valider-envoi');
        Route::post('/transferts/{id}/valider-reception', [StockTransferController::class, 'validerReception'])->name('transfers.valider-reception');
    });
    Route::middleware(['role:patron'])->group(function () {
        Route::get('/transferts/create', [StockTransferController::class, 'create'])->name('transfers.create');
        Route::post('/transferts', [StockTransferController::class, 'store'])->name('transfers.store');
        Route::post('/transferts/{id}/annuler', [StockTransferController::class, 'annuler'])->name('transfers.annuler');
    });
});

