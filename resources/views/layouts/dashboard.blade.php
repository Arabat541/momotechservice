@extends('layouts.base')

@section('body')
@php
    $user = session('user_id') ? \App\Models\User::find(session('user_id')) : null;
    $role = session('user_role', 'reparateur');
    $currentShopId = session('current_shop_id');
    $shops = $role === 'patron' ? \App\Models\Shop::all() : ($user ? $user->shops : collect());
    $currentShop = $currentShopId ? \App\Models\Shop::find($currentShopId) : null;

    $menuSections = [
        [
            'title' => null,
            'items' => [
                ['label' => 'Tableau de bord',        'icon' => 'fa-chart-line',      'route' => 'dashboard'],
                ['label' => 'Analytique',             'icon' => 'fa-chart-bar',       'route' => 'analytics.index',  'roles' => ['patron']],
            ],
        ],
        [
            'title' => 'Réparations',
            'items' => [
                ['label' => 'Nouvelle réparation',    'icon' => 'fa-mobile-screen',   'route' => 'reparations.place',   'roles' => ['caissiere']],
                ['label' => 'Réparation sur RDV',     'icon' => 'fa-calendar',        'route' => 'reparations.rdv',     'roles' => ['caissiere']],
                ['label' => 'Liste réparations',      'icon' => 'fa-list',            'route' => 'reparations.liste'],
                ['label' => 'Planning réparateurs',   'icon' => 'fa-calendar-week',   'route' => 'planning.index'],
                ['label' => 'Catalogue pannes',       'icon' => 'fa-book-medical',    'route' => 'panne-templates.index', 'roles' => ['patron']],
                ['label' => 'Relances',               'icon' => 'fa-bell',            'route' => 'relances.index',        'roles' => ['patron','caissiere']],
                ['label' => 'Appareils abandonnés',   'icon' => 'fa-clock-rotate-left','route' => 'abandons.index',       'roles' => ['patron']],
            ],
        ],
        [
            'title' => 'Ventes & Clients',
            'items' => [
                ['label' => 'Vente pièce détachée',   'icon' => 'fa-box',             'route' => 'article',          'roles' => ['caissiere','patron']],
                ['label' => 'Clients',                'icon' => 'fa-users',           'route' => 'clients.index',    'roles' => ['caissiere','patron']],
                ['label' => 'SAV',                    'icon' => 'fa-shield-halved',   'route' => 'sav.index',        'roles' => ['caissiere','patron']],
                ['label' => 'Garanties',              'icon' => 'fa-certificate',     'route' => 'warranties.index'],
                ['label' => 'Crédit revendeurs',      'icon' => 'fa-credit-card',     'route' => 'credit.index',     'roles' => ['caissiere','patron']],
            ],
        ],
        [
            'title' => 'Caisse & Factures',
            'items' => [
                ['label' => 'Caisse',                 'icon' => 'fa-cash-register',   'route' => 'caisse.index',     'roles' => ['patron','caissiere']],
                ['label' => 'Factures clients',       'icon' => 'fa-file-invoice',    'route' => 'invoices.index',   'roles' => ['caissiere','patron']],
                ['label' => 'Rapport de marge',       'icon' => 'fa-percent',         'route' => 'margin.index',     'roles' => ['patron']],
            ],
        ],
        [
            'title' => 'Stock & Fournisseurs',
            'items' => [
                ['label' => 'Gestion des stocks',     'icon' => 'fa-boxes-stacked',   'route' => 'stocks.index',              'roles' => ['patron']],
                ['label' => 'Transferts',             'icon' => 'fa-exchange-alt',    'route' => 'transfers.index',           'roles' => ['caissiere','patron']],
                ['label' => 'Inventaires',            'icon' => 'fa-clipboard-list',  'route' => 'inventory.index',           'roles' => ['patron']],
                ['label' => 'Fournisseurs',           'icon' => 'fa-truck',           'route' => 'suppliers.index',           'roles' => ['patron']],
                ['label' => 'Factures fourn.',        'icon' => 'fa-file-invoice-dollar','route' => 'purchase-invoices.index','roles' => ['patron']],
                ['label' => 'Bons de commande',       'icon' => 'fa-clipboard',       'route' => 'purchase-orders.index',     'roles' => ['patron']],
            ],
        ],
        [
            'title' => 'Administration',
            'items' => [
                ['label' => 'Compétences répar.',     'icon' => 'fa-user-cog',        'route' => 'skills.index',         'roles' => ['patron']],
                ['label' => 'Paramètres',             'icon' => 'fa-gear',            'route' => 'parametres',           'roles' => ['patron']],
                ['label' => 'Double auth. (2FA)',     'icon' => 'fa-shield-alt',      'route' => 'two-factor.show',      'roles' => ['patron']],
            ],
        ],
    ];
@endphp

<div class="min-h-screen flex" x-data="{ sidebarOpen: true }" x-cloak>
    {{-- Sidebar --}}
    <aside class="sidebar-gradient shadow-2xl flex flex-col justify-between fixed left-0 top-0 bottom-0 z-40 overflow-x-hidden transition-all duration-300"
           :class="sidebarOpen ? 'w-64' : 'w-20'">
        <div>
            {{-- Header --}}
            <div class="p-4 flex items-center h-20" :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                <div x-show="sidebarOpen" class="flex items-center gap-2">
                    <img src="/images/logo-app.png" alt="MTS" class="w-10 h-10 rounded-full">
                    <h1 class="text-xl font-bold text-white truncate">MOMO TECH</h1>
                </div>
                <img x-show="!sidebarOpen" src="/images/logo-app.png" alt="MTS" class="w-10 h-10 rounded-full">
                <button @click="sidebarOpen = !sidebarOpen" class="text-white hover:bg-white/10 p-2 rounded-lg">
                    <i class="fas text-lg" :class="sidebarOpen ? 'fa-xmark' : 'fa-bars'"></i>
                </button>
            </div>

            {{-- Shop Selector --}}
            <div x-show="sidebarOpen" class="px-4 mb-3">
                <form action="{{ route('shop.switch') }}" method="POST">
                    @csrf
                    <select name="shop_id" onchange="this.form.submit()"
                            class="w-full text-sm bg-white/20 border-blue-400 text-white rounded-lg px-3 py-2 focus:ring-blue-300">
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ $currentShopId === $shop->id ? 'selected' : '' }}
                                    class="text-gray-800">{{ $shop->nom }}</option>
                        @endforeach
                    </select>
                </form>
                @if($role === 'patron')
                    <button onclick="document.getElementById('createShopModal').classList.remove('hidden')"
                            class="mt-2 w-full text-xs text-blue-200 hover:text-white flex items-center justify-center gap-1">
                        <i class="fas fa-plus"></i> <span x-show="sidebarOpen">Nouvelle boutique</span>
                    </button>
                @endif
            </div>

            {{-- Navigation --}}
            <nav class="space-y-0.5 overflow-y-auto max-h-[calc(100vh-200px)]" :class="sidebarOpen ? 'px-3' : 'px-2'">
                @foreach($menuSections as $section)
                    @php
                        $visibleItems = array_filter($section['items'], function($item) use ($role) {
                            if (empty($item['roles'])) return true;
                            return in_array($role, $item['roles']);
                        });
                    @endphp
                    @if(empty($visibleItems)) @continue @endif

                    @if($section['title'])
                    <div x-show="sidebarOpen" class="pt-3 pb-1 px-1">
                        <span class="text-xs font-semibold text-blue-300 uppercase tracking-widest">{{ $section['title'] }}</span>
                    </div>
                    <div x-show="!sidebarOpen" class="border-t border-white/10 my-1"></div>
                    @endif

                    @foreach($visibleItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="w-full flex items-center space-x-3 rounded-lg transition-all duration-200
                              {{ request()->routeIs($item['route']) ? 'bg-white/20 text-white shadow-md' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}"
                       :class="sidebarOpen ? 'px-3 py-2' : 'p-3 justify-center'"
                       title="{{ $item['label'] }}">
                        <i class="fas {{ $item['icon'] }} flex-shrink-0" :class="sidebarOpen ? 'text-base w-5 text-center' : 'text-xl'"></i>
                        <span x-show="sidebarOpen" class="font-medium text-sm whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
                    @endforeach
                @endforeach
            </nav>
        </div>

        {{-- Logout --}}
        <div :class="sidebarOpen ? 'p-4' : 'p-2'">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full flex items-center space-x-3 rounded-lg text-red-200 hover:bg-red-500/30 hover:text-red-100 transition-colors"
                        :class="sidebarOpen ? 'px-4 py-3' : 'p-3 justify-center'">
                    <i class="fas fa-right-from-bracket" :class="sidebarOpen ? 'text-lg' : 'text-xl'"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm whitespace-nowrap">Déconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 p-4 sm:p-6 overflow-y-auto transition-all duration-300"
          :class="sidebarOpen ? 'ml-64' : 'ml-20'">
        <div class="max-w-full mx-auto">
            {{-- Header bar --}}
            <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <h2 class="text-xl sm:text-2xl font-bold text-gradient">@yield('page-title', 'Tableau de Bord')</h2>
                <div class="flex items-center space-x-2 bg-white rounded-lg px-3 py-2 shadow border border-gray-200">
                    <span class="inline-flex w-8 h-8 rounded-full bg-blue-200 text-blue-700 items-center justify-center font-bold text-lg uppercase">
                        {{ substr(session('user_email', '?'), 0, 1) }}
                    </span>
                    <span class="text-sm font-medium text-gray-700">{{ session('user_email') }}</span>
                </div>
            </div>

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg flex items-center justify-between" id="flash-success">
                    <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg flex items-center justify-between">
                    <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">&times;</button>
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Page Content --}}
            @yield('content')
        </div>
    </main>
</div>

{{-- Create Shop Modal --}}
<div id="createShopModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Créer une boutique</h3>
            <button onclick="document.getElementById('createShopModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <form action="{{ route('shops.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                <input type="text" name="nom" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <input type="text" name="adresse" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <input type="text" name="telephone" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-semibold">Créer</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    // Auto-hide flash messages after 5 seconds
    setTimeout(() => {
        const flash = document.getElementById('flash-success');
        if (flash) flash.remove();
    }, 5000);
</script>
@endsection
