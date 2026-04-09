@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-purple-950 flex flex-col">
    {{-- Header --}}
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-2xl font-bold text-white">MOMO TECH SERVICE</a>
            <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                <i class="fas fa-sign-in-alt mr-1"></i> Connexion
            </a>
        </div>
    </header>

    {{-- Track form --}}
    <main class="flex-1 flex items-center justify-center px-4 py-16">
        <div class="w-full max-w-md">
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-8 border border-white/20 shadow-2xl">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-blue-500/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-search text-blue-300 text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white">Suivre ma réparation</h2>
                    <p class="text-blue-200 text-sm mt-1">Entrez votre numéro de réparation</p>
                </div>

                <form action="{{ route('track.search') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <input type="text" name="numero" required
                               placeholder="Ex: REP-XXXXXXXX"
                               value="{{ $numero ?? '' }}"
                               class="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-blue-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg text-center tracking-wider">
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 rounded-lg font-semibold">
                        <i class="fas fa-search mr-2"></i> Rechercher
                    </button>
                </form>

                {{-- Error --}}
                @if(isset($error))
                <div class="mt-4 p-4 bg-red-500/20 border border-red-400/30 rounded-lg text-red-200 text-center">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $error }}
                </div>
                @endif

                {{-- Result --}}
                @if(isset($repair))
                <div class="mt-6 bg-white/10 rounded-lg p-4 border border-white/20 space-y-3">
                    <h3 class="font-bold text-white text-center text-lg border-b border-white/20 pb-2">
                        {{ $repair['numeroReparation'] }}
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-blue-100">
                            <span>Appareil:</span>
                            <span class="font-medium text-white">{{ $repair['appareil'] }}</span>
                        </div>
                        <div class="flex justify-between text-blue-100">
                            <span>Statut:</span>
                            <span class="font-semibold px-2 py-0.5 rounded-full text-xs
                                {{ $repair['statut'] === 'Terminé' ? 'bg-green-500/30 text-green-300' :
                                   ($repair['statut'] === 'En cours' || $repair['statut'] === 'En attente' ? 'bg-yellow-500/30 text-yellow-300' :
                                   'bg-red-500/30 text-red-300') }}">
                                {{ $repair['statut'] }}
                            </span>
                        </div>
                        <div class="flex justify-between text-blue-100">
                            <span>Date création:</span>
                            <span class="text-white">{{ $repair['date_creation'] ?? 'N/A' }}</span>
                        </div>
                        @if($repair['date_retrait'])
                        <div class="flex justify-between text-blue-100">
                            <span>Date retrait:</span>
                            <span class="text-green-300">{{ $repair['date_retrait'] }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('home') }}" class="text-blue-300 hover:text-white text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="bg-black/30 border-t border-white/10 py-4">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-blue-300">
            <p>&copy; {{ date('Y') }} MOMO TECH SERVICE</p>
        </div>
    </footer>
</div>
@endsection
