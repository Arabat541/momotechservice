<?php $__env->startSection('page-title', 'Détail Réparation'); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white rounded-xl shadow-2xl p-6 space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800"><?php echo e($repair->numeroReparation); ?></h1>
            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?php echo e($repair->type_reparation === 'place' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'); ?>">
                <?php echo e($repair->type_reparation === 'place' ? 'Sur Place' : 'Sur RDV'); ?>

            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('reparations.receipt', $repair->id)); ?>" target="_blank"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                <i class="fas fa-print mr-2"></i> Imprimer Reçu
            </a>
            <a href="<?php echo e(route('reparations.liste')); ?>" class="inline-flex items-center px-4 py-2 border rounded-md hover:bg-gray-50 text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <form action="<?php echo e(route('reparations.update', $repair->id)); ?>" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Client</label>
                    <input type="text" name="client_nom" value="<?php echo e($repair->client_nom); ?>"
                           class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Téléphone</label>
                    <input type="text" name="client_telephone" value="<?php echo e($repair->client_telephone); ?>"
                           class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Appareil</label>
                <input type="text" name="appareil_marque_modele" value="<?php echo e($repair->appareil_marque_modele); ?>"
                       class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Statut</label>
                    <select name="statut_reparation" class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
                        <?php $__currentLoopData = ['En attente', 'En cours', 'Terminé', 'Annulé']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($s); ?>" <?php echo e($repair->statut_reparation === $s ? 'selected' : ''); ?>><?php echo e($s); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Montant payé</label>
                    <input type="number" name="montant_paye" step="any" value="<?php echo e($repair->montant_paye); ?>"
                           class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border no-spinner">
                </div>
            </div>

            <?php if($repair->type_reparation === 'rdv'): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Date RDV</label>
                <input type="date" name="date_rendez_vous"
                       value="<?php echo e($repair->date_rendez_vous ? $repair->date_rendez_vous->format('Y-m-d') : ''); ?>"
                       class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-3 pt-3 border-t">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-1"></i> Mettre à jour
                </button>
            </div>
        </form>

        
        <div class="bg-gray-50 rounded-lg p-5 space-y-4">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Résumé</h3>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-gray-500">Date création:</span>
                    <p class="font-medium"><?php echo e($repair->date_creation ? $repair->date_creation->format('d/m/Y H:i') : 'N/A'); ?></p>
                </div>
                <div>
                    <span class="text-gray-500">État paiement:</span>
                    <p class="font-medium <?php echo e($repair->etat_paiement === 'Soldé' ? 'text-green-600' : 'text-red-600'); ?>"><?php echo e($repair->etat_paiement); ?></p>
                </div>
                <div>
                    <span class="text-gray-500">Total:</span>
                    <p class="font-bold text-lg"><?php echo e(number_format($repair->total_reparation, 0, ',', ' ')); ?> cfa</p>
                </div>
                <div>
                    <span class="text-gray-500">Reste à payer:</span>
                    <p class="font-bold text-lg <?php echo e($repair->reste_a_payer > 0 ? 'text-red-600' : 'text-green-600'); ?>"><?php echo e(number_format($repair->reste_a_payer, 0, ',', ' ')); ?> cfa</p>
                </div>
                <?php if($repair->date_retrait): ?>
                <div>
                    <span class="text-gray-500">Date retrait:</span>
                    <p class="font-medium text-green-600"><?php echo e($repair->date_retrait->format('d/m/Y H:i')); ?></p>
                </div>
                <?php endif; ?>
                <?php if($repair->date_rendez_vous): ?>
                <div>
                    <span class="text-gray-500">Date RDV:</span>
                    <p class="font-medium"><?php echo e($repair->date_rendez_vous->format('d/m/Y')); ?></p>
                </div>
                <?php endif; ?>
            </div>

            
            <div class="border-t pt-3">
                <form action="<?php echo e(route('reparations.update', $repair->id)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <?php if($repair->date_retrait): ?>
                        <input type="hidden" name="unmark_retrieved" value="1">
                        <button type="submit" class="text-sm px-4 py-2 bg-green-100 text-green-700 border border-green-200 rounded-md hover:bg-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Récupéré — Annuler
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="mark_retrieved" value="1">
                        <button type="submit" class="text-sm px-4 py-2 bg-orange-100 text-orange-700 border border-orange-200 rounded-md hover:bg-orange-200">
                            <i class="fas fa-hand-holding mr-1"></i> Marquer comme récupéré
                        </button>
                    <?php endif; ?>
                </form>
            </div>

            
            <?php if(is_array($repair->pannes_services) && count($repair->pannes_services) > 0): ?>
            <div class="border-t pt-3">
                <h4 class="font-semibold text-gray-600 mb-2">Pannes / Services</h4>
                <?php $__currentLoopData = $repair->pannes_services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $panne): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex justify-between text-sm py-1">
                    <span><?php echo e($panne['description'] ?? ''); ?></span>
                    <span class="font-medium"><?php echo e(number_format($panne['montant'] ?? 0, 0, ',', ' ')); ?> cfa</span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>

            
            <?php if(is_array($repair->pieces_rechange_utilisees) && count($repair->pieces_rechange_utilisees) > 0): ?>
            <div class="border-t pt-3">
                <h4 class="font-semibold text-gray-600 mb-2">Pièces de rechange</h4>
                <?php $__currentLoopData = $repair->pieces_rechange_utilisees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $piece): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex justify-between text-sm py-1">
                    <span><?php echo e($piece['nom'] ?? ''); ?> (x<?php echo e($piece['quantiteUtilisee'] ?? 0); ?>)</span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /data/projects/node.js/momotechservice/momotech-app/resources/views/dashboard/reparation-detail.blade.php ENDPATH**/ ?>