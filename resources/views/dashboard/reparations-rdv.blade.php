@extends('layouts.dashboard')

@section('page-title', 'Nouvelle Réparation sur RDV')

@section('content')
<div class="bg-gray-50 rounded-xl shadow-2xl w-full p-2 sm:p-4">
    <div class="p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800">Nouvelle Réparation sur RDV</h1>
    </div>

    <div class="flex flex-col md:flex-row">
        <form action="{{ route('reparations.store') }}" method="POST" class="w-full md:w-3/5 p-4 space-y-3 md:border-r border-gray-200" id="repairForm">
            @csrf
            <input type="hidden" name="type_reparation" value="rdv">

            <div>
                <label class="text-xs font-medium text-gray-600">N° Réparation</label>
                <input type="text" name="numeroReparation" value="{{ $numero }}" readonly
                       class="w-full text-sm py-1.5 border-gray-300 rounded-md bg-gray-100 px-3">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600">Client</label>
                    <input type="text" name="client_nom" required class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">Téléphone</label>
                    <input type="text" name="client_telephone" required class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border">
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600">Appareil</label>
                <select id="appareilSelect" onchange="handleAppareilChange(this)"
                        class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border focus:ring-blue-500">
                    <option value="">-- Modèles iPhone --</option>
                    @foreach(['iPhone 16e','iPhone 16','iPhone 16 Plus','iPhone 16 Pro','iPhone 16 Pro Max','iPhone 15','iPhone 15 Plus','iPhone 15 Pro','iPhone 15 Pro Max','iPhone 14','iPhone 14 Plus','iPhone 14 Pro','iPhone 14 ProMax','iPhone 13','iPhone 13 Mini','iPhone 13 Pro','iPhone 13 Pro Max','iPhone 12 classique','iPhone 12 Mini','iPhone 12 Pro','iPhone 12 Pro Max','iPhone 11 classique','iPhone 11 Pro','iPhone 11 Pro Max','iPhone X classique','iPhone XR','iPhone XS','iPhone XS Max','iPhone SE','iPhone SE 2020','iPhone SE 2022','iPhone 8','iPhone 8 Plus','iPhone 7','iPhone 7 Plus','iPhone 6'] as $model)
                        <option value="{{ $model }}">{{ $model }}</option>
                    @endforeach
                </select>
                <input type="text" name="appareil_marque_modele" id="appareilValue" required
                       placeholder="Ou saisir directement la marque et modèle"
                       class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border focus:ring-blue-500 mt-1.5">
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600">Date de Rendez-vous</label>
                <input type="date" name="date_rendez_vous"
                       value="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                       class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border">
            </div>

            {{-- Pannes / Services --}}
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Pannes et Montants</label>
                <div id="pannesContainer">
                    <div class="flex items-center space-x-2 mb-1.5 panne-row">
                        <input type="text" name="panne_description[]" placeholder="Service 1" class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border">
                        <input type="number" name="panne_montant[]" placeholder="Montant" step="any" class="w-1/3 text-sm py-1.5 border-gray-300 rounded-md px-3 border no-spinner" oninput="calculateTotal()">
                    </div>
                </div>
                <button type="button" onclick="addPanne()" class="text-xs py-1 px-3 border rounded-md text-blue-600 hover:bg-blue-50 mt-1">
                    <i class="fas fa-plus-circle mr-1"></i> Ajouter Service
                </button>
            </div>

            {{-- Pièces --}}
            <div>
                <label class="text-xs font-medium text-gray-600 mb-1 block">Pièces de rechange</label>
                <div id="piecesContainer"></div>
                @if($stocks->count() > 0)
                    <button type="button" onclick="addPiece()" class="text-xs py-1 px-3 border rounded-md text-blue-600 hover:bg-blue-50 mt-1">
                        <i class="fas fa-search mr-1"></i> Ajouter Pièce
                    </button>
                @else
                    <p class="text-xs text-gray-500">Aucune pièce en stock.</p>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600">Total</label>
                    <input type="number" id="totalDisplay" readonly
                           class="w-full text-sm py-1.5 border-gray-300 rounded-md bg-gray-100 font-semibold px-3 border">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">Payé</label>
                    <input type="number" name="montant_paye" id="montantPaye" step="any" required value="0"
                           class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border no-spinner" oninput="calculateTotal()">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">Reste</label>
                    <input type="number" id="resteDisplay" readonly
                           class="w-full text-sm py-1.5 border-gray-300 rounded-md bg-gray-100 px-3 border">
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600">Moyen de paiement</label>
                <select name="mode_paiement" id="modePaiement" class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border" onchange="updatePreview()">
                    <option value="">— Non précisé —</option>
                    <option value="especes">Espèces</option>
                    <option value="orange_money">Orange Money</option>
                    <option value="wave">Wave</option>
                    <option value="mtn_money">MTN Money</option>
                    <option value="cheque">Chèque</option>
                    <option value="virement">Virement</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600">Statut</label>
                <select name="statut_reparation" class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border">
                    <option value="En attente" selected>En attente</option>
                    <option value="En cours">En cours</option>
                    <option value="Terminé">Terminé</option>
                    <option value="Annulé">Annulé</option>
                </select>
            </div>

            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-3 border-t">
                <button type="reset" class="px-4 py-2 text-xs bg-red-100 text-red-700 hover:bg-red-200 border border-red-200 rounded-md">Effacer</button>
                <button type="submit" class="px-4 py-2 text-xs bg-green-600 hover:bg-green-700 text-white rounded-md font-semibold">Enregistrer</button>
            </div>
        </form>

        <div class="w-full md:w-2/5 p-2 bg-white flex flex-col items-center justify-start">
            <div class="bg-white text-gray-800 text-sm w-[302px] p-3 shadow-lg border border-gray-300 mt-4" id="receiptPreview">
                <div class="text-center mb-2">
                    <img src="/images/logo-receipt.png" alt="MTS" class="w-14 h-14 mx-auto mb-1">
                    <h2 class="text-lg font-bold uppercase">{{ $settings->companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</h2>
                    <p class="text-xs">{{ $settings->companyInfo['adresse'] ?? '' }}</p>
                    <p class="text-xs">Tél: {{ $settings->companyInfo['telephone'] ?? '' }}</p>
                    <p class="text-xs italic">{{ $settings->companyInfo['slogan'] ?? '' }}</p>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between"><span class="font-semibold">N° Réparation:</span><span>{{ $numero }}</span></div>
                    <div class="flex justify-between"><span class="font-semibold">Date création:</span><span>{{ now()->format('d/m/Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="font-semibold">Date RDV:</span><span id="prevRdvDate">{{ \Carbon\Carbon::tomorrow()->format('d/m/Y') }}</span></div>
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
                    <div class="flex justify-between text-xs" id="prevModeWrap" style="display:none">
                        <span>Mode:</span><span id="prevMode"></span>
                    </div>
                </div>
                <div class="text-center mb-2 py-2">
                    <div id="qrPreview" class="flex justify-center items-center"></div>
                    <p class="text-xs text-gray-500 mt-1 font-mono">{{ $numero }}</p>
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
                    <span>Statut: <span id="prevStatut">En attente</span></span> |
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

{{-- Modal popup after save --}}
<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full mx-4 max-h-[90vh] overflow-y-auto relative">
        <button onclick="closeModal()" class="absolute top-2 right-3 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
        <div class="p-4">
            <div id="modalReceiptContent"></div>
            <div class="flex gap-2 justify-center mt-4 pb-2">
                <button onclick="printTicket()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded-md font-semibold"><i class="fas fa-print mr-1"></i> Imprimer ticket</button>
                <button onclick="printBarcode()" class="px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white text-xs rounded-md font-semibold"><i class="fas fa-barcode mr-1"></i> Imprimer code barre</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const stocksData = @json($stocks);
    let panneCount = 1;

    function handleAppareilChange(select) {
        if (select.value) {
            document.getElementById('appareilValue').value = select.value;
        }
    }

    function addPanne() {
        if (panneCount >= 4) return;
        panneCount++;
        const c = document.getElementById('pannesContainer');
        const row = document.createElement('div');
        row.className = 'flex items-center space-x-2 mb-1.5 panne-row';
        row.innerHTML = `<input type="text" name="panne_description[]" placeholder="Service ${panneCount}" class="w-full text-sm py-1.5 border-gray-300 rounded-md px-3 border"><input type="number" name="panne_montant[]" placeholder="Montant" step="any" class="w-1/3 text-sm py-1.5 border-gray-300 rounded-md px-3 border no-spinner" oninput="calculateTotal()"><button type="button" onclick="this.closest('.panne-row').remove(); panneCount--; calculateTotal()" class="text-red-500 hover:text-red-700 px-2 py-1"><i class="fas fa-trash text-xs"></i></button>`;
        c.appendChild(row);
    }

    function addPiece() {
        const c = document.getElementById('piecesContainer');
        if (c.querySelectorAll('.piece-row').length >= 5) return;
        const row = document.createElement('div');
        row.className = 'flex items-center space-x-2 mb-1.5 piece-row';
        let opts = '<option value="">Choisir...</option>';
        stocksData.forEach(s => { opts += `<option value="${s.id}" data-prix="${s.prixVente}">${s.nom} (${s.quantite}, ${s.prixVente} cfa)</option>`; });
        row.innerHTML = `<select name="piece_stock_id[]" class="w-2/3 text-sm py-1.5 border-gray-300 rounded-md px-3 border" onchange="calculateTotal()">${opts}</select><input type="number" name="piece_quantite[]" value="1" min="1" class="w-1/4 text-sm py-1.5 border-gray-300 rounded-md px-3 border" oninput="calculateTotal()"><button type="button" onclick="this.closest('.piece-row').remove(); calculateTotal()" class="text-red-500 hover:text-red-700 px-2 py-1"><i class="fas fa-trash text-xs"></i></button>`;
        c.appendChild(row);
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('input[name="panne_montant[]"]').forEach(el => { total += parseFloat(el.value) || 0; });
        document.querySelectorAll('.piece-row').forEach(row => {
            const sel = row.querySelector('select');
            const qty = parseInt(row.querySelector('input').value) || 0;
            const prix = parseFloat(sel.options[sel.selectedIndex]?.dataset?.prix) || 0;
            total += prix * qty;
        });
        document.getElementById('totalDisplay').value = total;
        const paye = parseFloat(document.getElementById('montantPaye').value) || 0;
        document.getElementById('resteDisplay').value = total - paye;
        updatePreview();
    }

    function formatNumber(n) {
        return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function updatePreview() {
        document.getElementById('prevClient').textContent = document.querySelector('input[name="client_nom"]').value || '---';
        document.getElementById('prevTel').textContent = document.querySelector('input[name="client_telephone"]').value || '---';
        document.getElementById('prevAppareil').textContent = document.getElementById('appareilValue').value || '---';

        // RDV date
        const rdvInput = document.querySelector('input[name="date_rendez_vous"]');
        if (rdvInput && rdvInput.value) {
            const parts = rdvInput.value.split('-');
            document.getElementById('prevRdvDate').textContent = parts[2] + '/' + parts[1] + '/' + parts[0];
        }

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
        document.getElementById('prevTotal').textContent = formatNumber(total) + ' cfa';
        document.getElementById('prevPaye').textContent = formatNumber(paye) + ' cfa';
        document.getElementById('prevReste').textContent = formatNumber(total - paye) + ' cfa';

        // Statut & Paiement
        const statutSelect = document.querySelector('select[name="statut_reparation"]');
        if (statutSelect) {
            document.getElementById('prevStatut').textContent = statutSelect.value;
        }
        const etatPaiement = (paye >= total && total > 0) ? 'Soldé' : 'Non soldé';
        document.getElementById('prevPaiement').textContent = etatPaiement;

        // Moyen de paiement
        const modeLabels = { especes: 'Espèces', orange_money: 'Orange Money', wave: 'Wave', mtn_money: 'MTN Money', cheque: 'Chèque', virement: 'Virement' };
        const modeSelect = document.getElementById('modePaiement');
        const modeWrap   = document.getElementById('prevModeWrap');
        if (modeSelect && modeSelect.value) {
            document.getElementById('prevMode').textContent = modeLabels[modeSelect.value] || modeSelect.value;
            modeWrap.style.display = 'flex';
        } else if (modeWrap) {
            modeWrap.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        form.addEventListener('input', updatePreview);
        form.addEventListener('change', updatePreview);
        const observer = new MutationObserver(() => { setTimeout(updatePreview, 50); });
        observer.observe(document.getElementById('pannesContainer'), { childList: true, subtree: true });
        observer.observe(document.getElementById('piecesContainer'), { childList: true, subtree: true });
        // Initialisation + détection autofill tardif
        updatePreview();
        setTimeout(updatePreview, 500);
    });

    let savedRepairId = null;

    function initQrPreview() {
        const el = document.getElementById('qrPreview');
        if (!el || typeof QRCode === 'undefined') return;
        el.innerHTML = '';
        new QRCode(el, { text: '{{ $numero }}', width: 110, height: 110, colorDark: '#000', colorLight: '#fff', correctLevel: QRCode.CorrectLevel.M });
    }

    function printTicket() {
        if (savedRepairId) {
            window.open('{{ url("/dashboard/reparations") }}/' + savedRepairId + '/recu', '_blank');
        } else {
            alert('Enregistrez d\'abord la réparation pour imprimer le ticket.');
        }
    }

    function printBarcode() {
        if (savedRepairId) {
            window.open('{{ url("/dashboard/reparations") }}/' + savedRepairId + '/etiquette', '_blank');
        } else {
            alert('Enregistrez d\'abord la réparation pour imprimer l\'étiquette.');
        }
    }

    // AJAX form submit + popup
    document.getElementById('repairForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Enregistrement...';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                savedRepairId = data.id;
                document.getElementById('modalReceiptContent').innerHTML = document.getElementById('receiptPreview').innerHTML;
                document.getElementById('successModal').classList.remove('hidden');
            } else {
                alert('Erreur lors de l\'enregistrement');
                btn.disabled = false;
                btn.textContent = 'Enregistrer';
            }
        })
        .catch(() => {
            alert('Erreur réseau');
            btn.disabled = false;
            btn.textContent = 'Enregistrer';
        });
    });

    function closeModal() {
        document.getElementById('successModal').classList.add('hidden');
        window.location.href = '{{ route("reparations.liste") }}';
    }

    document.addEventListener('DOMContentLoaded', function() { initQrPreview(); });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" onload="initQrPreview()"></script>
@endpush
@endsection
