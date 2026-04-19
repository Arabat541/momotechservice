@extends('layouts.dashboard')
@section('page-title', 'Double authentification')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Double authentification (2FA)</h1>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    @if($user->two_factor_enabled)
    {{-- 2FA activé --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                <i class="fas fa-shield-alt text-green-600"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-900">2FA activé</p>
                <p class="text-sm text-gray-500">Votre compte est protégé par une double authentification.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('two-factor.disable') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Code de votre application d'authentification <span class="text-red-500">*</span>
                </label>
                <input type="text" name="otp" inputmode="numeric" maxlength="6" required
                    placeholder="000000"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono tracking-widest w-36 focus:ring-2 focus:ring-red-500">
            </div>
            <button type="submit"
                onclick="return confirm('Désactiver la double authentification ?')"
                class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-lock-open mr-2"></i>Désactiver le 2FA
            </button>
        </form>
    </div>
    @else
    {{-- 2FA désactivé --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                <i class="fas fa-shield-alt text-gray-400"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-900">2FA désactivé</p>
                <p class="text-sm text-gray-500">Activez le 2FA pour sécuriser davantage votre compte patron.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('two-factor.activate') }}">
            @csrf
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-shield-alt mr-2"></i>Activer le 2FA
            </button>
        </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        <i class="fas fa-info-circle mr-2"></i>
        Vous aurez besoin d'une application comme <strong>Google Authenticator</strong> ou <strong>Authy</strong>
        pour scanner le QR code.
    </div>
    @endif
</div>
@endsection
