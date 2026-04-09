@extends('layouts.base')

@section('title', 'Réinitialiser le mot de passe')

@section('body')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 p-4">
    <div class="w-full max-w-md glass-effect shadow-2xl rounded-xl p-8" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
        <h2 class="text-xl font-semibold text-white mb-6">Réinitialiser le mot de passe</h2>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-500/30 border border-green-400 rounded-lg text-green-200 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-500/30 border border-red-400 rounded-lg text-red-200 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Step 1: Request code --}}
        <form action="{{ route('password.reset.send') }}" method="POST" class="space-y-4 mb-6">
            @csrf
            <div>
                <label class="block text-sm text-purple-300 mb-1">Email</label>
                <input type="email" name="email" required placeholder="exemple@mail.com"
                       class="w-full px-4 py-3 bg-white/20 border border-purple-500 text-white placeholder-purple-400 rounded-lg focus:ring-2 focus:ring-purple-400">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 rounded-lg font-semibold">
                Envoyer le code
            </button>
        </form>

        <hr class="border-purple-500/30 my-4">

        {{-- Step 2: Confirm code --}}
        <form action="{{ route('password.reset.confirm') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm text-purple-300 mb-1">Email</label>
                <input type="email" name="email" required placeholder="exemple@mail.com"
                       class="w-full px-4 py-3 bg-white/20 border border-purple-500 text-white placeholder-purple-400 rounded-lg focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label class="block text-sm text-purple-300 mb-1">Code reçu</label>
                <input type="text" name="code" required maxlength="6" placeholder="123456"
                       class="w-full px-4 py-3 bg-white/20 border border-purple-500 text-white placeholder-purple-400 rounded-lg focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label class="block text-sm text-purple-300 mb-1">Nouveau mot de passe</label>
                <input type="password" name="password" required minlength="8" placeholder="Minimum 8 caractères"
                       class="w-full px-4 py-3 bg-white/20 border border-purple-500 text-white placeholder-purple-400 rounded-lg focus:ring-2 focus:ring-purple-400">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white py-3 rounded-lg font-semibold">
                Réinitialiser
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-purple-300 hover:text-purple-100">
                <i class="fas fa-arrow-left mr-1"></i> Retour à la connexion
            </a>
        </div>
    </div>
</div>
@endsection
