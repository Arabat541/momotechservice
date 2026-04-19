<div class="group bg-white rounded-2xl border border-gray-100 hover:shadow-lg hover:border-blue-100 transition-all overflow-hidden flex flex-col">

    {{-- Badge stock faible --}}
    <div class="relative">
        <div class="h-44 bg-gray-50 flex items-center justify-center">
            @php
                $catKey = mb_strtolower($produit['categorie'] ?? '');
                $icone = match(true) {
                    str_contains($catKey, 'tel') || str_contains($catKey, 'phon') => 'fa-mobile-screen-button',
                    str_contains($catKey, 'access')                               => 'fa-headphones',
                    str_contains($catKey, 'écran') || str_contains($catKey, 'ecran') => 'fa-display',
                    str_contains($catKey, 'batt')                                 => 'fa-battery-full',
                    default                                                        => 'fa-screwdriver-wrench',
                };
            @endphp
            <i class="fas {{ $icone }} text-5xl text-gray-300 group-hover:text-blue-200 transition-colors"></i>
        </div>
        @if($produit['stock_total'] <= 3)
        <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-semibold px-2 py-0.5 rounded-full flex items-center gap-1">
            <i class="fas fa-circle-info text-xs"></i> Plus que {{ $produit['stock_total'] }}
        </span>
        @endif
    </div>

    {{-- Infos --}}
    <div class="p-4 flex flex-col flex-1">
        {{-- Catégorie badge --}}
        @if($produit['categorie'])
        <span class="inline-block self-start text-xs font-bold uppercase tracking-wide px-2 py-0.5 rounded-full mb-2
            {{ match(mb_strtolower($produit['categorie'])) {
                'téléphone','telephone' => 'bg-purple-100 text-purple-700',
                'accessoire','accessoires' => 'bg-green-100 text-green-700',
                'écran','ecran' => 'bg-blue-100 text-blue-700',
                'batterie' => 'bg-yellow-100 text-yellow-700',
                default => 'bg-gray-100 text-gray-600'
            } }}">
            {{ $produit['categorie'] }}
        </span>
        @endif

        {{-- Nom --}}
        <h4 class="font-semibold text-gray-900 text-sm leading-snug mb-1">{{ $produit['nom'] }}</h4>

        {{-- Boutiques disponibles --}}
        <div class="space-y-0.5 mb-3">
            @foreach($produit['boutiques'] as $dispo)
            <p class="text-xs text-gray-400 flex items-center gap-1 truncate">
                <i class="fas fa-map-marker-alt text-blue-400 shrink-0 text-xs"></i>
                {{ $dispo['shop']['nom'] }}
            </p>
            @endforeach
        </div>

        {{-- Prix + bouton --}}
        <div class="mt-auto flex items-center justify-between gap-2">
            <div>
                @if($produit['prix_min'] === $produit['prix_max'])
                <span class="text-xl font-extrabold text-gray-900">{{ number_format($produit['prix_min'], 0, ',', ' ') }}</span>
                <span class="text-xs text-gray-500 font-medium"> FCFA</span>
                @else
                <span class="text-lg font-extrabold text-gray-900">{{ number_format($produit['prix_min'], 0, ',', ' ') }}</span>
                <span class="text-xs text-gray-400"> – {{ number_format($produit['prix_max'], 0, ',', ' ') }} FCFA</span>
                @endif
            </div>
            @if($produit['boutiques']->count() === 1 && !empty($produit['boutiques'][0]['shop']['telephone']))
            <a href="tel:{{ $produit['boutiques'][0]['shop']['telephone'] }}"
                class="w-9 h-9 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center justify-center shrink-0 transition-colors"
                title="Appeler pour commander">
                <i class="fas fa-phone text-sm"></i>
            </a>
            @else
            <div class="w-9 h-9 bg-blue-100 text-blue-400 rounded-lg flex items-center justify-center shrink-0"
                title="{{ $produit['boutiques']->count() }} boutique(s)">
                <i class="fas fa-store text-sm"></i>
            </div>
            @endif
        </div>
    </div>
</div>
