@extends('layouts.dashboard')
@section('page-title', 'Notifications')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
        @if($notifCount > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-check-double"></i> Tout marquer lu ({{ $notifCount }})
            </button>
        </form>
        @endif
    </div>

    {{-- Filtres par type --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('notifications.index') }}"
               class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors
                      {{ !$type ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                <i class="fas fa-list mr-1"></i> Toutes
            </a>
            <a href="{{ route('notifications.index', ['type' => 'stock_alerte']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors
                      {{ $type === 'stock_alerte' ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-orange-600 border-orange-300 hover:bg-orange-50' }}">
                <i class="fas fa-triangle-exclamation mr-1"></i> Stock critique
            </a>
            <a href="{{ route('notifications.index', ['type' => 'reparation_prete']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors
                      {{ $type === 'reparation_prete' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-green-600 border-green-300 hover:bg-green-50' }}">
                <i class="fas fa-check-circle mr-1"></i> Réparations prêtes
            </a>
            <a href="{{ route('notifications.index', ['type' => 'credit_depasse']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors
                      {{ $type === 'credit_depasse' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-red-600 border-red-300 hover:bg-red-50' }}">
                <i class="fas fa-credit-card mr-1"></i> Crédit dépassé
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Liste --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @forelse($notifications as $notif)
        @php
            $borderColor = match($notif->type) {
                'stock_alerte'    => 'border-orange-400',
                'reparation_prete'=> 'border-green-400',
                'credit_depasse'  => 'border-red-400',
                default           => 'border-gray-300',
            };
            $iconClass = match($notif->type) {
                'stock_alerte'    => 'fa-triangle-exclamation text-orange-500 bg-orange-50',
                'reparation_prete'=> 'fa-check-circle text-green-600 bg-green-50',
                'credit_depasse'  => 'fa-credit-card text-red-600 bg-red-50',
                default           => 'fa-bell text-gray-400 bg-gray-50',
            };
            $typLabel = match($notif->type) {
                'stock_alerte'    => 'Stock critique',
                'reparation_prete'=> 'Réparation prête',
                'credit_depasse'  => 'Crédit dépassé',
                default           => $notif->type,
            };
            $isUnread = is_null($notif->lu_at);
        @endphp
        <div class="flex items-start gap-4 px-6 py-4 border-b border-gray-100 border-l-4 {{ $borderColor }} {{ $isUnread ? 'bg-blue-50/40' : 'bg-white' }} hover:bg-gray-50 transition-colors">
            {{-- Icône --}}
            <div class="flex-shrink-0 w-10 h-10 rounded-full {{ explode(' ', $iconClass)[2] }} flex items-center justify-center">
                <i class="fas {{ explode(' ', $iconClass)[0] }} {{ explode(' ', $iconClass)[1] }}"></i>
            </div>

            {{-- Contenu --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2 flex-wrap">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wide
                            {{ $notif->type === 'stock_alerte' ? 'text-orange-600' : ($notif->type === 'reparation_prete' ? 'text-green-600' : 'text-red-600') }}">
                            {{ $typLabel }}
                        </span>
                        @if($isUnread)
                        <span class="ml-2 inline-flex w-2 h-2 rounded-full bg-blue-500"></span>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0">
                        {{ \Carbon\Carbon::parse($notif->created_at)->format('d/m/Y H:i') }}
                        — {{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}
                    </span>
                </div>
                <p class="mt-1 text-sm font-semibold text-gray-800">{{ $notif->titre }}</p>
                <p class="mt-0.5 text-sm text-gray-500">{{ $notif->message }}</p>
                @if($notif->shop)
                <p class="mt-1 text-xs text-gray-400"><i class="fas fa-store mr-1"></i>{{ $notif->shop->nom }}</p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                @if($isUnread)
                <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            title="Marquer comme lu"
                            class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 border border-blue-200 hover:bg-blue-50 px-2.5 py-1.5 rounded-lg transition-colors font-medium">
                        <i class="fas fa-check"></i> Lu
                    </button>
                </form>
                @else
                <span class="text-xs text-gray-400 px-2.5 py-1.5">
                    <i class="fas fa-check-double mr-1"></i>Lu
                    {{ $notif->lu_at ? \Carbon\Carbon::parse($notif->lu_at)->format('d/m H:i') : '' }}
                </span>
                @endif

                @if(session('user_role') === 'patron')
                <form action="{{ route('notifications.destroy', $notif->id) }}" method="POST"
                      onsubmit="return confirm('Supprimer cette notification ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            title="Supprimer"
                            class="flex items-center justify-center w-8 h-8 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="px-6 py-16 text-center text-gray-400">
            <i class="fas fa-bell-slash text-4xl mb-3 block"></i>
            <p class="text-base font-medium">Aucune notification{{ $type ? ' de ce type' : '' }}.</p>
            <p class="text-sm mt-1">Les alertes apparaîtront ici automatiquement.</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if(method_exists($notifications, 'hasPages') && $notifications->hasPages())
    <div class="py-2">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection
