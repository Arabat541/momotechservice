@extends('layouts.dashboard')
@section('page-title', 'Transfert')

@section('content')
@php
    $isSource      = $transfer->shop_from_id === $shopId;
    $isDest        = $transfer->shop_to_id === $shopId;
    $isPatron      = $user->role === 'patron';
    $canValidSend  = ($isSource || $isPatron) && $transfer->statut === 'en_attente_envoi';
    $canValidRecv  = ($isDest   || $isPatron) && $transfer->statut === 'en_attente_reception';
    $canCancel     = $isPatron  && !in_array($transfer->statut, ['completee', 'annulee']);

    $badgeClass = match($transfer->statut) {
        'en_attente_envoi'     => 'bg-yellow-100 text-yellow-700',
        'en_attente_reception' => 'bg-blue-100 text-blue-700',
        'completee'            => 'bg-green-100 text-green-700',
        'annulee'              => 'bg-red-100 text-red-600',
        default                => 'bg-gray-100 text-gray-600',
    };
    $badgeLabel = match($transfer->statut) {
        'en_attente_envoi'     => 'En attente d\'envoi',
        'en_attente_reception' => 'En attente de réception',
        'completee'            => 'Complété',
        'annulee'              => 'Annulé',
        default                => $transfer->statut,
    };
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('transfers.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ $transfer->numero }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">
                    Créé le {{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y à H:i') }}
                    par {{ optional($transfer->createdBy)->prenom }} {{ optional($transfer->createdBy)->nom }}
                </p>
            </div>
        </div>
        <span class="px-3 py-1.5 text-sm font-semibold rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
    </div>

    {{-- Progression visuelle --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            {{-- Étape 1 : Création --}}
            <div class="flex flex-col items-center gap-1 flex-1">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold bg-green-500 text-white">
                    <i class="fas fa-check text-xs"></i>
                </div>
                <span class="text-xs text-gray-600 font-medium text-center">Créé</span>
                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m') }}</span>
            </div>

            <div class="flex-1 h-0.5 {{ !in_array($transfer->statut, ['en_attente_envoi']) ? 'bg-green-400' : 'bg-gray-200' }}"></div>

            {{-- Étape 2 : Validation envoi --}}
            <div class="flex flex-col items-center gap-1 flex-1">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ in_array($transfer->statut, ['en_attente_reception','completee']) ? 'bg-green-500 text-white' : ($transfer->statut === 'en_attente_envoi' ? 'bg-yellow-400 text-white' : 'bg-gray-200 text-gray-500') }}">
                    @if(in_array($transfer->statut, ['en_attente_reception','completee']))
                        <i class="fas fa-check text-xs"></i>
                    @else
                        2
                    @endif
                </div>
                <span class="text-xs text-gray-600 font-medium text-center">Envoi validé</span>
                @if($transfer->validated_sender_at)
                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($transfer->validated_sender_at)->format('d/m') }}</span>
                @endif
            </div>

            <div class="flex-1 h-0.5 {{ $transfer->statut === 'completee' ? 'bg-green-400' : 'bg-gray-200' }}"></div>

            {{-- Étape 3 : Validation réception --}}
            <div class="flex flex-col items-center gap-1 flex-1">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $transfer->statut === 'completee' ? 'bg-green-500 text-white' : ($transfer->statut === 'en_attente_reception' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                    @if($transfer->statut === 'completee')
                        <i class="fas fa-check text-xs"></i>
                    @else
                        3
                    @endif
                </div>
                <span class="text-xs text-gray-600 font-medium text-center">Reçu</span>
                @if($transfer->validated_receiver_at)
                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($transfer->validated_receiver_at)->format('d/m') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Boutiques --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border {{ $isSource ? 'border-yellow-300' : 'border-gray-100' }} p-5">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-store text-gray-400 text-sm"></i>
                <span class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Boutique source</span>
                @if($isSource) <span class="text-xs text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded-full">Votre boutique</span> @endif
            </div>
            <div class="font-semibold text-gray-900">{{ optional($transfer->shopFrom)->nom }}</div>
            @if($transfer->validatedBySender)
            <div class="text-xs text-green-600 mt-2 flex items-center gap-1">
                <i class="fas fa-check-circle"></i>
                Validé par {{ $transfer->validatedBySender->prenom }} {{ $transfer->validatedBySender->nom }}
                le {{ \Carbon\Carbon::parse($transfer->validated_sender_at)->format('d/m/Y à H:i') }}
            </div>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm border {{ $isDest ? 'border-blue-300' : 'border-gray-100' }} p-5">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas fa-store text-gray-400 text-sm"></i>
                <span class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Boutique destinataire</span>
                @if($isDest) <span class="text-xs text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">Votre boutique</span> @endif
            </div>
            <div class="font-semibold text-gray-900">{{ optional($transfer->shopTo)->nom }}</div>
            @if($transfer->validatedByReceiver)
            <div class="text-xs text-green-600 mt-2 flex items-center gap-1">
                <i class="fas fa-check-circle"></i>
                Validé par {{ $transfer->validatedByReceiver->prenom }} {{ $transfer->validatedByReceiver->nom }}
                le {{ \Carbon\Carbon::parse($transfer->validated_receiver_at)->format('d/m/Y à H:i') }}
            </div>
            @endif
        </div>
    </div>

    @if($transfer->notes)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-sm text-gray-700">
        <i class="fas fa-sticky-note text-gray-400 mr-2"></i>{{ $transfer->notes }}
    </div>
    @endif

    {{-- Articles --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Articles transférés ({{ $transfer->lines->count() }})</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Quantité</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Stock actuel source</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($transfer->lines as $line)
                <tr>
                    <td class="px-6 py-3 font-medium text-gray-900 text-sm">{{ optional($line->stock)->nom ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500">{{ optional($line->stock)->categorie ?? '—' }}</td>
                    <td class="px-6 py-3 text-right text-sm font-semibold">{{ $line->quantite }}</td>
                    <td class="px-6 py-3 text-right text-sm {{ optional($line->stock)->quantite === 0 ? 'text-red-600' : 'text-gray-700' }}">
                        {{ optional($line->stock)->quantite ?? '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Actions --}}
    @if($canValidSend || $canValidRecv || $canCancel)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
        <h2 class="font-semibold text-gray-800 text-sm uppercase tracking-wide">Actions</h2>

        @if($canValidSend)
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800 mb-3">
                <i class="fas fa-info-circle mr-1"></i>
                En tant que {{ $isPatron ? 'patron' : 'caissière de la boutique source' }}, vous devez confirmer
                que les articles ont bien quitté <strong>{{ optional($transfer->shopFrom)->nom }}</strong>.
                Le stock sera immédiatement débité.
            </p>
            <form method="POST" action="{{ route('transfers.valider-envoi', $transfer->id) }}">
                @csrf
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg text-sm font-medium"
                    onclick="return confirm('Confirmer l\'envoi ? Le stock de la boutique source sera débité.')">
                    <i class="fas fa-paper-plane mr-2"></i> Valider l'envoi
                </button>
            </form>
        </div>
        @endif

        @if($canValidRecv)
        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800 mb-3">
                <i class="fas fa-info-circle mr-1"></i>
                En tant que {{ $isPatron ? 'patron' : 'caissière de la boutique destinataire' }}, vous devez confirmer
                la bonne réception des articles dans <strong>{{ optional($transfer->shopTo)->nom }}</strong>.
                Le stock sera crédité et les articles seront disponibles.
            </p>
            <form method="POST" action="{{ route('transfers.valider-reception', $transfer->id) }}">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium"
                    onclick="return confirm('Confirmer la réception ? Le stock de la boutique destinataire sera crédité.')">
                    <i class="fas fa-box-open mr-2"></i> Valider la réception
                </button>
            </form>
        </div>
        @endif

        @if($canCancel)
        <div class="pt-1">
            <form method="POST" action="{{ route('transfers.annuler', $transfer->id) }}">
                @csrf
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center gap-2"
                    onclick="return confirm('Annuler ce transfert ?{{ $transfer->statut === 'en_attente_reception' ? ' Le stock de la boutique source sera restauré.' : '' }}')">
                    <i class="fas fa-ban"></i>
                    Annuler le transfert
                    @if($transfer->statut === 'en_attente_reception')
                    <span class="text-xs text-gray-500">(le stock source sera restauré)</span>
                    @endif
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
