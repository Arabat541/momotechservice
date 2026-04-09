<?php $__env->startSection('page-title', 'Liste des Réparations'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 p-4 sm:p-6 bg-white rounded-xl shadow-2xl" x-data="listeReparations()">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Liste de Toutes les Réparations</h1>
        <a href="<?php echo e(route('reparations.export.csv')); ?>" class="inline-flex items-center px-4 py-2 text-sm border rounded-md hover:bg-gray-50">
            <i class="fas fa-download mr-2"></i> Exporter CSV
        </a>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div>
            <label class="text-sm font-medium text-gray-700">Rechercher</label>
            <div class="relative mt-1">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" x-model="search" placeholder="Client, Appareil, N°..."
                       class="pl-10 w-full text-sm py-2 border-gray-300 rounded-md border px-3">
            </div>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Type</label>
            <select x-model="filterType" class="w-full mt-1 text-sm py-2 border-gray-300 rounded-md border px-3">
                <option value="all">Tous les types</option>
                <option value="place">Sur Place</option>
                <option value="rdv">Sur RDV</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Statut</label>
            <select x-model="filterStatut" class="w-full mt-1 text-sm py-2 border-gray-300 rounded-md border px-3">
                <option value="all">Tous les statuts</option>
                <option value="En attente">En attente</option>
                <option value="En cours">En cours</option>
                <option value="Terminé">Terminé</option>
                <option value="Annulé">Annulé</option>
            </select>
        </div>
    </div>

    
    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-slate-100 to-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-200" @click="sort('numeroReparation')">
                        N° Réparation <span x-text="sortIndicator('numeroReparation')"></span>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-200" @click="sort('client_nom')">
                        Client <span x-text="sortIndicator('client_nom')"></span>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appareil</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-200" @click="sort('total_reparation')">
                        Total (fcfa) <span x-text="sortIndicator('total_reparation')"></span>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-200" @click="sort('date_creation')">
                        Date <span x-text="sortIndicator('date_creation')"></span>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Récupéré ?</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php $__empty_1 = true; $__currentLoopData = $repairs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $repair): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 transition-colors"
                    x-show="matchFilter('<?php echo e(addslashes($repair->type_reparation)); ?>', '<?php echo e(addslashes($repair->statut_reparation)); ?>', '<?php echo e(addslashes($repair->client_nom)); ?>', '<?php echo e(addslashes($repair->appareil_marque_modele)); ?>', '<?php echo e(addslashes($repair->numeroReparation)); ?>')"
                    x-cloak>
                    <td class="px-4 py-3 text-sm font-medium text-blue-600">
                        <a href="<?php echo e(route('reparations.show', $repair->id)); ?>" class="hover:underline"><?php echo e($repair->numeroReparation); ?></a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?php echo e($repair->type_reparation === 'place' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'); ?>">
                            <?php echo e($repair->type_reparation === 'place' ? 'Sur Place' : 'Sur RDV'); ?>

                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div><?php echo e($repair->client_nom); ?></div>
                        <div class="text-xs text-gray-500"><?php echo e($repair->client_telephone); ?></div>
                    </td>
                    <td class="px-4 py-3 text-sm"><?php echo e($repair->appareil_marque_modele); ?></td>
                    <td class="px-4 py-3 text-sm text-right font-semibold"><?php echo e(number_format($repair->total_reparation, 0, ',', ' ')); ?></td>
                    <td class="px-4 py-3 text-sm"><?php echo e($repair->date_creation ? $repair->date_creation->format('d/m/Y') : 'N/A'); ?></td>
                    <td class="px-4 py-3">
                        <form action="<?php echo e(route('reparations.update', $repair->id)); ?>" method="POST" class="inline">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <select name="statut_reparation" onchange="this.form.submit()"
                                    class="text-xs py-1 px-2 rounded-md border
                                    <?php echo e($repair->statut_reparation === 'Terminé' ? 'bg-green-100 text-green-800 border-green-200' :
                                       (in_array($repair->statut_reparation, ['En cours', 'En attente']) ? 'bg-yellow-100 text-yellow-800 border-yellow-200' :
                                       'bg-red-100 text-red-800 border-red-200')); ?>">
                                <?php $__currentLoopData = ['En attente', 'En cours', 'Terminé', 'Annulé']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($s); ?>" <?php echo e($repair->statut_reparation === $s ? 'selected' : ''); ?>><?php echo e($s); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        <form action="<?php echo e(route('reparations.update', $repair->id)); ?>" method="POST" class="inline">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <?php if($repair->date_retrait): ?>
                                <input type="hidden" name="unmark_retrieved" value="1">
                                <button type="submit" class="text-xs px-2 py-1 rounded-md bg-green-100 text-green-700 border border-green-200 hover:bg-green-200">Oui</button>
                            <?php else: ?>
                                <input type="hidden" name="mark_retrieved" value="1">
                                <button type="submit" class="text-xs px-2 py-1 rounded-md bg-red-100 text-red-700 border border-red-200 hover:bg-red-200">Non</button>
                            <?php endif; ?>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-center space-x-1">
                        <a href="<?php echo e(route('reparations.show', $repair->id)); ?>" class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form action="<?php echo e(route('reparations.destroy', $repair->id)); ?>" method="POST" class="inline"
                              onsubmit="return confirm('Supprimer cette réparation ?')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-800 hover:bg-red-50 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-gray-500">
                        <i class="fas fa-list text-4xl mb-2 block text-gray-300"></i>
                        Aucune réparation enregistrée.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 px-4">
        <?php echo e($repairs->links()); ?>

    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function listeReparations() {
    return {
        search: '',
        filterType: 'all',
        filterStatut: 'all',
        sortKey: 'date_creation',
        sortDir: 'desc',
        sort(key) {
            if (this.sortKey === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDir = 'asc';
            }
        },
        sortIndicator(key) {
            if (this.sortKey !== key) return '';
            return this.sortDir === 'asc' ? '▲' : '▼';
        },
        matchFilter(type, statut, client, appareil, numero) {
            if (this.filterType !== 'all' && type !== this.filterType) return false;
            if (this.filterStatut !== 'all' && statut !== this.filterStatut) return false;
            if (this.search) {
                const s = this.search.toLowerCase();
                if (!client.toLowerCase().includes(s) && !appareil.toLowerCase().includes(s) && !numero.toLowerCase().includes(s)) return false;
            }
            return true;
        }
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /data/projects/node.js/momotechservice/momotech-app/resources/views/dashboard/reparations-liste.blade.php ENDPATH**/ ?>