import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api, setCsrfToken } from '../lib/api';
import { setCurrencySymbol } from '../lib/format';

const AppContext = createContext(null);

export const useApp = () => {
  const ctx = useContext(AppContext);
  if (!ctx) throw new Error('useApp must be used inside <AppProvider>');
  return ctx;
};

const DEFAULT_STORE = {
  name: 'Books, Bits & Co',
  email: '',
  phone: '',
  phoneTel: '',
  whatsapp: '',
  facebookUrl: '#',
  instagramUrl: '#',
  currencySymbol: '\u20a6',
  currencyCode: 'NGN',
};

export function AppProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isAdmin, setIsAdmin] = useState(false);
  const [cartCount, setCartCount] = useState(0);
  const [store, setStore] = useState(DEFAULT_STORE);
  const [categories, setCategories] = useState([]);
  const [ready, setReady] = useState(false);
  const [toast, setToast] = useState(null);

  const loadSession = useCallback(async () => {
    const session = await api.get('/session');
    setCsrfToken(session.csrfToken);
    setCurrencySymbol(session.store?.currencySymbol);
    setUser(session.user);
    setIsAdmin(session.isAdmin);
    setCartCount(session.cartCount);
    setStore({ ...DEFAULT_STORE, ...session.store });
    return session;
  }, []);

  useEffect(() => {
    // The session call must succeed before any write, since it carries the
    // CSRF token. Categories are chrome and can fail quietly.
    (async () => {
      try {
        await loadSession();
      } catch (err) {
        console.error('Could not start a session:', err);
      }
      try {
        setCategories(await api.get('/categories'));
      } catch {
        setCategories([]);
      }
      setReady(true);
    })();
  }, [loadSession]);

  const notify = useCallback((message, tone = 'success') => {
    setToast({ message, tone, id: Date.now() });
  }, []);

  const dismissToast = useCallback(() => setToast(null), []);

  const value = useMemo(
    () => ({
      user,
      setUser,
      isAdmin,
      setIsAdmin,
      cartCount,
      setCartCount,
      store,
      categories,
      ready,
      toast,
      notify,
      dismissToast,
      loadSession,
    }),
    [user, isAdmin, cartCount, store, categories, ready, toast, notify, dismissToast, loadSession]
  );

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
}
