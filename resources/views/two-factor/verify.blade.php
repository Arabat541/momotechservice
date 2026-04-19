@extends('layouts.base')
@section('title', 'Vérification 2FA')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 w-full max-w-sm space-y-6">
        <div class="text-center">
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Vérification 2FA</h1>
            <p class="text-sm text-gray-500 mt-1">Entrez le code de votre application d'authentification.</p>
        </div>

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('two-factor.verify.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code à 6 chiffres</label>
                <input type="text" name="otp" inputmode="numeric" maxlength="6" required
                    placeholder="000000" autofocus
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-center text-2xl font-mono tracking-widest focus:ring-2 focus:ring-blue-500">
                @error('otp')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold text-sm">
                <i class="fas fa-unlock-alt mr-2"></i>Vérifier
            </button>
        </form>

        <div class="text-center">
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form-2fa').submit();"
               class="text-sm text-gray-400 hover:text-gray-600">
                <i class="fas fa-arrow-left mr-1"></i>Retour à la connexion
            </a>
            <form id="logout-form-2fa" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
        </div>
    </div>
</div>
@endsection
