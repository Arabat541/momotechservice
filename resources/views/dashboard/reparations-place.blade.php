@extends('layouts.dashboard')

@section('page-title', 'Nouvelle Réparation sur Place')

@section('content')
<div class="bg-gray-50 rounded-xl shadow-2xl w-full p-2 sm:p-4">
    <div class="p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800">Nouvelle Réparation sur Place</h1>
    </div>

    <div class="flex flex-col md:flex-row">
        {{-- Form --}}
        <form action="{{ route('reparations.store') }}" method="POST" class="w-full md:w-3/5 p-4 space-y-3 md:border-r border-gray-200" id="repairForm">
            @csrf
            <input type="hidden" name="type_reparation" value="place">

            <div>
                <label class="text-xs font-medium text-gray-600">N° Réparation</label>
                <input type="text" name="numeroReparation" value="{{ $numero }}" readonly
                       class="w-full text-sm py-1.5 border-gray-300 rounded-md bg-gray-100 px-3">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600">Client</label>
                    <input type="text" name="client_nom" required class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">Téléphone</label>
                    <input type="text" name="client_telephone" required class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600">Appareil (Marque et Modèle)</label>
                <select name="appareil_select" id="appareilSelect" onchange="handleAppareilChange(this)"
                        class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border focus:ring-blue-500">
                    <option value="">Sélectionner un modèle</option>
                    @foreach(['iPhone 16e','iPhone 16','iPhone 16 Plus','iPhone 16 Pro','iPhone 16 Pro Max','iPhone 15','iPhone 15 Plus','iPhone 15 Pro','iPhone 15 Pro Max','iPhone 14','iPhone 14 Plus','iPhone 14 Pro','iPhone 14 ProMax','iPhone 13','iPhone 13 Mini','iPhone 13 Pro','iPhone 13 Pro Max','iPhone 12 classique','iPhone 12 Mini','iPhone 12 Pro','iPhone 12 Pro Max','iPhone 11 classique','iPhone 11 Pro','iPhone 11 Pro Max','iPhone X classique','iPhone XR','iPhone XS','iPhone XS Max','iPhone SE','iPhone SE 2020','iPhone SE 2022','iPhone 8','iPhone 8 Plus','iPhone 7','iPhone 7 Plus','iPhone 6'] as $model)
                        <option value="{{ $model }}">{{ $model }}</option>
                    @endforeach
                    <option value="autre">Autre...</option>
                </select>
                <input type="hidden" name="appareil_marque_modele" id="appareilValue">
                <input type="text" id="appareilAutre" placeholder="Saisir un autre modèle"
                       class="w-full text-sm py-1.5 border-gray-300 rounded-md mt-2 px-3 border hidden">
            </div>

            {{-- Pannes / Services --}}
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Pannes et Montants (Services)</label>
                <div id="pannesContainer">
                    <div class="flex items-center space-x-2 mb-1.5 panne-row">
                        <input type="text" name="panne_description[]" placeholder="Service 1" class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border">
                        <input type="number" name="panne_montant[]" placeholder="Montant" step="any" class="w-1/3 text-sm py-1.5 border-gray-300 rounded-md px-3 border no-spinner" oninput="calculateTotal()">
                        <button type="button" onclick="this.closest('.panne-row').remove(); calculateTotal()" class="text-red-500 hover:text-red-700 px-2 py-1 hidden remove-btn"><i class="fas fa-trash text-xs"></i></button>
                    </div>
                </div>
                <button type="button" onclick="addPanne()" class="text-xs py-1 px-3 border rounded-md text-blue-600 hover:bg-blue-50 mt-1">
                    <i class="fas fa-plus-circle mr-1"></i> Ajouter Service
                </button>
            </div>

            {{-- Pièces de rechange --}}
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Pièces de rechange utilisées</label>
                <div id="piecesContainer"></div>
                @if($stocks->count() > 0)
                    <button type="button" onclick="addPiece()" class="text-xs py-1 px-3 border rounded-md text-blue-600 hover:bg-blue-50 mt-1">
                        <i class="fas fa-search mr-1"></i> Ajouter Pièce
                    </button>
                @else
                    <p class="text-xs text-gray-500">Aucune pièce en stock.</p>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600">Total</label>
                    <input type="number" name="total_display" id="totalDisplay" readonly
                           class="w-full text-sm py-1.5 border-gray-300 rounded-md bg-gray-100 font-semibold px-3 border">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">Payé</label>
                    <input type="number" name="montant_paye" id="montantPaye" step="any" required
                           class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border no-spinner" oninput="calculateTotal()">
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600">Statut</label>
                <select name="statut_reparation" class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border">
                    <option value="En cours">En cours</option>
                    <option value="Terminé">Terminé</option>
                    <option value="Annulé">Annulé</option>
                </select>
            </div>

            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-3 border-t border-gray-200">
                <button type="reset" class="px-4 py-2 text-xs bg-red-100 text-red-700 hover:bg-red-200 border border-red-200 rounded-md">Effacer</button>
                <button type="submit" class="px-4 py-2 text-xs bg-green-600 hover:bg-green-700 text-white rounded-md font-semibold">Enregistrer</button>
            </div>
        </form>

        {{-- Receipt Preview (right side) --}}
        <div class="w-full md:w-2/5 p-2 bg-white flex flex-col items-center justify-start">
            <div class="bg-white text-gray-800 text-sm w-[302px] p-3 shadow-lg border border-gray-300 mt-4" id="receiptPreview">
                <div class="text-center mb-2">
                    <h2 class="text-lg font-bold uppercase">{{ $settings->companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</h2>
                    <p class="text-xs">{{ $settings->companyInfo['adresse'] ?? '' }}</p>
                    <p class="text-xs">Tél: {{ $settings->companyInfo['telephone'] ?? '' }}</p>
                    <p class="text-xs italic">{{ $settings->companyInfo['slogan'] ?? '' }}</p>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between"><span class="font-semibold">N° Réparation:</span><span>{{ $numero }}</span></div>
                    <div class="flex justify-between"><span class="font-semibold">Date création:</span><span>{{ now()->format('d/m/Y H:i') }}</span></div>
                </div>
                <div class="mb-2 border-t border-b border-dashed border-gray-400 py-1">
                    <p><span class="font-semibold">Client:</span> <span id="prevClient">---</span></p>
                    <p><span class="font-semibold">Téléphone:</span> <span id="prevTel">---</span></p>
                    <p><span class="font-semibold">Appareil:</span> <span id="prevAppareil">---</span></p>
                </div>
                <div class="mb-2" id="prevPannesSection">
                    <p class="font-semibold underline mb-0.5">Pannes / Services:</p>
                    <div id="prevPannesList"></div>
                </div>
                <div class="mb-2 hidden" id="prevPiecesSection">
                    <p class="font-semibold underline mb-0.5">Pièces de rechange:</p>
                    <div id="prevPiecesList"></div>
                </div>
                <div class="border-t border-gray-400 pt-1 mb-2">
                    <div class="flex justify-between font-bold text-base"><span>TOTAL:</span><span id="prevTotal">0 cfa</span></div>
                    <div class="flex justify-between text-xs"><span>Payé:</span><span id="prevPaye">0 cfa</span></div>
                    <div class="flex justify-between text-xs font-semibold"><span>Reste à payer:</span><span id="prevReste">0 cfa</span></div>
                </div>
                <div class="text-center mb-2 py-2">
                    <svg id="barcodePreview"></svg>
                </div>
                <div class="text-xs text-center border-t border-dashed border-gray-400 pt-1">
                    <p class="font-semibold">Merci pour votre confiance!</p>
                    @if(!empty($settings->warranty['conditions']))
                    <p class="mt-0.5">{{ $settings->warranty['conditions'] }}
                    @if(!empty($settings->warranty['duree']))
                    (Durée: {{ $settings->warranty['duree'] }} jours)
                    @endif
                    </p>
                    @endif
                </div>
                <div class="text-xs text-center font-semibold mt-1 border-t border-dashed border-gray-400 pt-1">
                    <span>Statut: <span id="prevStatut">En cours</span></span> |
                    <span>Paiement: <span id="prevPaiement">Soldé</span></span>
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <button type="button" onclick="printTicket()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded-md font-semibold"><i class="fas fa-print mr-1"></i> Imprimer ticket</button>
                <button type="button" onclick="printBarcode()" class="px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white text-xs rounded-md font-semibold"><i class="fas fa-barcode mr-1"></i> Imprimer code barre</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const stocksData = @json($stocks);
    let panneCount = 1;

    function handleAppareilChange(select) {
        const autreInput = document.getElementById('appareilAutre');
        const hiddenInput = document.getElementById('appareilValue');
        if (select.value === 'autre') {
            autreInput.classList.remove('hidden');
            autreInput.oninput = () => { hiddenInput.value = autreInput.value; };
            hiddenInput.value = autreInput.value;
        } else {
            autreInput.classList.add('hidden');
            hiddenInput.value = select.value;
        }
    }

    function addPanne() {
        if (panneCount >= 4) { alert('Maximum 4 services'); return; }
        panneCount++;
        const container = document.getElementById('pannesContainer');
        const row = document.createElement('div');
        row.className = 'flex items-center space-x-2 mb-1.5 panne-row';
        row.innerHTML = `
            <input type="text" name="panne_description[]" placeholder="Service ${panneCount}" class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border">
            <input type="number" name="panne_montant[]" placeholder="Montant" step="any" class="w-1/3 text-sm py-1.5 border-gray-300 rounded-md px-3 border no-spinner" oninput="calculateTotal()">
            <button type="button" onclick="this.closest('.panne-row').remove(); panneCount--; calculateTotal()" class="text-red-500 hover:text-red-700 px-2 py-1"><i class="fas fa-trash text-xs"></i></button>
        `;
        container.appendChild(row);
    }

    function addPiece() {
        const container = document.getElementById('piecesContainer');
        const rows = container.querySelectorAll('.piece-row');
        if (rows.length >= 5) { alert('Maximum 5 pièces'); return; }
        const row = document.createElement('div');
        row.className = 'flex items-center space-x-2 mb-1.5 piece-row';
        let options = '<option value="">Choisir une pièce...</option>';
        stocksData.forEach(s => {
            options += `<option value="${s.id}" data-prix="${s.prixVente}">${s.nom} (Stock: ${s.quantite}, P.V: ${s.prixVente} cfa)</option>`;
        });
        row.innerHTML = `
            <select name="piece_stock_id[]" class="w-2/3 text-sm py-1.5 border-gray-300 rounded-md px-3 border" onchange="calculateTotal()">${options}</select>
            <input type="number" name="piece_quantite[]" value="1" min="1" class="w-1/4 text-sm py-1.5 border-gray-300 rounded-md px-3 border" oninput="calculateTotal()">
            <button type="button" onclick="this.closest('.piece-row').remove(); calculateTotal()" class="text-red-500 hover:text-red-700 px-2 py-1"><i class="fas fa-trash text-xs"></i></button>
        `;
        container.appendChild(row);
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('input[name="panne_montant[]"]').forEach(el => {
            total += parseFloat(el.value) || 0;
        });
        document.querySelectorAll('.piece-row').forEach(row => {
            const select = row.querySelector('select');
            const qte = parseInt(row.querySelector('input[type="number"]').value) || 0;
            const option = select.options[select.selectedIndex];
            const prix = parseFloat(option?.dataset?.prix) || 0;
            total += prix * qte;
        });
        document.getElementById('totalDisplay').value = total;
        updatePreview();
    }

    function formatNumber(n) {
        return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function updatePreview() {
        // Client info
        document.getElementById('prevClient').textContent = document.querySelector('input[name="client_nom"]').value || '---';
        document.getElementById('prevTel').textContent = document.querySelector('input[name="client_telephone"]').value || '---';
        document.getElementById('prevAppareil').textContent = document.getElementById('appareilValue').value || '---';

        // Pannes
        const pannesList = document.getElementById('prevPannesList');
        pannesList.innerHTML = '';
        document.querySelectorAll('.panne-row').forEach(row => {
            const desc = row.querySelector('input[name="panne_description[]"]').value;
            const montant = parseFloat(row.querySelector('input[name="panne_montant[]"]').value) || 0;
            if (desc || montant > 0) {
                pannesList.innerHTML += `<div class="flex justify-between text-xs"><span>- ${desc || '...'}</span><span>${formatNumber(montant)} cfa</span></div>`;
            }
        });

        // Pièces
        const piecesList = document.getElementById('prevPiecesList');
        const piecesSection = document.getElementById('prevPiecesSection');
        piecesList.innerHTML = '';
        const pieceRows = document.querySelectorAll('.piece-row');
        if (pieceRows.length > 0) {
            piecesSection.classList.remove('hidden');
            pieceRows.forEach(row => {
                const sel = row.querySelector('select');
                const nom = sel.options[sel.selectedIndex]?.text || '';
                const qty = parseInt(row.querySelector('input[type="number"]').value) || 0;
                if (sel.value) {
                    piecesList.innerHTML += `<div class="flex justify-between text-xs"><span>- ${nom.split('(')[0].trim()} (x${qty})</span></div>`;
                }
            });
        } else {
            piecesSection.classList.add('hidden');
        }

        // Totals
        const total = parseFloat(document.getElementById('totalDisplay').value) || 0;
        const paye = parseFloat(document.getElementById('montantPaye').value) || 0;
        const reste = total - paye;
        document.getElementById('prevTotal').textContent = formatNumber(total) + ' cfa';
        document.getElementById('prevPaye').textContent = formatNumber(paye) + ' cfa';
        document.getElementById('prevReste').textContent = formatNumber(reste) + ' cfa';

        // Statut & Paiement
        const statutSelect = document.querySelector('select[name="statut_reparation"]');
        if (statutSelect) {
            document.getElementById('prevStatut').textContent = statutSelect.value;
        }
        const etatPaiement = (paye >= total && total > 0) ? 'Soldé' : 'Non soldé';
        document.getElementById('prevPaiement').textContent = etatPaiement;
    }

    // Attach live listeners
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="client_nom"], input[name="client_telephone"], input[name="montant_paye"]').forEach(el => {
            el.addEventListener('input', updatePreview);
        });
        document.getElementById('appareilSelect').addEventListener('change', function() {
            setTimeout(updatePreview, 50);
        });
        const appareilAutre = document.getElementById('appareilAutre');
        appareilAutre.addEventListener('input', updatePreview);

        // Observe pannes/pieces container changes
        const observer = new MutationObserver(() => { setTimeout(updatePreview, 50); });
        observer.observe(document.getElementById('pannesContainer'), { childList: true, subtree: true });
        observer.observe(document.getElementById('piecesContainer'), { childList: true, subtree: true });

        // Listen for input on dynamically added fields
        document.getElementById('repairForm').addEventListener('input', updatePreview);
        document.getElementById('repairForm').addEventListener('change', updatePreview);
    });

    // Init appareil hidden field
    document.getElementById('appareilSelect').dispatchEvent(new Event('change'));

    // Barcode
    function initBarcode() {
        if (typeof JsBarcode !== 'undefined') {
            JsBarcode('#barcodePreview', '{{ $numero }}', { format: 'CODE128', width: 2, height: 50, displayValue: true, fontSize: 14, font: 'Courier New', margin: 5, textMargin: 2 });
        }
    }

    function printTicket() {
        const content = document.getElementById('receiptPreview').innerHTML;
        const css = `
            @page { size: 80mm auto; margin: 0; }
            * { margin:0; padding:0; box-sizing:border-box; }
            body { font-family:'Courier New',monospace; font-size:13px; line-height:1.4; width:72mm; max-width:72mm; padding:3mm 4mm; color:#000; }
            .text-center,.center { text-align:center; }
            .text-xs,.small { font-size:11px; }
            .text-sm { font-size:13px; }
            .text-base,.text-lg,.total-row { font-size:16px; }
            .font-bold,.font-semibold,.bold { font-weight:bold; }
            .uppercase { text-transform:uppercase; }
            .italic { font-style:italic; }
            .underline { text-decoration:underline; }
            .flex { display:flex; }
            .justify-between { justify-content:space-between; }
            .border-t { border-top:1px solid #000; }
            .border-b { border-bottom:1px solid #000; }
            .border-dashed { border-style:dashed; }
            .mb-0\\.5,.mb-1 { margin-bottom:2px; }
            .mb-2 { margin-bottom:4px; }
            .mt-1 { margin-top:3px; }
            .py-1,.pt-1 { padding-top:3px; padding-bottom:3px; }
            .hidden { display:none; }
            svg { width:100%; max-width:64mm; height:auto; }
            p { margin:0; }
        `;
        const w = window.open('', '_blank', 'width=420,height=600');
        w.document.write('<html><head><title>Ticket</title><style>'+css+'</style></head><body>'+content+'</body></html>');
        w.document.close();
        w.onload = function() { setTimeout(function(){ w.print(); }, 200); };
    }

    function printBarcode() {
        const svg = document.getElementById('barcodePreview');
        if (!svg) return;
        const css = '@page{size:58mm auto;margin:0}body{margin:0;padding:3mm;text-align:center;font-family:monospace}svg{width:100%;max-width:50mm;height:auto}';
        const w = window.open('', '_blank', 'width=320,height=200');
        w.document.write('<html><head><title>Code barre</title><style>'+css+'</style></head><body>'+svg.outerHTML+'</body></html>');
        w.document.close();
        w.onload = function() { setTimeout(function(){ w.print(); }, 200); };
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js" onload="initBarcode()"></script>
@endpush
@endsection
