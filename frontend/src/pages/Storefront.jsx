import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { getShopPublicInfo, trackRepair } from '@/lib/api';
import { Loader2, Phone, Mail, MapPin, Search, ArrowLeft, Wrench, CheckCircle2, Clock, CalendarCheck } from 'lucide-react';

const statusConfig = {
  'En attente': { color: 'bg-yellow-100 text-yellow-800', icon: Clock },
  'En cours': { color: 'bg-blue-100 text-blue-800', icon: Wrench },
  'Terminée': { color: 'bg-green-100 text-green-800', icon: CheckCircle2 },
  'Livrée': { color: 'bg-emerald-100 text-emerald-800', icon: CheckCircle2 },
  'Annulée': { color: 'bg-red-100 text-red-800', icon: Clock },
};

function StatusBadge({ statut }) {
  const config = statusConfig[statut] || { color: 'bg-gray-100 text-gray-800', icon: Clock };
  const Icon = config.icon;
  return (
    <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium ${config.color}`}>
      <Icon className="w-4 h-4" />
      {statut}
    </span>
  );
}

function RepairTracker({ shopId }) {
  const [numero, setNumero] = useState('');
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleTrack = async (e) => {
    e.preventDefault();
    if (!numero.trim()) return;
    setLoading(true);
    setError('');
    setResult(null);
    try {
      const data = await trackRepair(numero.trim());
      setResult(data);
    } catch (err) {
      setError(err.message || 'Réparation introuvable.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white rounded-2xl shadow-lg p-6 md:p-8">
      <h2 className="text-xl font-bold text-gray-900 mb-2 flex items-center gap-2">
        <Search className="w-5 h-5 text-blue-600" />
        Suivre ma réparation
      </h2>
      <p className="text-gray-500 text-sm mb-6">Entrez votre numéro de réparation pour voir l'état d'avancement.</p>

      <form onSubmit={handleTrack} className="flex gap-2">
        <input
          type="text"
          value={numero}
          onChange={(e) => setNumero(e.target.value)}
          placeholder="Ex: RP123456789"
          className="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-base"
          maxLength={20}
        />
        <button
          type="submit"
          disabled={loading || !numero.trim()}
          className="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white rounded-xl font-medium transition flex items-center gap-2"
        >
          {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Search className="w-4 h-4" />}
          Suivre
        </button>
      </form>

      {error && (
        <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
          {error}
        </div>
      )}

      {result && (
        <div className="mt-6 p-5 bg-gray-50 rounded-xl border border-gray-200 space-y-4">
          <div className="flex items-center justify-between flex-wrap gap-2">
            <h3 className="font-bold text-lg text-gray-900">N° {result.numeroReparation}</h3>
            <StatusBadge statut={result.statut} />
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
              <span className="text-gray-500">Appareil</span>
              <p className="font-medium text-gray-900">{result.appareil}</p>
            </div>
            <div>
              <span className="text-gray-500">Type</span>
              <p className="font-medium text-gray-900">{result.type === 'place' ? 'Sur place' : 'Rendez-vous'}</p>
            </div>
            <div>
              <span className="text-gray-500">Date de dépôt</span>
              <p className="font-medium text-gray-900">
                {new Date(result.date_creation).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
              </p>
            </div>
            {result.date_rendez_vous && (
              <div>
                <span className="text-gray-500">Rendez-vous</span>
                <p className="font-medium text-gray-900 flex items-center gap-1">
                  <CalendarCheck className="w-4 h-4 text-blue-500" />
                  {new Date(result.date_rendez_vous).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
                </p>
              </div>
            )}
            {result.date_retrait && (
              <div>
                <span className="text-gray-500">Date de retrait</span>
                <p className="font-medium text-green-700">
                  {new Date(result.date_retrait).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
                </p>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

export default function Storefront() {
  const { shopId } = useParams();
  const [shopInfo, setShopInfo] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!shopId) return;
    setLoading(true);
    getShopPublicInfo(shopId)
      .then(setShopInfo)
      .catch(() => setError('Boutique introuvable.'))
      .finally(() => setLoading(false));
  }, [shopId]);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
        <Loader2 className="w-10 h-10 animate-spin text-blue-600" />
      </div>
    );
  }

  if (error || !shopInfo) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 px-4">
        <div className="bg-white rounded-2xl shadow-lg p-8 text-center max-w-md">
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Boutique introuvable</h1>
          <p className="text-gray-500 mb-6">Le lien semble invalide ou la boutique n'existe plus.</p>
          <Link to="/auth" className="text-blue-600 hover:underline flex items-center justify-center gap-1">
            <ArrowLeft className="w-4 h-4" /> Retour à la connexion
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b border-gray-100">
        <div className="max-w-4xl mx-auto px-4 py-6 flex items-center gap-4">
          {shopInfo.logoUrl && (
            <img src={shopInfo.logoUrl} alt={shopInfo.nom} className="w-14 h-14 rounded-xl object-cover" />
          )}
          <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-900">{shopInfo.nom}</h1>
            {shopInfo.slogan && <p className="text-gray-500 text-sm mt-0.5">{shopInfo.slogan}</p>}
          </div>
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-4 py-8 space-y-8">
        {/* Contact info cards */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {shopInfo.telephone && (
            <a href={`tel:${shopInfo.telephone}`} className="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition group">
              <div className="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition">
                <Phone className="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <p className="text-xs text-gray-500">Téléphone</p>
                <p className="font-medium text-gray-900">{shopInfo.telephone}</p>
              </div>
            </a>
          )}
          {shopInfo.email && (
            <a href={`mailto:${shopInfo.email}`} className="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition group">
              <div className="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition">
                <Mail className="w-5 h-5 text-purple-600" />
              </div>
              <div>
                <p className="text-xs text-gray-500">Email</p>
                <p className="font-medium text-gray-900 break-all">{shopInfo.email}</p>
              </div>
            </a>
          )}
          {shopInfo.adresse && (
            <div className="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                <MapPin className="w-5 h-5 text-green-600" />
              </div>
              <div>
                <p className="text-xs text-gray-500">Adresse</p>
                <p className="font-medium text-gray-900">{shopInfo.adresse}</p>
              </div>
            </div>
          )}
        </div>

        {/* Repair tracker */}
        <RepairTracker shopId={shopId} />

        {/* Warranty info */}
        {(shopInfo.warranty?.duree || shopInfo.warranty?.conditions) && (
          <div className="bg-white rounded-2xl shadow-lg p-6 md:p-8">
            <h2 className="text-xl font-bold text-gray-900 mb-4">Garantie</h2>
            {shopInfo.warranty.duree && (
              <p className="text-sm text-gray-700 mb-2">
                <span className="font-medium">Durée :</span> {shopInfo.warranty.duree}
              </p>
            )}
            {shopInfo.warranty.conditions && (
              <p className="text-sm text-gray-600 whitespace-pre-line">{shopInfo.warranty.conditions}</p>
            )}
          </div>
        )}
      </main>

      {/* Footer */}
      <footer className="border-t border-gray-200 bg-white mt-12">
        <div className="max-w-4xl mx-auto px-4 py-6 text-center text-sm text-gray-400">
          &copy; {new Date().getFullYear()} {shopInfo.nom}. Tous droits réservés.
        </div>
      </footer>
    </div>
  );
}
