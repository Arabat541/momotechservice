@extends('layouts.base')

@section('title', 'Connexion - MOMO TECH SERVICE')

@section('body')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 p-4">
    <div class="w-full max-w-md glass-effect shadow-2xl rounded-xl p-8" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
        <div class="text-center mb-8">
            <img src="/images/logo-app.png" alt="MTS" class="w-20 h-20 mx-auto mb-4 rounded-full shadow-lg">
            <h1 class="text-3xl font-bold text-white">MOMO TECH SERVICE</h1>
            <p class="text-purple-300 mt-1">Connectez-vous à votre compte</p>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-500/30 border border-green-400 rounded-lg text-green-200 text-center text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-500/30 border border-red-400 rounded-lg text-red-200 text-center text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-purple-300 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       placeholder="exemple@mail.com"
                       class="w-full px-4 py-3 bg-white/20 border border-purple-500 text-white placeholder-purple-400 rounded-lg focus:ring-2 focus:ring-purple-400 focus:border-purple-400">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-purple-300 mb-1">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       placeholder="********"
                       class="w-full px-4 py-3 bg-white/20 border border-purple-500 text-white placeholder-purple-400 rounded-lg focus:ring-2 focus:ring-purple-400 focus:border-purple-400">
            </div>
            <button type="submit"
                    class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 rounded-lg text-lg font-semibold transition-all flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('password.reset') }}" class="text-sm text-purple-300 hover:text-purple-100">
                Mot de passe oublié ?
            </a>
        </div>
    </div>
</div>
@endsection
