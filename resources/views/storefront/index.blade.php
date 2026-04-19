@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-white font-sans" x-data="{ mobileMenu: false }">

    {{-- ══ HEADER ══════════════════════════════════════════════════ --}}
    <header class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                <img src="/images/logo-app.png" alt="Momo Tech Service" class="h-10 w-10 object-contain">
                <span class="hidden sm:block font-bold text-gray-900 text-base">MOMO TECH <span class="text-blue-600">SERVICE</span></span>
            </a>

            {{-- Nav --}}
            <nav class="hidden md:flex items-center gap-6 ml-6">
                <a href="{{ route('home') }}" class="text-sm font-medium {{ !request('categorie') && !request('search') ? 'text-blue-600 border-b-2 border-blue-600 pb-0.5' : 'text-gray-600 hover:text-blue-600' }}">Accueil</a>
                <a href="{{ route('home') }}?categorie=" class="text-sm font-medium text-gray-600 hover:text-blue-600">Catalogue</a>
                <a href="{{ route('track') }}" class="text-sm font-medium text-gray-600 hover:text-blue-600">Suivi de réparation</a>
            </nav>

            {{-- Search --}}
            <form method="GET" class="flex-1 max-w-md ml-auto">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                        placeholder="Rechercher un produit..."
                        class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @if($categorie)
                    <input type="hidden" name="categorie" value="{{ $categorie }}">
                    @endif
                </div>
            </form>

            {{-- Auth --}}
            <a href="{{ route('login') }}" class="hidden md:flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shrink-0">
                <i class="fas fa-sign-in-alt"></i> Connexion
            </a>
        </div>
    </header>

    {{-- ══ HERO ══════════════════════════════════════════════════════ --}}
    <section class="bg-gradient-to-br from-slate-900 via-blue-950 to-blue-900 text-white">
        <div class="max-w-7xl mx-auto px-4 py-16 flex flex-col md:flex-row items-center gap-10">
            <div class="flex-1">
                <span class="inline-flex items-center gap-2 bg-white/10 border border-white/20 text-blue-200 text-xs font-semibold px-3 py-1 rounded-full mb-5">
                    <i class="fas fa-store"></i> Boutique en ligne officielle
                </span>
                <h2 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">
                    Trouvez le<br><span class="text-blue-400">téléphone idéal</span>
                </h2>
                <p class="text-blue-200 text-lg mb-8">Téléphones, accessoires et pièces détachées<br>disponibles dans nos boutiques.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="#catalogue" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-th-large"></i> Explorer le catalogue
                    </a>
                    <a href="{{ route('track') }}" class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-search"></i> Suivi de réparation
                    </a>
                </div>
                <div class="flex gap-8 mt-10">
                    <div><div class="text-2xl font-bold">100%</div><div class="text-blue-300 text-xs">Authentique</div></div>
                    <div><div class="text-2xl font-bold">Rapide</div><div class="text-blue-300 text-xs">Service SAV</div></div>
                    <div><div class="text-2xl font-bold">Garanti</div><div class="text-blue-300 text-xs">Qualité</div></div>
                </div>
            </div>
            <div class="hidden md:flex items-center justify-center w-64 h-64 bg-white/5 rounded-3xl border border-white/10">
                <i class="fas fa-mobile-screen-button text-9xl text-blue-400/50"></i>
            </div>
        </div>
    </section>

    {{-- ══ ONGLETS CATÉGORIES ══════════════════════════════════════════ --}}
    <section class="border-b border-gray-100 bg-white sticky top-[65px] z-40">
        <div class="max-w-7xl mx-auto px-4 overflow-x-auto">
            <div class="flex items-center gap-2 py-3 min-w-max">
                <a href="{{ route('home') }}{{ $search ? '?search='.$search : '' }}"
                    class="flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                    {{ !$categorie ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    <i class="fas fa-th text-xs"></i> Tous
                    <span class="text-xs {{ !$categorie ? 'opacity-80' : 'text-gray-400' }}">({{ $totalProduits }})</span>
                </a>
                @foreach($categoriesCount as $cat => $count)
                <a href="{{ route('home') }}?categorie={{ urlencode($cat) }}{{ $search ? '&search='.$search : '' }}"
                    class="flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                    {{ $categorie === $cat ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ strtoupper($cat) }}
                    <span class="text-xs {{ $categorie === $cat ? 'opacity-80' : 'text-gray-400' }}">({{ $count }})</span>
                </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══ NOS CATÉGORIES (affiché seulement sur la page d'accueil sans filtre) ══ --}}
    @if(!$categorie && !$search)
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="text-center mb-8">
            <h3 class="text-2xl font-bold text-gray-900">Nos <span class="text-blue-600">catégories</span></h3>
            <p class="text-gray-500 text-sm mt-1">Trouvez exactement ce que vous cherchez</p>
        </div>
        @php
            $iconesCat = [
                'telephone'      => ['icon' => 'fa-mobile-screen-button', 'color' => 'bg-purple-100 text-purple-600'],
                'téléphone'      => ['icon' => 'fa-mobile-screen-button', 'color' => 'bg-purple-100 text-purple-600'],
                'accessoire'     => ['icon' => 'fa-headphones',            'color' => 'bg-green-100 text-green-600'],
                'accessoires'    => ['icon' => 'fa-headphones',            'color' => 'bg-green-100 text-green-600'],
                'écran'          => ['icon' => 'fa-display',               'color' => 'bg-blue-100 text-blue-600'],
                'ecran'          => ['icon' => 'fa-display',               'color' => 'bg-blue-100 text-blue-600'],
                'batterie'       => ['icon' => 'fa-battery-full',          'color' => 'bg-yellow-100 text-yellow-600'],
                'pièce détachée' => ['icon' => 'fa-screwdriver-wrench',    'color' => 'bg-orange-100 text-orange-600'],
                'pièces'         => ['icon' => 'fa-screwdriver-wrench',    'color' => 'bg-orange-100 text-orange-600'],
            ];
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($categoriesCount as $cat => $count)
            @php
                $key = mb_strtolower($cat);
                $style = $iconesCat[$key] ?? ['icon' => 'fa-box', 'color' => 'bg-gray-100 text-gray-600'];
            @endphp
            <a href="{{ route('home') }}?categorie={{ urlencode($cat) }}"
                class="group flex flex-col items-center p-6 bg-white rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-md transition-all text-center">
                <div class="w-16 h-16 {{ $style['color'] }} rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="fas {{ $style['icon'] }} text-2xl"></i>
                </div>
                <p class="font-semibold text-gray-900 capitalize">{{ $cat }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $count }} produit(s)</p>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ══ DERNIERS ARRIVAGES (accueil sans filtre) ══════════════════ --}}
    @if(!$categorie && !$search && $derniersArrivages->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 pb-12">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Derniers <span class="text-blue-600">arrivages</span></h3>
                <p class="text-gray-500 text-sm">Nos produits les plus récents</p>
            </div>
            <a href="{{ route('home') }}?categorie=" class="text-sm text-blue-600 hover:underline font-medium">
                Voir tout →
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($derniersArrivages as $produit)
                @include('storefront._product_card', ['produit' => $produit])
            @endforeach
        </div>
    </section>
    @endif

    {{-- ══ CATALOGUE COMPLET ══════════════════════════════════════════ --}}
    <section id="catalogue" class="max-w-7xl mx-auto px-4 pb-16">
        @if($categorie || $search)
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    @if($categorie) <span class="capitalize">{{ $categorie }}</span>
                    @elseif($search) Résultats pour « {{ $search }} »
                    @endif
                </h3>
                <p class="text-gray-500 text-sm">{{ $produits->count() }} produit(s) trouvé(s)</p>
            </div>
            <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-blue-600 underline">← Retour</a>
        </div>
        @elseif($produits->isNotEmpty())
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Tous les <span class="text-blue-600">produits</span></h3>
                <p class="text-gray-500 text-sm">{{ $produits->count() }} produit(s) disponible(s)</p>
            </div>
        </div>
        @endif

        @if($produits->isEmpty())
        <div class="text-center py-20 bg-gray-50 rounded-2xl">
            <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg font-medium">Aucun produit disponible</p>
            <a href="{{ route('home') }}" class="mt-4 inline-block text-blue-600 hover:underline text-sm">Voir tous les produits</a>
        </div>
        @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($produits as $produit)
                @include('storefront._product_card', ['produit' => $produit])
            @endforeach
        </div>
        @endif
    </section>

    {{-- ══ NOS BOUTIQUES ══════════════════════════════════════════════ --}}
    @if($shops->isNotEmpty())
    <section class="bg-gray-50 border-t border-gray-100 py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-10">
                <h3 class="text-2xl font-bold text-gray-900">Nos <span class="text-blue-600">boutiques</span></h3>
                <p class="text-gray-500 text-sm mt-1">Retrouvez-nous dans nos points de vente</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($shops as $shop)
                <div class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-store text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $shop['nom'] }}</h4>
                        </div>
                    </div>
                    <div class="space-y-1.5 text-sm text-gray-600 mb-5">
                        @if(!empty($shop['adresse']))
                        <p class="flex items-start gap-2"><i class="fas fa-map-marker-alt text-blue-400 mt-0.5 shrink-0"></i>{{ $shop['adresse'] }}</p>
                        @endif
                        @if(!empty($shop['telephone']))
                        <p class="flex items-center gap-2"><i class="fas fa-phone text-blue-400 shrink-0"></i>{{ $shop['telephone'] }}</p>
                        @endif
                    </div>
                    <a href="{{ route('home') }}?boutique={{ $shop['id'] }}"
                        class="flex items-center justify-center gap-2 w-full border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg py-2.5 text-sm font-semibold transition-colors">
                        Voir les produits <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ══ FOOTER ══════════════════════════════════════════════════════ --}}
    <footer class="bg-slate-900 text-white pt-12 pb-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 pb-10 border-b border-white/10">
                {{-- Brand --}}
                <div class="md:col-span-1">
                    <div class="flex items-center gap-3 mb-3">
                        <img src="/images/logo-app.png" alt="Momo Tech Service" class="h-10 w-10 object-contain rounded-full bg-white p-0.5">
                        <span class="text-white font-bold text-base">MOMO TECH <span class="text-blue-400">SERVICE</span></span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">Téléphones, accessoires et pièces détachées. Qualité garantie.</p>
                </div>
                {{-- Navigation --}}
                <div>
                    <h5 class="font-semibold text-sm uppercase tracking-wide text-gray-300 mb-4">Navigation</h5>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('home') }}" class="hover:text-white flex items-center gap-2"><i class="fas fa-chevron-right text-xs"></i> Accueil</a></li>
                        <li><a href="{{ route('home') }}?categorie=" class="hover:text-white flex items-center gap-2"><i class="fas fa-chevron-right text-xs"></i> Catalogue</a></li>
                        <li><a href="{{ route('track') }}" class="hover:text-white flex items-center gap-2"><i class="fas fa-chevron-right text-xs"></i> Suivi de réparation</a></li>
                    </ul>
                </div>
                {{-- Catégories --}}
                <div>
                    <h5 class="font-semibold text-sm uppercase tracking-wide text-gray-300 mb-4">Catégories</h5>
                    <ul class="space-y-2 text-sm text-gray-400">
                        @foreach($categoriesCount->keys()->take(5) as $cat)
                        <li><a href="{{ route('home') }}?categorie={{ urlencode($cat) }}" class="hover:text-white flex items-center gap-2 capitalize"><i class="fas fa-chevron-right text-xs"></i> {{ $cat }}</a></li>
                        @endforeach
                    </ul>
                </div>
                {{-- Contact --}}
                <div>
                    <h5 class="font-semibold text-sm uppercase tracking-wide text-gray-300 mb-4">Contact</h5>
                    <ul class="space-y-2.5 text-sm text-gray-400">
                        @if(!empty($contactPrincipal['telephone']))
                        <li class="flex items-center gap-2">
                            <i class="fas fa-phone text-blue-400 text-xs w-4 text-center"></i>
                            <a href="tel:{{ $contactPrincipal['telephone'] }}" class="hover:text-white">{{ $contactPrincipal['telephone'] }}</a>
                        </li>
                        @endif
                        @if(!empty($contactPrincipal['whatsapp']))
                        <li class="flex items-center gap-2">
                            <i class="fab fa-whatsapp text-green-400 text-xs w-4 text-center"></i>
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $contactPrincipal['whatsapp']) }}" target="_blank" class="hover:text-white">{{ $contactPrincipal['whatsapp'] }}</a>
                        </li>
                        @endif
                        @if(!empty($contactPrincipal['adresse']))
                        <li class="flex items-start gap-2">
                            <i class="fas fa-map-marker-alt text-blue-400 text-xs w-4 text-center mt-0.5"></i>
                            <span>{{ $contactPrincipal['adresse'] }}</span>
                        </li>
                        @endif
                        @if(!empty($contactPrincipal['email']))
                        <li class="flex items-center gap-2">
                            <i class="fas fa-envelope text-blue-400 text-xs w-4 text-center"></i>
                            <a href="mailto:{{ $contactPrincipal['email'] }}" class="hover:text-white">{{ $contactPrincipal['email'] }}</a>
                        </li>
                        @endif
                        @if(!empty($contactPrincipal['facebook']))
                        <li class="flex items-center gap-2">
                            <i class="fab fa-facebook text-blue-400 text-xs w-4 text-center"></i>
                            <a href="{{ $contactPrincipal['facebook'] }}" target="_blank" class="hover:text-white">Facebook</a>
                        </li>
                        @endif
                        @if(!empty($contactPrincipal['instagram']))
                        <li class="flex items-center gap-2">
                            <i class="fab fa-instagram text-pink-400 text-xs w-4 text-center"></i>
                            <a href="{{ $contactPrincipal['instagram'] }}" target="_blank" class="hover:text-white">Instagram</a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="pt-6 text-center text-xs text-gray-500">
                &copy; {{ date('Y') }} MOMO TECH SERVICE. Tous droits réservés.
            </div>
        </div>
    </footer>
</div>
@endsection
