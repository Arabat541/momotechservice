@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-purple-950">
    {{-- Header --}}
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">MOMO TECH SERVICE</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('track') }}" class="text-blue-200 hover:text-white text-sm font-medium">
                    <i class="fas fa-search mr-1"></i> Suivre ma réparation
                </a>
                <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                    <i class="fas fa-sign-in-alt mr-1"></i> Connexion
                </a>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="max-w-7xl mx-auto px-4 py-16 text-center">
        <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">Réparation de smartphones</h2>
        <p class="text-xl text-blue-200 mb-8">La technologie au bout des doigts...</p>
        <a href="{{ route('track') }}" class="inline-flex items-center bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-3 rounded-lg font-semibold text-lg shadow-lg">
            <i class="fas fa-search mr-2"></i> Suivre ma réparation
        </a>
    </section>

    {{-- Shops --}}
    <section class="max-w-7xl mx-auto px-4 pb-16">
        <h3 class="text-2xl font-bold text-white mb-8 text-center">Nos boutiques</h3>
        @if($shops->isEmpty())
            <p class="text-center text-blue-200">Aucune boutique disponible.</p>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($shops as $shop)
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 hover:bg-white/20 transition-all">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-blue-500/30 rounded-full flex items-center justify-center">
                        <i class="fas fa-store text-blue-300 text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-white">{{ $shop['companyInfo']['nom'] ?? $shop['nom'] }}</h4>
                        @if(!empty($shop['companyInfo']['slogan']))
                        <p class="text-xs text-blue-300 italic">{{ $shop['companyInfo']['slogan'] }}</p>
                        @endif
                    </div>
                </div>
                <div class="space-y-2 text-sm text-blue-100">
                    @if(!empty($shop['adresse']) || !empty($shop['companyInfo']['adresse']))
                    <p><i class="fas fa-map-marker-alt mr-2 text-blue-400"></i> {{ $shop['companyInfo']['adresse'] ?? $shop['adresse'] }}</p>
                    @endif
                    @if(!empty($shop['telephone']) || !empty($shop['companyInfo']['telephone']))
                    <p><i class="fas fa-phone mr-2 text-blue-400"></i> {{ $shop['companyInfo']['telephone'] ?? $shop['telephone'] }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

    {{-- Footer --}}
    <footer class="bg-black/30 border-t border-white/10 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-blue-300">
            <p>&copy; {{ date('Y') }} MOMO TECH SERVICE. Tous droits réservés.</p>
        </div>
    </footer>
</div>
@endsection
