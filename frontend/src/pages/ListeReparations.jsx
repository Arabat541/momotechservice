import React, { useState, useMemo } from 'react';
import PropTypes from 'prop-types';
import { motion } from 'framer-motion';
import { Eye, Trash2, Search, Filter, Download, ListChecks } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from "@/components/ui/alert-dialog";
import { toast } from '@/components/ui/use-toast';
// Modal for editing is removed as RecuPreview handles edits now.

const STATUTS = ["En attente", "En cours", "Terminé", "Annulé"];

const ListeReparations = ({ reparations, onView, onDeleteRepair, onUpdateRepair, loadingReparations }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [filterType, setFilterType] = useState('all');
  const [filterStatut, setFilterStatut] = useState('all');
  const [sortConfig, setSortConfig] = useState({ key: 'date_creation', direction: 'descending' });

  const formattedReparations = useMemo(() => {
    return reparations.map(r => ({
      ...r,
      typeDisplay: r.type_reparation === 'place' ? 'Sur Place' : 'Sur RDV',
      dateSort: new Date(r.date_creation), 
      client: r.client_nom, 
      appareil: r.appareil_marque_modele,
      total: r.total_reparation,
      statut: r.statut_reparation,
      // Correction : toujours afficher le numéro de réparation
      numeroReparation: r.numeroReparation || r.numero_reparation || '',
      telephone: r.client_telephone,
      pannes: r.pannes_services?.map(p => p.description).join('; ') || '',
      montants: r.pannes_services?.map(p => p.montant).join('; ') || '',
      paye: r.montant_paye,
      resteAPayer: r.reste_a_payer,
      miseEnReparation: r.date_mise_en_reparation,
      dateRendezVous: r.date_rendez_vous,
      dateRetrait: r.date_retrait,
      etatPaiement: r.etat_paiement,
    }));
  }, [reparations]);

  const filteredAndSortedReparations = useMemo(() => {
    let filtered = formattedReparations;

    if (filterType !== 'all') {
      filtered = filtered.filter(r => r.type_reparation === filterType);
    }
    if (filterStatut !== 'all') {
      filtered = filtered.filter(r => r.statut === filterStatut);
    }
    if (searchTerm) {
      filtered = filtered.filter(r =>
        r.client?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        r.appareil?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        r.numeroReparation?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    return [...filtered].sort((a, b) => {
      let aValue = a[sortConfig.key];
      let bValue = b[sortConfig.key];

      if (sortConfig.key === 'date_creation' || sortConfig.key === 'dateSort') {
        aValue = new Date(a.date_creation); // Use date_creation for sorting
        bValue = new Date(b.date_creation);
      } else if (sortConfig.key === 'total_reparation') {
        aValue = parseFloat(aValue);
        bValue = parseFloat(bValue);
      }

      if (aValue < bValue) return sortConfig.direction === 'ascending' ? -1 : 1;
      if (aValue > bValue) return sortConfig.direction === 'ascending' ? 1 : -1;
      return 0;
    });
  }, [formattedReparations, searchTerm, filterType, filterStatut, sortConfig]);

  const requestSort = (key) => {
    let direction = 'ascending';
    if (sortConfig.key === key && sortConfig.direction === 'ascending') {
      direction = 'descending';
    }
    setSortConfig({ key, direction });
  };

  const getSortIndicator = (key) => {
    if (sortConfig.key === key) {
      return sortConfig.direction === 'ascending' ? ' ▲' : ' ▼';
    }
    return '';
  };
  
  const handleDelete = async (repairId) => {
    await onDeleteRepair(repairId);
  };

  const exportToCSV = () => {
    const headers = ["N° Réparation", "Type", "Client", "Téléphone", "Appareil", "Pannes/Services", "Total", "Payé", "Reste à Payer", "Statut", "Date Création", "Date RDV", "Date Retrait", "État Paiement"];
    const rows = filteredAndSortedReparations.map(r => [
      `"${r.numeroReparation || ''}"`,
      `"${r.typeDisplay || ''}"`,
      `"${r.client || ''}"`,
      `"${r.telephone || ''}"`,
      `"${r.appareil || ''}"`,
      `"${r.pannes_services?.map(p => `${p.description} (${p.montant} cfa)`).join('; ') || ''}"`,
      r.total_reparation || 0,
      r.montant_paye || 0,
      r.reste_a_payer || 0,
      `"${r.statut_reparation || ''}"`,
      `"${r.date_creation ? new Date(r.date_creation).toLocaleString('fr-FR') : ''}"`,
      `"${r.date_rendez_vous ? new Date(r.date_rendez_vous).toLocaleDateString('fr-FR') : ''}"`,
      `"${r.date_retrait ? new Date(r.date_retrait).toLocaleString('fr-FR') : ''}"`,
      `"${r.etat_paiement || ''}"`
    ]);

    let csvContent = "data:text/csv;charset=utf-8," 
        + headers.join(",") + "\n" 
        + rows.map(e => e.join(",")).join("\n");
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "liste_reparations.csv");
    document.body.appendChild(link); 
    link.click();
    document.body.removeChild(link);
    toast({ title: "Exportation CSV", description: "Liste des réparations exportée." });
  };


  return (
    <motion.div 
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="space-y-6 p-4 sm:p-6 bg-white rounded-xl shadow-2xl"
    >
      <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
        <h1 className="text-2xl font-bold text-gray-800">Liste de Toutes les Réparations</h1>
        <Button onClick={exportToCSV} variant="outline" size="sm" className="text-sm">
          <Download size={16} className="mr-2" /> Exporter CSV
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div>
          <Label htmlFor="search" className="text-sm font-medium text-gray-700">Rechercher</Label>
          <div className="relative mt-1">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
            <Input
              id="search"
              type="text"
              placeholder="Client, Appareil, N°..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 w-full"
            />
          </div>
        </div>
        <div>
          <Label htmlFor="filterType" className="text-sm font-medium text-gray-700">Type de Réparation</Label>
          <Select value={filterType} onValueChange={setFilterType}>
            <SelectTrigger id="filterType" className="w-full mt-1">
              <SelectValue placeholder="Tous les types" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Tous les types</SelectItem>
              <SelectItem value="place">Sur Place</SelectItem>
              <SelectItem value="rdv">Sur RDV</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div>
          <Label htmlFor="filterStatut" className="text-sm font-medium text-gray-700">Statut</Label>
          <Select value={filterStatut} onValueChange={setFilterStatut}>
            <SelectTrigger id="filterStatut" className="w-full mt-1">
              <SelectValue placeholder="Tous les statuts" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Tous les statuts</SelectItem>
              <SelectItem value="En cours">En cours</SelectItem>
              <SelectItem value="Terminé">Terminé</SelectItem>
              <SelectItem value="Annulé">Annulé</SelectItem>
              <SelectItem value="En attente">En attente</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      
      {loadingReparations && (
        <div className="text-center py-10">
          <ListChecks size={48} className="mx-auto mb-2 text-gray-400 animate-pulse" />
          <p className="text-gray-500">Chargement des réparations...</p>
        </div>
      )}

      {!loadingReparations && (
        <div className="overflow-x-auto bg-white rounded-lg shadow-md">
          <Table>
            <TableHeader className="bg-gradient-to-r from-slate-100 to-gray-100">
              <TableRow>
                <TableHead className="cursor-pointer hover:bg-gray-200" onClick={() => requestSort('numero_reparation')}>N° Réparation{getSortIndicator('numero_reparation')}</TableHead>
                <TableHead className="cursor-pointer hover:bg-gray-200" onClick={() => requestSort('type_reparation')}>Type{getSortIndicator('type_reparation')}</TableHead>
                <TableHead className="cursor-pointer hover:bg-gray-200" onClick={() => requestSort('client_nom')}>Client{getSortIndicator('client_nom')}</TableHead>
                <TableHead>Appareil</TableHead>
                <TableHead className="cursor-pointer hover:bg-gray-200 text-right" onClick={() => requestSort('total_reparation')}>Total (fcfa){getSortIndicator('total_reparation')}</TableHead>
                <TableHead className="cursor-pointer hover:bg-gray-200" onClick={() => requestSort('date_creation')}>Date Création{getSortIndicator('date_creation')}</TableHead>
                <TableHead className="cursor-pointer hover:bg-gray-200" onClick={() => requestSort('statut_reparation')}>Statut{getSortIndicator('statut_reparation')}</TableHead>
                <TableHead className="text-center">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredAndSortedReparations.map((repair) => (
                <TableRow key={repair.id || repair._id || repair.numeroReparation} className="hover:bg-gray-50 transition-colors">
                  <TableCell className="font-medium text-blue-600">{repair.numeroReparation}</TableCell>
                  <TableCell>
                    <span className={`px-2 py-0.5 text-xs font-semibold rounded-full ${
                      repair.type_reparation === 'place' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'
                    }`}>
                      {repair.typeDisplay}
                    </span>
                  </TableCell>
                  <TableCell>
                    <div>{repair.client}</div>
                    <div className="text-xs text-gray-500">{repair.telephone}</div>
                  </TableCell>
                  <TableCell>{repair.appareil}</TableCell>
                  <TableCell className="text-right font-semibold">{repair.total?.toLocaleString('fr-FR')}</TableCell>
                  <TableCell>{repair.date_creation ? new Date(repair.date_creation).toLocaleDateString('fr-FR') : 'N/A'}</TableCell>
                  <TableCell>
                    <Select
                      value={repair.statut_reparation}
                      onValueChange={async (newStatut) => {
                        console.log('Select statut changed:', newStatut, repair);
                        if (newStatut !== repair.statut_reparation && typeof onUpdateRepair === 'function') {
                          await onUpdateRepair({ ...repair, statut_reparation: newStatut });
                        }
                      }}
                    >
                      <SelectTrigger className={`w-full text-xs ${
                        repair.statut_reparation === 'Terminé' ? 'bg-green-100 text-green-800' : 
                        (repair.statut_reparation === 'En cours' || repair.statut_reparation === 'En attente') ? 'bg-yellow-100 text-yellow-800' : 
                        'bg-red-100 text-red-800'
                      }`}>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {STATUTS.map(s => (
                          <SelectItem key={s} value={s}>{s}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell className="text-center space-x-1">
                    <Button size="icon" variant="ghost" onClick={() => onView(repair)} className="text-blue-600 hover:text-blue-800 h-8 w-8">
                      <Eye size={18} />
                    </Button>
                    {/* Edit button removed, editing is done via RecuPreview */}
                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button size="icon" variant="ghost" className="text-red-600 hover:text-red-800 h-8 w-8">
                          <Trash2 size={18} />
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Êtes-vous sûr de vouloir supprimer?</AlertDialogTitle>
                          <AlertDialogDescription>
                            Cette action est irréversible et supprimera définitivement la réparation <span className="font-semibold">{repair.numeroReparation}</span>.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Annuler</AlertDialogCancel>
                          <AlertDialogAction onClick={() => handleDelete(repair.id || repair._id || repair.numeroReparation)} className="bg-red-600 hover:bg-red-700">Supprimer</AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {filteredAndSortedReparations.length === 0 && (
            <div className="text-center py-10 text-gray-500">
              <Filter size={48} className="mx-auto mb-2 text-gray-400" />
              Aucune réparation ne correspond à vos critères.
            </div>
          )}
        </div>
      )}
    </motion.div>
  );
};

ListeReparations.propTypes = {
  reparations: PropTypes.arrayOf(PropTypes.object).isRequired,
  onView: PropTypes.func.isRequired,
  onDeleteRepair: PropTypes.func.isRequired,
  onUpdateRepair: PropTypes.func,
  loadingReparations: PropTypes.bool,
};

export default ListeReparations;
