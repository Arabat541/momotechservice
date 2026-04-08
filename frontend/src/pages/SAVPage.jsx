import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { motion, AnimatePresence } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/use-toast';
import Modal from '@/components/shared/Modal';
import { lookupRepairForSAV } from '@/lib/api';
import {
  ShieldAlert,
  Plus,
  Search,
  Clock,
  Wrench,
  CheckCircle2,
  XCircle,
  Trash2,
  Eye,
  Loader2,
  Link as LinkIcon,
  ShieldCheck,
  ShieldX,
} from 'lucide-react';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

const statusConfig = {
  'En attente': { color: 'bg-yellow-100 text-yellow-800 border-yellow-300', icon: Clock, label: 'En attente' },
  'En cours': { color: 'bg-blue-100 text-blue-800 border-blue-300', icon: Wrench, label: 'En cours' },
  'Résolu': { color: 'bg-green-100 text-green-800 border-green-300', icon: CheckCircle2, label: 'Résolu' },
  'Refusé': { color: 'bg-red-100 text-red-800 border-red-300', icon: XCircle, label: 'Refusé' },
};

function StatusBadge({ statut }) {
  const config = statusConfig[statut] || statusConfig['En attente'];
  const Icon = config.icon;
  return (
    <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium border ${config.color}`}>
      <Icon className="w-3 h-3" /> {config.label}
    </span>
  );
}

function WarrantyBadge({ sousGarantie, dateFinGarantie }) {
  if (sousGarantie === undefined && !dateFinGarantie) return null;
  if (sousGarantie) {
    return (
      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium border bg-emerald-100 text-emerald-800 border-emerald-300">
        <ShieldCheck className="w-3 h-3" /> Sous garantie
        {dateFinGarantie && (
          <span className="ml-1 opacity-75">
            (jusqu'au {new Date(dateFinGarantie).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })})
          </span>
        )}
      </span>
    );
  }
  return (
    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium border bg-gray-100 text-gray-600 border-gray-300">
      <ShieldX className="w-3 h-3" /> Hors garantie
    </span>
  );
}

function generateNumeroSAV() {
  const timestamp = Date.now().toString().slice(-6);
  const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
  return `SAV${timestamp}${random}`;
}

const SAVPage = ({ savList, loadingSAV, addSAV, editSAV, removeSAV, currentUser }) => {
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [selectedSAV, setSelectedSAV] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatut, setFilterStatut] = useState('Tous');

  // Create form state
  const [form, setForm] = useState({
    numeroReparationOrigine: '',
    client_nom: '',
    client_telephone: '',
    appareil_marque_modele: '',
    description_probleme: '',
    notes: '',
  });
  const [lookupLoading, setLookupLoading] = useState(false);
  const [linkedRepair, setLinkedRepair] = useState(null);

  const resetForm = () => {
    setForm({
      numeroReparationOrigine: '',
      client_nom: '',
      client_telephone: '',
      appareil_marque_modele: '',
      description_probleme: '',
      notes: '',
    });
    setLinkedRepair(null);
  };

  const handleLookupRepair = async () => {
    if (!form.numeroReparationOrigine.trim()) return;
    setLookupLoading(true);
    try {
      const token = localStorage.getItem('token');
      const repair = await lookupRepairForSAV(token, form.numeroReparationOrigine.trim());
      setLinkedRepair(repair);
      setForm(prev => ({
        ...prev,
        client_nom: repair.client_nom,
        client_telephone: repair.client_telephone,
        appareil_marque_modele: repair.appareil_marque_modele,
      }));
      toast({ title: "Réparation trouvée", description: `Client: ${repair.client_nom}` });
    } catch (e) {
      toast({ variant: "destructive", title: "Introuvable", description: e.message });
      setLinkedRepair(null);
    } finally {
      setLookupLoading(false);
    }
  };

  const handleCreateSAV = async (e) => {
    e.preventDefault();
    if (!form.client_nom || !form.appareil_marque_modele || !form.description_probleme) {
      toast({ variant: "destructive", title: "Champs requis", description: "Remplissez le client, l'appareil et la description." });
      return;
    }
    const savData = {
      ...form,
      numeroSAV: generateNumeroSAV(),
      userId: currentUser?._id || currentUser?.id || '',
      statut: 'En attente',
    };
    const result = await addSAV(savData);
    if (result) {
      setShowCreateModal(false);
      resetForm();
    }
  };

  const handleUpdateStatut = async (sav, newStatut) => {
    await editSAV(sav._id, {
      statut: newStatut,
      ...(newStatut === 'Résolu' || newStatut === 'Refusé' ? { date_resolution: new Date() } : {}),
    });
  };

  const handleUpdateDecision = async (sav, decision) => {
    await editSAV(sav._id, { decision });
  };

  const handleUpdateNotes = async (sav, notes) => {
    await editSAV(sav._id, { notes });
  };

  // Filtered & searched list
  const filteredList = savList
    .filter(s => filterStatut === 'Tous' || s.statut === filterStatut)
    .filter(s => {
      if (!searchTerm) return true;
      const term = searchTerm.toLowerCase();
      return (
        (s.numeroSAV || '').toLowerCase().includes(term) ||
        (s.client_nom || '').toLowerCase().includes(term) ||
        (s.client_telephone || '').includes(term) ||
        (s.appareil_marque_modele || '').toLowerCase().includes(term) ||
        (s.numeroReparationOrigine || '').toLowerCase().includes(term)
      );
    });

  // Stats
  const stats = {
    total: savList.length,
    enAttente: savList.filter(s => s.statut === 'En attente').length,
    enCours: savList.filter(s => s.statut === 'En cours').length,
    resolu: savList.filter(s => s.statut === 'Résolu').length,
    refuse: savList.filter(s => s.statut === 'Refusé').length,
    sousGarantie: savList.filter(s => s.sous_garantie).length,
    horsGarantie: savList.filter(s => s.numeroReparationOrigine && !s.sous_garantie).length,
  };

  return (
    <div className="p-4 md:p-6 space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <ShieldAlert className="w-7 h-7 text-orange-600" />
            Service Après-Vente (SAV)
          </h2>
          <p className="text-sm text-slate-500 mt-1">Gérez les retours et réclamations clients</p>
        </div>
        <Button onClick={() => { resetForm(); setShowCreateModal(true); }} className="bg-orange-600 hover:bg-orange-700 text-white">
          <Plus className="w-4 h-4 mr-2" /> Nouvelle demande SAV
        </Button>
      </div>

      {/* Stats cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        {[
          { label: 'Total', value: stats.total, color: 'bg-slate-100 text-slate-800' },
          { label: 'En attente', value: stats.enAttente, color: 'bg-yellow-100 text-yellow-800' },
          { label: 'En cours', value: stats.enCours, color: 'bg-blue-100 text-blue-800' },
          { label: 'Résolus', value: stats.resolu, color: 'bg-green-100 text-green-800' },
          { label: 'Refusés', value: stats.refuse, color: 'bg-red-100 text-red-800' },
          { label: 'Sous garantie', value: stats.sousGarantie, color: 'bg-emerald-100 text-emerald-800' },
          { label: 'Hors garantie', value: stats.horsGarantie, color: 'bg-gray-100 text-gray-600' },
        ].map(s => (
          <div key={s.label} className={`rounded-xl p-3 text-center ${s.color}`}>
            <p className="text-2xl font-bold">{s.value}</p>
            <p className="text-xs font-medium">{s.label}</p>
          </div>
        ))}
      </div>

      {/* Search + filter */}
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <Input
            placeholder="Rechercher par numéro, client, appareil..."
            value={searchTerm}
            onChange={e => setSearchTerm(e.target.value)}
            className="pl-9"
          />
        </div>
        <select
          value={filterStatut}
          onChange={e => setFilterStatut(e.target.value)}
          className="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white"
        >
          <option value="Tous">Tous les statuts</option>
          <option value="En attente">En attente</option>
          <option value="En cours">En cours</option>
          <option value="Résolu">Résolu</option>
          <option value="Refusé">Refusé</option>
        </select>
      </div>

      {/* SAV List */}
      {loadingSAV ? (
        <div className="flex items-center justify-center h-40">
          <Loader2 className="w-8 h-8 animate-spin text-orange-500" />
        </div>
      ) : filteredList.length === 0 ? (
        <div className="text-center py-16 text-slate-400">
          <ShieldAlert className="w-12 h-12 mx-auto mb-3 opacity-30" />
          <p className="text-lg font-medium">Aucune demande SAV</p>
          <p className="text-sm">{searchTerm || filterStatut !== 'Tous' ? 'Aucun résultat pour cette recherche.' : 'Créez votre première demande SAV.'}</p>
        </div>
      ) : (
        <div className="space-y-3">
          <AnimatePresence>
            {filteredList.map((sav, index) => (
              <motion.div
                key={sav._id}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0, transition: { delay: index * 0.03 } }}
                exit={{ opacity: 0, y: -10 }}
                className="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition"
              >
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="font-mono text-sm font-bold text-orange-700">{sav.numeroSAV}</span>
                      <StatusBadge statut={sav.statut} />
                      {sav.numeroReparationOrigine && (
                        <WarrantyBadge sousGarantie={sav.sous_garantie} dateFinGarantie={sav.date_fin_garantie} />
                      )}
                      {sav.numeroReparationOrigine && (
                        <span className="inline-flex items-center gap-1 text-xs text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">
                          <LinkIcon className="w-3 h-3" /> {sav.numeroReparationOrigine}
                        </span>
                      )}
                    </div>
                    <p className="text-sm text-slate-700 mt-1 font-medium">{sav.client_nom} — {sav.appareil_marque_modele}</p>
                    <p className="text-xs text-slate-500 mt-0.5 line-clamp-1">{sav.description_probleme}</p>
                    <p className="text-xs text-slate-400 mt-1">
                      {new Date(sav.date_creation).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}
                      {sav.date_resolution && (
                        <span className="ml-2 text-green-600">
                          • Résolu le {new Date(sav.date_resolution).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })}
                        </span>
                      )}
                    </p>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => { setSelectedSAV(sav); setShowDetailModal(true); }}
                    >
                      <Eye className="w-4 h-4 mr-1" /> Détails
                    </Button>
                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button variant="outline" size="sm" className="text-red-600 hover:bg-red-50">
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Supprimer ce SAV ?</AlertDialogTitle>
                          <AlertDialogDescription>
                            La demande SAV {sav.numeroSAV} sera définitivement supprimée.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Annuler</AlertDialogCancel>
                          <AlertDialogAction onClick={() => removeSAV(sav._id)} className="bg-red-600 hover:bg-red-700">
                            Supprimer
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </div>
                </div>
              </motion.div>
            ))}
          </AnimatePresence>
        </div>
      )}

      {/* Create Modal */}
      {showCreateModal && (
        <Modal onClose={() => setShowCreateModal(false)} title="Nouvelle demande SAV">
          <form onSubmit={handleCreateSAV} className="space-y-4">
            {/* Repair lookup */}
            <div>
              <Label className="text-sm font-medium text-slate-600 mb-1 block">Lier à une réparation (optionnel)</Label>
              <div className="flex gap-2">
                <Input
                  placeholder="Numéro de réparation (ex: RP123456)"
                  value={form.numeroReparationOrigine}
                  onChange={e => setForm(prev => ({ ...prev, numeroReparationOrigine: e.target.value }))}
                />
                <Button type="button" variant="outline" onClick={handleLookupRepair} disabled={lookupLoading || !form.numeroReparationOrigine.trim()}>
                  {lookupLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Search className="w-4 h-4" />}
                </Button>
              </div>
              {linkedRepair && (
                <div className="mt-2 space-y-1">
                  <p className="text-xs text-green-600 flex items-center gap-1">
                    <CheckCircle2 className="w-3 h-3" /> Réparation liée : {linkedRepair.client_nom} — {linkedRepair.appareil_marque_modele}
                  </p>
                  <WarrantyBadge sousGarantie={linkedRepair.sous_garantie} dateFinGarantie={linkedRepair.date_fin_garantie} />
                  {linkedRepair.date_retrait && (
                    <p className="text-xs text-slate-500">
                      Retrait le {new Date(linkedRepair.date_retrait).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}
                      {linkedRepair.duree_garantie && ` • Garantie : ${linkedRepair.duree_garantie}`}
                    </p>
                  )}
                  {!linkedRepair.date_retrait && (
                    <p className="text-xs text-yellow-600">⚠ Réparation non retirée — garantie non applicable</p>
                  )}
                </div>
              )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <Label className="text-sm font-medium text-slate-600 mb-1 block">Nom du client *</Label>
                <Input
                  value={form.client_nom}
                  onChange={e => setForm(prev => ({ ...prev, client_nom: e.target.value }))}
                  required
                />
              </div>
              <div>
                <Label className="text-sm font-medium text-slate-600 mb-1 block">Téléphone *</Label>
                <Input
                  value={form.client_telephone}
                  onChange={e => setForm(prev => ({ ...prev, client_telephone: e.target.value }))}
                  required
                />
              </div>
            </div>

            <div>
              <Label className="text-sm font-medium text-slate-600 mb-1 block">Appareil *</Label>
              <Input
                value={form.appareil_marque_modele}
                onChange={e => setForm(prev => ({ ...prev, appareil_marque_modele: e.target.value }))}
                placeholder="Marque et modèle"
                required
              />
            </div>

            <div>
              <Label className="text-sm font-medium text-slate-600 mb-1 block">Description du problème *</Label>
              <textarea
                value={form.description_probleme}
                onChange={e => setForm(prev => ({ ...prev, description_probleme: e.target.value }))}
                className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm min-h-[80px] focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none"
                placeholder="Décrivez le problème rencontré par le client..."
                required
              />
            </div>

            <div>
              <Label className="text-sm font-medium text-slate-600 mb-1 block">Notes internes</Label>
              <textarea
                value={form.notes}
                onChange={e => setForm(prev => ({ ...prev, notes: e.target.value }))}
                className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm min-h-[60px] focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none"
                placeholder="Notes internes (non visibles par le client)..."
              />
            </div>

            <div className="flex gap-2 justify-end">
              <Button type="button" variant="outline" onClick={() => setShowCreateModal(false)}>Annuler</Button>
              <Button type="submit" className="bg-orange-600 hover:bg-orange-700 text-white">
                <Plus className="w-4 h-4 mr-1" /> Créer le SAV
              </Button>
            </div>
          </form>
        </Modal>
      )}

      {/* Detail Modal */}
      {showDetailModal && selectedSAV && (
        <SAVDetailModal
          sav={selectedSAV}
          onClose={() => { setShowDetailModal(false); setSelectedSAV(null); }}
          onUpdateStatut={handleUpdateStatut}
          onUpdateDecision={handleUpdateDecision}
          onUpdateNotes={handleUpdateNotes}
        />
      )}
    </div>
  );
};

function SAVDetailModal({ sav, onClose, onUpdateStatut, onUpdateDecision, onUpdateNotes }) {
  const [decision, setDecision] = useState(sav.decision || '');
  const [notes, setNotes] = useState(sav.notes || '');
  const [saving, setSaving] = useState(false);

  const handleSaveDecision = async () => {
    setSaving(true);
    await onUpdateDecision(sav, decision);
    setSaving(false);
  };

  const handleSaveNotes = async () => {
    setSaving(true);
    await onUpdateNotes(sav, notes);
    setSaving(false);
  };

  const nextStatuses = {
    'En attente': ['En cours', 'Refusé'],
    'En cours': ['Résolu', 'Refusé'],
    'Résolu': [],
    'Refusé': ['En attente'],
  };

  const availableStatuses = nextStatuses[sav.statut] || [];

  return (
    <Modal onClose={onClose} title={`SAV ${sav.numeroSAV}`}>
      <div className="space-y-5">
        {/* Status + linked repair */}
        <div className="flex items-center justify-between flex-wrap gap-2">
          <StatusBadge statut={sav.statut} />
          {sav.numeroReparationOrigine && (
            <span className="text-xs text-purple-600 bg-purple-50 px-2 py-1 rounded-full flex items-center gap-1">
              <LinkIcon className="w-3 h-3" /> Réparation {sav.numeroReparationOrigine}
            </span>
          )}
        </div>

        {/* Warranty status */}
        {sav.numeroReparationOrigine && (
          <div className={`flex items-center gap-3 p-3 rounded-lg border ${sav.sous_garantie ? 'bg-emerald-50 border-emerald-200' : 'bg-gray-50 border-gray-200'}`}>
            <WarrantyBadge sousGarantie={sav.sous_garantie} dateFinGarantie={sav.date_fin_garantie} />
            {sav.sous_garantie && (
              <p className="text-xs text-emerald-700">Prise en charge gratuite au titre de la garantie</p>
            )}
            {!sav.sous_garantie && sav.date_fin_garantie && (
              <p className="text-xs text-gray-600">
                Garantie expirée le {new Date(sav.date_fin_garantie).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
              </p>
            )}
          </div>
        )}

        {/* Client info */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-slate-50 rounded-lg">
          <div>
            <p className="text-xs text-slate-500">Client</p>
            <p className="font-medium text-slate-900">{sav.client_nom}</p>
          </div>
          <div>
            <p className="text-xs text-slate-500">Téléphone</p>
            <p className="font-medium text-slate-900">{sav.client_telephone}</p>
          </div>
          <div>
            <p className="text-xs text-slate-500">Appareil</p>
            <p className="font-medium text-slate-900">{sav.appareil_marque_modele}</p>
          </div>
          <div>
            <p className="text-xs text-slate-500">Date de création</p>
            <p className="font-medium text-slate-900">
              {new Date(sav.date_creation).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
            </p>
          </div>
        </div>

        {/* Problem description */}
        <div>
          <p className="text-sm font-medium text-slate-600 mb-1">Problème signalé</p>
          <div className="p-3 bg-orange-50 border border-orange-200 rounded-lg text-sm text-slate-800 whitespace-pre-line">
            {sav.description_probleme}
          </div>
        </div>

        {/* Decision */}
        <div>
          <Label className="text-sm font-medium text-slate-600 mb-1 block">Décision / Action prise</Label>
          <textarea
            value={decision}
            onChange={e => setDecision(e.target.value)}
            className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm min-h-[60px] focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none"
            placeholder="Ex: Remplacement gratuit, réparation offerte, remboursement..."
          />
          <Button
            size="sm"
            variant="outline"
            className="mt-2"
            onClick={handleSaveDecision}
            disabled={saving || decision === sav.decision}
          >
            Enregistrer la décision
          </Button>
        </div>

        {/* Notes */}
        <div>
          <Label className="text-sm font-medium text-slate-600 mb-1 block">Notes internes</Label>
          <textarea
            value={notes}
            onChange={e => setNotes(e.target.value)}
            className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm min-h-[60px] focus:ring-2 focus:ring-slate-400 focus:border-transparent outline-none"
            placeholder="Notes privées..."
          />
          <Button
            size="sm"
            variant="outline"
            className="mt-2"
            onClick={handleSaveNotes}
            disabled={saving || notes === sav.notes}
          >
            Enregistrer les notes
          </Button>
        </div>

        {/* Status actions */}
        {availableStatuses.length > 0 && (
          <div>
            <p className="text-sm font-medium text-slate-600 mb-2">Changer le statut</p>
            <div className="flex flex-wrap gap-2">
              {availableStatuses.map(status => {
                const config = statusConfig[status];
                const Icon = config.icon;
                return (
                  <Button
                    key={status}
                    variant="outline"
                    size="sm"
                    onClick={() => onUpdateStatut(sav, status)}
                    className={`border ${config.color.replace('bg-', 'border-').split(' ')[0]}`}
                  >
                    <Icon className="w-4 h-4 mr-1" /> {status}
                  </Button>
                );
              })}
            </div>
          </div>
        )}

        {sav.date_resolution && (
          <p className="text-sm text-green-600 font-medium">
            {sav.statut === 'Résolu' ? 'Résolu' : 'Refusé'} le {new Date(sav.date_resolution).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
          </p>
        )}
      </div>
    </Modal>
  );
}

SAVPage.propTypes = {
  savList: PropTypes.array.isRequired,
  loadingSAV: PropTypes.bool.isRequired,
  addSAV: PropTypes.func.isRequired,
  editSAV: PropTypes.func.isRequired,
  removeSAV: PropTypes.func.isRequired,
  currentUser: PropTypes.object,
};

export default SAVPage;
