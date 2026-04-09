<?php $__env->startSection('page-title', 'Paramètres'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-8 pb-12" x-data="parametresPage()">
    <h3 class="text-2xl font-bold text-gradient">Paramètres de l'application</h3>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <div class="bg-white rounded-xl shadow-xl p-6 border border-slate-200">
            <h4 class="text-xl font-semibold mb-4 text-slate-700 flex items-center">
                <i class="fas fa-file-lines mr-2 text-blue-600"></i>
                Informations de l'entreprise
            </h4>
            <form action="<?php echo e(route('parametres.update')); ?>" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Nom de l'entreprise</label>
                    <input type="text" name="nomEntreprise" value="<?php echo e($settings->companyInfo['nom'] ?? ''); ?>"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Slogan</label>
                    <input type="text" name="slogan" value="<?php echo e($settings->companyInfo['slogan'] ?? ''); ?>"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Adresse</label>
                    <input type="text" name="adresse" value="<?php echo e($settings->companyInfo['adresse'] ?? ''); ?>"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Téléphone</label>
                    <input type="text" name="telephone" value="<?php echo e($settings->companyInfo['telephone'] ?? ''); ?>"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>

                
                <input type="hidden" name="duree_garantie" value="<?php echo e($settings->warranty['duree'] ?? '7'); ?>">
                <input type="hidden" name="message_garantie" value="<?php echo e($settings->warranty['conditions'] ?? ''); ?>">

                <button type="submit" class="mt-4 w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-2 rounded-lg font-semibold">
                    <i class="fas fa-save mr-2"></i> Enregistrer Infos Entreprise
                </button>
            </form>
        </div>

        
        <div class="bg-white rounded-xl shadow-xl p-6 border border-slate-200">
            <h4 class="text-xl font-semibold mb-4 text-slate-700 flex items-center">
                <i class="fas fa-shield-halved mr-2 text-green-600"></i>
                Paramètres de garantie
            </h4>
            <form action="<?php echo e(route('parametres.update')); ?>" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Durée de garantie (jours)</label>
                    <input type="number" name="duree_garantie" value="<?php echo e($settings->warranty['duree'] ?? '7'); ?>"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-green-500 no-spinner">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Message de garantie</label>
                    <textarea name="message_garantie" rows="3"
                              class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-green-500"><?php echo e($settings->warranty['conditions'] ?? ''); ?></textarea>
                </div>

                
                <input type="hidden" name="nomEntreprise" value="<?php echo e($settings->companyInfo['nom'] ?? ''); ?>">
                <input type="hidden" name="slogan" value="<?php echo e($settings->companyInfo['slogan'] ?? ''); ?>">
                <input type="hidden" name="adresse" value="<?php echo e($settings->companyInfo['adresse'] ?? ''); ?>">
                <input type="hidden" name="telephone" value="<?php echo e($settings->companyInfo['telephone'] ?? ''); ?>">

                <button type="submit" class="mt-4 w-full bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white py-2 rounded-lg font-semibold">
                    <i class="fas fa-save mr-2"></i> Enregistrer Paramètres Garantie
                </button>
            </form>
        </div>
    </div>

    
    <div class="bg-white rounded-xl shadow-xl p-6 border border-slate-200">
        <h4 class="text-xl font-semibold mb-4 text-slate-700 flex items-center">
            <i class="fas fa-user mr-2 text-indigo-600"></i>
            Gestion des utilisateurs
        </h4>

        <div class="flex flex-wrap gap-2 mb-4">
            <a href="<?php echo e(route('users.export.csv')); ?>" class="inline-flex items-center px-3 py-1.5 text-sm border rounded-md hover:bg-gray-50">
                <i class="fas fa-file-csv mr-1"></i> Exporter CSV
            </a>
        </div>

        <h5 class="text-md font-medium text-slate-600 mb-3">Liste des utilisateurs</h5>
        <?php if($users->isEmpty()): ?>
            <p class="text-center text-slate-500 p-4">Aucun utilisateur trouvé.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <i class="fas <?php echo e($u->role === 'patron' ? 'fa-shield-halved text-amber-500' : 'fa-user text-blue-500'); ?> text-xl mr-3"></i>
                    <div>
                        <p class="font-medium text-slate-800"><?php echo e($u->nom); ?> <?php echo e($u->prenom); ?></p>
                        <p class="text-xs text-slate-500"><?php echo e($u->email); ?></p>
                        <span class="text-xs text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full capitalize"><?php echo e($u->role); ?></span>
                    </div>
                </div>
                <?php if($user->id !== $u->id && $u->role !== 'patron'): ?>
                <form action="<?php echo e(route('users.destroy', $u->id)); ?>" method="POST"
                      onsubmit="return confirm('Supprimer le compte de <?php echo e(addslashes($u->email)); ?> ?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="text-red-500 hover:bg-red-100 hover:text-red-600 px-3 py-1.5 rounded text-sm">
                        <i class="fas fa-trash mr-1"></i> Supprimer
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        
        <div class="mt-6 pt-6 border-t border-slate-200">
            <h5 class="text-md font-medium text-slate-600 mb-3 flex items-center">
                <i class="fas fa-user-plus mr-2 text-green-600"></i>
                Créer un compte employé
            </h5>
            <form action="<?php echo e(route('users.register')); ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Nom</label>
                    <input type="text" name="nom" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Prénoms</label>
                    <input type="text" name="prenom" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
                    <input type="email" name="email" required placeholder="email@example.com" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Mot de passe</label>
                    <input type="password" name="password" required minlength="8" placeholder="Min. 8 caractères" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white py-2 rounded-lg font-semibold">
                        <i class="fas fa-user-plus mr-2"></i> Créer l'employé
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div class="bg-white rounded-xl shadow-xl p-6 border border-slate-200">
        <h4 class="text-xl font-semibold mb-4 text-slate-700 flex items-center">
            <i class="fas fa-store mr-2 text-purple-600"></i>
            Gestion des boutiques
        </h4>

        <?php if($shops->isEmpty()): ?>
            <p class="text-center text-slate-500 p-4">Aucune boutique configurée.</p>
        <?php else: ?>
        <div class="space-y-4">
            <?php $__currentLoopData = $shops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $isCurrent = session('current_shop_id') === $shop->id; ?>
            <div class="p-4 rounded-lg border <?php echo e($isCurrent ? 'border-purple-300 bg-purple-50' : 'border-slate-200 bg-slate-50'); ?> shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-store text-purple-500"></i>
                        <span class="font-semibold text-slate-800"><?php echo e($shop->nom); ?></span>
                        <?php if($isCurrent): ?>
                            <span class="text-xs bg-purple-200 text-purple-700 px-2 py-0.5 rounded-full">Active</span>
                        <?php endif; ?>
                    </div>
                    <form action="<?php echo e(route('shops.destroy', $shop->id)); ?>" method="POST"
                          onsubmit="return confirm('Supprimer la boutique <?php echo e(addslashes($shop->nom)); ?> et toutes ses données ?')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="text-red-500 hover:bg-red-100 hover:text-red-600 px-3 py-1.5 rounded text-sm">
                            <i class="fas fa-trash mr-1"></i> Supprimer
                        </button>
                    </form>
                </div>
                <div class="text-sm text-slate-600 mb-3">
                    <p>Adresse: <?php echo e($shop->adresse ?? '-'); ?></p>
                    <p>Téléphone: <?php echo e($shop->telephone ?? '-'); ?></p>
                </div>

                
                <div class="border-t pt-2">
                    <p class="text-xs font-medium text-slate-500 mb-1">Utilisateurs assignés:</p>
                    <?php $__currentLoopData = $shop->users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shopUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-sm"><?php echo e($shopUser->nom); ?> <?php echo e($shopUser->prenom); ?> (<?php echo e($shopUser->email); ?>)</span>
                        <?php if($shopUser->role !== 'patron'): ?>
                        <form action="<?php echo e(route('shops.removeUser', $shop->id)); ?>" method="POST"
                              onsubmit="return confirm('Retirer <?php echo e(addslashes($shopUser->email)); ?> de cette boutique ?')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <input type="hidden" name="userId" value="<?php echo e($shopUser->id); ?>">
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    
                    <form action="<?php echo e(route('shops.addUser', $shop->id)); ?>" method="POST" class="flex items-center gap-2 mt-2">
                        <?php echo csrf_field(); ?>
                        <select name="userId" class="flex-1 text-xs border rounded px-2 py-1">
                            <option value="">Ajouter un utilisateur...</option>
                            <?php $__currentLoopData = $users->where('role', '!=', 'patron'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $availableUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(!$shop->users->contains('id', $availableUser->id)): ?>
                                <option value="<?php echo e($availableUser->id); ?>"><?php echo e($availableUser->nom); ?> <?php echo e($availableUser->prenom); ?></option>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit" class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                            <i class="fas fa-user-plus"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function parametresPage() {
    return {};
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /data/projects/node.js/momotechservice/momotech-app/resources/views/dashboard/parametres.blade.php ENDPATH**/ ?>