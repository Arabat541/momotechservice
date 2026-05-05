@extends('layouts.base')

@section('body')
@php
    $user = session('user_id') ? \App\Models\User::find(session('user_id')) : null;
    $role = session('user_role', 'caissiere');
    $currentShopId = session('current_shop_id');
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
['label' => 'Catalogue pannes',       'icon' => 'fa-book-medical',    'route' => 'panne-templates.index', 'roles' => ['patron']],
                ['label' => 'Relances',               'icon' => 'fa-bell',            'route' => 'relances.index',        'roles' => ['patron','caissiere']],
                ['label' => 'Appareils abandonnés',   'icon' => 'fa-clock-rotate-left','route' => 'abandons.index',       'roles' => ['patron']],
            ],
        ],
        [
            'title' => 'Ventes & Clients',
            'items' => [
                ['label' => 'Vente pièce détachée',   'icon' => 'fa-box',             'route' => 'article',           'roles' => ['caissiere','patron']],
                ['label' => 'Ventes en attente',      'icon' => 'fa-clock',           'route' => 'pending-sales.index','roles' => ['caissiere']],
                ['label' => 'Clients',                'icon' => 'fa-users',           'route' => 'clients.index',     'roles' => ['caissiere','patron']],
                ['label' => 'SAV',                    'icon' => 'fa-shield-halved',   'route' => 'sav.index',        'roles' => ['caissiere','patron']],
                ['label' => 'Garanties',              'icon' => 'fa-certificate',     'route' => 'warranties.index'],
                ['label' => 'Crédit revendeurs',      'icon' => 'fa-credit-card',     'route' => 'credit.index',     'roles' => ['caissiere','patron']],
                ['label' => 'Revendeurs',             'icon' => 'fa-users-gear',      'route' => 'credits.revendeurs','roles' => ['caissiere','patron']],
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
            'title' => 'Rapports',
            'items' => [
                ['label' => 'Rapport ventes',         'icon' => 'fa-chart-column',     'route' => 'reports.ventes',      'roles' => ['patron']],
                ['label' => 'Rapport réparations',    'icon' => 'fa-screwdriver-wrench','route' => 'reports.reparations', 'roles' => ['patron']],
                ['label' => 'Rapport stock',          'icon' => 'fa-warehouse',        'route' => 'reports.stock',       'roles' => ['patron']],
                ['label' => 'Rapport financier',      'icon' => 'fa-coins',            'route' => 'reports.financier',   'roles' => ['patron']],
            ],
        ],
        [
            'title' => 'Administration',
            'items' => [
['label' => 'Paramètres',             'icon' => 'fa-gear',            'route' => 'parametres',           'roles' => ['patron']],
                ['label' => 'Double auth. (2FA)',     'icon' => 'fa-shield-alt',      'route' => 'two-factor.show',      'roles' => ['patron']],
            ],
        ],
    ];
@endphp

<div class="min-h-screen flex" x-data="{ sidebarOpen: true, sections: {} }" x-cloak>
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

            {{-- Boutique label --}}
            <div x-show="sidebarOpen" class="px-4 mb-3">
                <div class="flex items-center gap-2 bg-white/20 rounded-lg px-3 py-2">
                    <i class="fas fa-store text-blue-200 text-sm flex-shrink-0"></i>
                    @if($role === 'patron')
                        <span class="text-white text-sm font-medium truncate">Toutes les boutiques</span>
                    @else
                        <span class="text-white text-sm font-medium truncate">{{ $currentShop?->nom ?? 'Ma boutique' }}</span>
                    @endif
                </div>
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
                    {{-- Section header — cliquable pour plier/déplier --}}
                    <div x-show="sidebarOpen" class="pt-3 pb-0 px-1">
                        <button type="button"
                                @click="sections['{{ $section['title'] }}'] = sections['{{ $section['title'] }}'] === false ? true : false"
                                class="w-full flex items-center justify-between group py-1 focus:outline-none">
                            <span class="text-xs font-semibold text-blue-300 uppercase tracking-widest group-hover:text-white transition-colors">{{ $section['title'] }}</span>
                            <i class="fas fa-chevron-down text-blue-400/70 text-[9px] transition-transform duration-200"
                               :class="sections['{{ $section['title'] }}'] === false ? '-rotate-90' : 'rotate-0'"></i>
                        </button>
                    </div>
                    <div x-show="!sidebarOpen" class="border-t border-white/10 my-1"></div>

                    {{-- Items — masqués si section repliée (sauf quand sidebar réduite) --}}
                    <div x-show="!sidebarOpen || sections['{{ $section['title'] }}'] !== false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1">
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
                    </div>
                    @else
                    {{-- Section sans titre (Tableau de bord) — toujours visible --}}
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
                    @endif
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
                <div class="flex items-center gap-3">
                    {{-- Recherche globale Ctrl+K --}}
                    <div x-data="globalSearch()" class="relative" @click.outside="open = false">
                        <div class="relative flex items-center">
                            <i class="fas fa-magnifying-glass absolute left-3 text-gray-400 text-sm pointer-events-none z-10"></i>
                            <input
                                x-ref="searchInput"
                                x-model="q"
                                @input="onInput"
                                @focus="open = q.length >= 2"
                                @keydown.escape="open = false; $el.blur()"
                                type="text"
                                placeholder="Rechercher… (Ctrl+K)"
                                autocomplete="off"
                                class="w-64 pl-9 pr-8 py-2 text-sm bg-white border border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-400 focus:outline-none placeholder-gray-400"
                            >
                            <div x-show="loading" class="absolute right-3 pointer-events-none">
                                <svg class="animate-spin h-3.5 w-3.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                            </div>
                            <button x-show="q && !loading" @click="q=''; results=[]; total=0; open=false; $refs.searchInput.focus()"
                                    class="absolute right-3 text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>

                        {{-- Dropdown résultats --}}
                        <div x-show="open && q.length >= 2"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-1 w-96 bg-white rounded-xl shadow-xl border border-gray-200 z-50 max-h-[28rem] overflow-y-auto"
                             style="display:none;">

                            {{-- Chargement --}}
                            <div x-show="loading" class="flex items-center gap-2 px-4 py-4 text-sm text-gray-400">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                Recherche en cours…
                            </div>

                            {{-- Aucun résultat --}}
                            <div x-show="!loading && total === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                                <i class="fas fa-magnifying-glass text-2xl mb-2 block text-gray-300"></i>
                                Aucun résultat pour « <span class="font-medium text-gray-600" x-text="q"></span> »
                            </div>

                            {{-- Résultats groupés --}}
                            <template x-if="!loading && total > 0">
                                <div>
                                    <template x-for="group in grouped" :key="group.type">
                                        <div>
                                            {{-- En-tête groupe --}}
                                            <div class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wide border-b"
                                                 :class="typeConfig[group.type].cls">
                                                <i class="fas mr-1.5" :class="typeConfig[group.type].icon"></i>
                                                <span x-text="typeConfig[group.type].label"></span>
                                                <span class="ml-1 opacity-60" x-text="'(' + group.items.length + ')'"></span>
                                            </div>
                                            {{-- Items --}}
                                            <template x-for="item in group.items" :key="item.url">
                                                <a :href="item.url"
                                                   class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0 cursor-pointer">
                                                    <div class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center"
                                                         :class="typeConfig[item.type].iconBg">
                                                        <i class="fas text-xs" :class="'fa-' + item.icon + ' ' + typeConfig[item.type].iconColor"></i>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-semibold text-gray-800 truncate" x-text="item.label"></p>
                                                        <p class="text-xs text-gray-400 truncate" x-text="item.sublabel"></p>
                                                    </div>
                                                    <i class="fas fa-chevron-right text-gray-300 text-xs flex-shrink-0"></i>
                                                </a>
                                            </template>
                                        </div>
                                    </template>
                                    {{-- Pied dropdown --}}
                                    <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 text-xs text-gray-400 flex justify-between">
                                        <span x-text="total + ' résultat' + (total > 1 ? 's' : '')"></span>
                                        <span>↵ pour naviguer · Échap pour fermer</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Cloche notifications --}}
                    <div x-data="{ bellOpen: false }" class="relative">
                        <button @click="bellOpen = !bellOpen"
                                class="relative flex items-center justify-center w-10 h-10 bg-white rounded-lg shadow border border-gray-200 text-gray-600 hover:text-blue-600 hover:border-blue-300 transition-colors">
                            <i class="fas fa-bell text-base"></i>
                            @if(($notifCount ?? 0) > 0)
                            <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 leading-none">
                                {{ ($notifCount ?? 0) > 9 ? '9+' : ($notifCount ?? 0) }}
                            </span>
                            @endif
                        </button>

                        {{-- Dropdown --}}
                        <div x-show="bellOpen"
                             @click.away="bellOpen = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden"
                             style="display:none;">
                            {{-- Header dropdown --}}
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                                <span class="text-sm font-semibold text-gray-700">
                                    Notifications
                                    @if(($notifCount ?? 0) > 0)
                                    <span class="ml-1 px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-bold">{{ $notifCount ?? 0 }}</span>
                                    @endif
                                </span>
                                @if(($notifCount ?? 0) > 0)
                                <form action="{{ route('notifications.read-all') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                        Tout marquer lu
                                    </button>
                                </form>
                                @endif
                            </div>

                            {{-- Liste --}}
                            <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                                @forelse($notifications ?? [] as $notif)
                                @php
                                    $borderColor = match($notif->type) {
                                        'stock_alerte'    => 'border-orange-400',
                                        'reparation_prete'=> 'border-green-400',
                                        'credit_depasse'  => 'border-red-400',
                                        default           => 'border-gray-300',
                                    };
                                    $iconClass = match($notif->type) {
                                        'stock_alerte'    => 'fa-triangle-exclamation text-orange-500',
                                        'reparation_prete'=> 'fa-check-circle text-green-500',
                                        'credit_depasse'  => 'fa-credit-card text-red-500',
                                        default           => 'fa-bell text-gray-400',
                                    };
                                    $bgColor = $notif->lu_at ? 'bg-white' : 'bg-blue-50';
                                @endphp
                                <div class="flex gap-3 px-4 py-3 {{ $bgColor }} border-l-4 {{ $borderColor }} hover:bg-gray-50 transition-colors">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <i class="fas {{ $iconClass }} text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $notif->titre }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $notif->message }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</p>
                                    </div>
                                    @if(!$notif->lu_at)
                                    <form action="{{ route('notifications.read', $notif->id) }}" method="POST" class="flex-shrink-0">
                                        @csrf
                                        <button type="submit" title="Marquer lu" class="text-gray-300 hover:text-blue-500 transition-colors mt-1">
                                            <i class="fas fa-circle-check text-sm"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                @empty
                                <div class="px-4 py-8 text-center text-gray-400 text-sm">
                                    <i class="fas fa-bell-slash text-2xl mb-2 block"></i>
                                    Aucune nouvelle notification
                                </div>
                                @endforelse
                            </div>

                            {{-- Footer --}}
                            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-center">
                                <a href="{{ route('notifications.index') }}"
                                   class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Voir toutes les notifications →
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Avatar --}}
                    <div class="flex items-center space-x-2 bg-white rounded-lg px-3 py-2 shadow border border-gray-200">
                        <span class="inline-flex w-8 h-8 rounded-full bg-blue-200 text-blue-700 items-center justify-center font-bold text-lg uppercase">
                            {{ substr(session('user_email', '?'), 0, 1) }}
                        </span>
                        <span class="text-sm font-medium text-gray-700">{{ session('user_email') }}</span>
                    </div>
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

    document.addEventListener('alpine:init', () => {
        Alpine.data('globalSearch', () => ({
            q: '',
            results: [],
            total: 0,
            loading: false,
            open: false,
            _timer: null,

            typeConfig: {
                reparation:  { label: 'Réparations',  cls: 'bg-blue-50 text-blue-700 border-blue-100',     icon: 'fa-wrench',        iconBg: 'bg-blue-100',   iconColor: 'text-blue-600'   },
                client:      { label: 'Clients',       cls: 'bg-purple-50 text-purple-700 border-purple-100',icon: 'fa-user',          iconBg: 'bg-purple-100', iconColor: 'text-purple-600' },
                stock:       { label: 'Articles',      cls: 'bg-orange-50 text-orange-700 border-orange-100',icon: 'fa-cube',          iconBg: 'bg-orange-100', iconColor: 'text-orange-600' },
                fournisseur: { label: 'Fournisseurs',  cls: 'bg-gray-100 text-gray-600 border-gray-200',    icon: 'fa-truck',         iconBg: 'bg-gray-100',   iconColor: 'text-gray-500'   },
                facture:     { label: 'Factures',      cls: 'bg-green-50 text-green-700 border-green-100',  icon: 'fa-file-invoice',  iconBg: 'bg-green-100',  iconColor: 'text-green-600'  },
            },

            get grouped() {
                const order = ['reparation', 'client', 'stock', 'fournisseur', 'facture'];
                const map = {};
                for (const r of this.results) {
                    if (!map[r.type]) map[r.type] = [];
                    map[r.type].push(r);
                }
                return order.filter(t => map[t]).map(t => ({ type: t, items: map[t] }));
            },

            init() {
                window.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        this.$refs.searchInput.focus();
                        if (this.q.length >= 2) this.open = true;
                    }
                    if (e.key === 'Escape' && this.open) {
                        this.open = false;
                        this.$refs.searchInput.blur();
                    }
                });
            },

            onInput() {
                clearTimeout(this._timer);
                if (this.q.length < 2) {
                    this.results = [];
                    this.total   = 0;
                    this.open    = false;
                    return;
                }
                this._timer = setTimeout(() => this.doSearch(), 300);
            },

            async doSearch() {
                this.loading = true;
                this.open    = true;
                try {
                    const res  = await fetch('/dashboard/search?q=' + encodeURIComponent(this.q), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    this.results = data.results || [];
                    this.total   = data.total   || 0;
                } catch (err) {
                    this.results = [];
                    this.total   = 0;
                }
                this.loading = false;
            },
        }));
    });
</script>
@endsection
