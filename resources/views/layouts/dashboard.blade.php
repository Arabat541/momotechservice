@extends('layouts.base')

@section('body')
@php
    $user = session('user_id') ? \App\Models\User::find(session('user_id')) : null;
    $role = session('user_role', 'employé');
    $currentShopId = session('current_shop_id');
    $shops = $role === 'patron' ? \App\Models\Shop::all() : ($user ? $user->shops : collect());
    $currentShop = $currentShopId ? \App\Models\Shop::find($currentShopId) : null;

    $menuItems = [
        ['label' => 'Tableau de bord', 'icon' => 'fa-chart-line', 'route' => 'dashboard'],
        ['label' => 'Réparation sur place', 'icon' => 'fa-mobile-screen', 'route' => 'reparations.place'],
        ['label' => 'Réparation sur rdv', 'icon' => 'fa-calendar', 'route' => 'reparations.rdv'],
        ['label' => 'Vente de Pièce détachée', 'icon' => 'fa-box', 'route' => 'article'],
        ['label' => 'Liste-réparation', 'icon' => 'fa-list', 'route' => 'reparations.liste'],
        ['label' => 'SAV', 'icon' => 'fa-shield-halved', 'route' => 'sav.index'],
        ['label' => 'Gestion des stocks', 'icon' => 'fa-boxes-stacked', 'route' => 'stocks.index', 'patron' => true],
        ['label' => 'Paramètres', 'icon' => 'fa-gear', 'route' => 'parametres', 'patron' => true],
    ];
@endphp

<div class="min-h-screen flex" x-data="{ sidebarOpen: true }" x-cloak>
    {{-- Sidebar --}}
    <aside class="sidebar-gradient shadow-2xl flex flex-col justify-between fixed left-0 top-0 bottom-0 z-40 overflow-x-hidden transition-all duration-300"
           :class="sidebarOpen ? 'w-64' : 'w-20'">
        <div>
            {{-- Header --}}
            <div class="p-4 flex items-center h-20" :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                <h1 x-show="sidebarOpen" class="text-xl font-bold text-white truncate">MOMO TECH</h1>
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
            <nav class="space-y-1" :class="sidebarOpen ? 'px-4' : 'px-2'">
                @foreach($menuItems as $item)
                    @if(!empty($item['patron']) && $role !== 'patron')
                        @continue
                    @endif
                    <a href="{{ route($item['route']) }}"
                       class="w-full flex items-center space-x-3 rounded-lg transition-all duration-200
                              {{ request()->routeIs($item['route']) ? 'bg-white/20 text-white shadow-md' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}"
                       :class="sidebarOpen ? 'px-4 py-3' : 'p-3 justify-center'"
                       title="{{ $item['label'] }}">
                        <i class="fas {{ $item['icon'] }}" :class="sidebarOpen ? 'text-lg' : 'text-xl'"></i>
                        <span x-show="sidebarOpen" class="font-medium text-sm whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
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
