@extends('layouts.dashboard')
@section('page-title', 'Activer le 2FA')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Activer la double authentification</h1>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div>
            <h2 class="font-semibold text-gray-800 mb-1">Étape 1 — Scanner le QR code</h2>
            <p class="text-sm text-gray-500">Ouvrez Google Authenticator ou Authy et scannez ce QR code.</p>
        </div>

        <div class="flex justify-center py-2">
            {!! $qrSvg !!}
        </div>

        <div>
            <p class="text-xs text-gray-500 mb-1">Ou entrez manuellement cette clé :</p>
            <code class="text-sm bg-gray-100 px-3 py-1.5 rounded font-mono tracking-widest">{{ $secret }}</code>
        </div>

        <div class="border-t border-gray-100 pt-4">
            <h2 class="font-semibold text-gray-800 mb-3">Étape 2 — Confirmer avec votre code</h2>
            <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex items-end gap-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Code à 6 chiffres <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="otp" inputmode="numeric" maxlength="6" required
                        placeholder="000000" autofocus
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono tracking-widest w-36 focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-check mr-2"></i>Confirmer et activer
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
