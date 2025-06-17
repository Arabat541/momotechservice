import React from 'react';
import PropTypes from 'prop-types';
import { Outlet, NavLink, useNavigate, useLocation } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  Smartphone, 
  Calendar, 
  List, 
  Package, 
  Settings, 
  LogOut,
  Menu,
  X,
  Loader2
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useUserRole } from '@/hooks/useAuth';

const menuItems = [
  { id: 'reparations-place', label: 'Réparation sur place', icon: Smartphone, path: '/reparations-place' },
  { id: 'reparations-rdv', label: 'Réparation sur rdv', icon: Calendar, path: '/reparations-rdv' },
  { id: 'article', label: 'Vente de Pièce détâchée', icon: Package, path: '/article' },
  { id: 'liste-reparations', label: 'Liste-réparation', icon: List, path: '/liste-reparations' },
  { id: 'stocks', label: 'Gestion des stocks', icon: Package, path: '/stocks', onlyPatron: true },
  { id: 'parametres', label: 'Paramètres', icon: Settings, path: '/parametres', onlyPatron: true }
];

const DashboardLayout = ({ currentUser, onLogout = () => {}, loadingApp }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const [isSidebarOpen, setIsSidebarOpen] = React.useState(true); 

  const getCurrentLabel = () => {
    const currentPath = location.pathname;
    const activeItem = menuItems.find(item => currentPath === item.path || (currentPath.startsWith(item.path) && item.path !== '/')); 
    if (activeItem) return activeItem.label;
    
    const defaultItem = menuItems.find(item => item.path === '/reparations-place'); 
    return defaultItem ? defaultItem.label : 'Tableau de Bord';
  };
  
  const sidebarVariants = {
    open: { width: 256, transition: { type: "spring", stiffness: 300, damping: 30 } },
    closed: { width: 80, transition: { type: "spring", stiffness: 300, damping: 30 } }
  };

  const contentVariants = {
    open: { marginLeft: 256, transition: { type: "spring", stiffness: 300, damping: 30 } },
    closed: { marginLeft: 80, transition: { type: "spring", stiffness: 300, damping: 30 } }
  };

  // Supposons que le token JWT est stocké dans le localStorage
  const token = localStorage.getItem('token');
  const role = useUserRole(token);

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-100 to-blue-100 flex">
      <motion.div 
        variants={sidebarVariants}
        initial={false}
        animate={isSidebarOpen ? "open" : "closed"}
        className="min-h-screen sidebar-gradient shadow-2xl flex flex-col justify-between fixed left-0 top-0 bottom-0 z-40 overflow-x-hidden"
      >
        <div>
          <div className={`p-4 flex items-center ${isSidebarOpen ? 'justify-between' : 'justify-center'} h-20`}>
            {isSidebarOpen && (
              <motion.h1 
                initial={{ opacity:0, x: -20}}
                animate={{ opacity:1, x: 0}}
                exit={{ opacity:0, x: -20}}
                transition={{delay: 0.1}}
                className="text-xl font-bold text-white truncate"
              >
                MOMO TECH
              </motion.h1>
            )}
             <Button 
              variant="ghost" 
              size="icon" 
              onClick={() => setIsSidebarOpen(!isSidebarOpen)} 
              className="text-white hover:bg-white/10"
              aria-label={isSidebarOpen ? "Réduire le menu" : "Ouvrir le menu"}
            >
              {isSidebarOpen ? <X size={20} /> : <Menu size={20} />}
            </Button>
          </div>
          <nav className={`space-y-2 ${isSidebarOpen ? 'p-4' : 'p-2'}`}>
            {menuItems.map((item) => {
              const Icon = item.icon;
              if (item.onlyPatron && role !== 'patron') return null;
              return (
                <NavLink
                  key={item.id}
                  to={item.path}
                  className={({ isActive }) =>
                    `w-full flex items-center space-x-3 rounded-lg transition-all duration-200 group
                    ${isSidebarOpen ? 'px-4 py-3' : 'p-3 justify-center'}
                    ${
                      isActive
                        ? 'bg-white/20 text-white shadow-md'
                        : 'text-blue-100 hover:bg-white/10 hover:text-white'
                    }`
                  }
                  title={isSidebarOpen ? '' : item.label}
                >
                    <Icon size={isSidebarOpen ? 20 : 24} />
                    {isSidebarOpen && <motion.span initial={{opacity:0}} animate={{opacity:1, transition:{delay:0.2}}} className="font-medium text-sm whitespace-nowrap">{item.label}</motion.span>}
                </NavLink>
              );
            })}
          </nav>
        </div>
        <div className={`space-y-2 ${isSidebarOpen ? 'p-4' : 'p-2'}`}>
          <Button 
            onClick={() => { onLogout(); navigate('/auth', { replace: true }); }} 
            variant="ghost" 
            className={`w-full flex items-center space-x-3 rounded-lg text-red-200 hover:bg-red-500/30 hover:text-red-100 transition-colors
                        ${isSidebarOpen ? 'px-4 py-3' : 'p-3 justify-center'}`}
            title={isSidebarOpen ? '' : "Déconnexion"}
          >
            <LogOut size={isSidebarOpen ? 20 : 24} />
            {isSidebarOpen && <motion.span initial={{opacity:0}} animate={{opacity:1, transition:{delay:0.2}}} className="font-medium text-sm whitespace-nowrap">Déconnexion</motion.span>}
          </Button>
        </div>
      </motion.div>

      <motion.main 
        variants={contentVariants}
        initial={false}
        animate={isSidebarOpen ? "open" : "closed"}
        className="flex-1 p-4 sm:p-6 overflow-y-auto" 
      >
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="max-w-full mx-auto" 
        >
          <div className="mb-6">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <h2 className="text-xl sm:text-2xl font-bold text-gradient">
                {getCurrentLabel()}
              </h2>
              <div className="flex items-center space-x-4 w-full sm:w-auto">
                {/* Profil utilisateur à la place de la barre de recherche */}
                {currentUser && (
                  <div className="flex items-center space-x-2 bg-white rounded-lg px-3 py-2 shadow border border-gray-200">
                    <span className="inline-block w-8 h-8 rounded-full bg-blue-200 text-blue-700 flex items-center justify-center font-bold text-lg uppercase">
                      {currentUser.email ? currentUser.email[0].toUpperCase() : '?'}
                    </span>
                    <span className="text-sm font-medium text-gray-700">{currentUser.email}</span>
                  </div>
                )}
              </div>
            </div>
          </div>
          
          {loadingApp ? (
            <div className="flex flex-col items-center justify-center h-full min-h-[calc(100vh-200px)]">
                <Loader2 className="w-12 h-12 animate-spin text-blue-600 mb-4" />
                <p className="text-lg text-slate-700">Chargement des données...</p>
            </div>
          ) : (
            <div
              key={location.pathname} 
              className="w-full"
            >
              <Outlet /> 
            </div>
          )}

        </motion.div>
      </motion.main>
    </div>
  );
};

DashboardLayout.propTypes = {
  currentUser: PropTypes.shape({
    email: PropTypes.string,
  }),
  onLogout: PropTypes.func.isRequired,
  loadingApp: PropTypes.bool,
};

export default DashboardLayout;
