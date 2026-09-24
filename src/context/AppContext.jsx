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
  facebookUrl: '',
  instagramUrl: '',
  currencySymbol: '\u20a6',
  currencyCode: 'NGN',
};

export function AppProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isAdmin, setIsAdmin] = useState(false);
  const [cartCount, setCartCount] = useState(0);
  const [store, setStore] = useState(DEFAULT_STORE);
  const [categories, setCategories] = useState([]);
  const [googleClientId, setGoogleClientId] = useState('');
  const [metaPixelId, setMetaPixelId] = useState('');
  const [wishlist, setWishlist] = useState([]);
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
    setGoogleClientId(session.googleClientId || '');
    setMetaPixelId(session.metaPixelId || '');
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

  // Saved-book ids, so every card can show its state without its own request.
  // Signed-out visitors get an empty list rather than an error.
  useEffect(() => {
    if (!user) {
      setWishlist([]);
      return;
    }
    api.get('/wishlist/ids').then(setWishlist).catch(() => setWishlist([]));
  }, [user]);

  const notify = useCallback((message, tone = 'success') => {
    setToast({ message, tone, id: Date.now() });
  }, []);

  const dismissToast = useCallback(() => setToast(null), []);

  /** Save or unsave a book, updating the local list optimistically. */
  const toggleWishlist = useCallback(
    async (bookId) => {
      if (!user) {
        notify('Please sign in to save books.', 'info');
        return false;
      }
      const saved = wishlist.includes(bookId);
      setWishlist((ids) => (saved ? ids.filter((id) => id !== bookId) : [...ids, bookId]));
      try {
        const res = saved
          ? await api.delete(`/wishlist/${bookId}`)
          : await api.post('/wishlist', { bookId });
        notify(res.message);
        return !saved;
      } catch (err) {
        setWishlist((ids) => (saved ? [...ids, bookId] : ids.filter((id) => id !== bookId)));
        notify(err.message, 'error');
        return saved;
      }
    },
    [user, wishlist, notify]
  );

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
      googleClientId,
      metaPixelId,
      wishlist,
      toggleWishlist,
      ready,
      toast,
      notify,
      dismissToast,
      loadSession,
    }),
    [
      user, isAdmin, cartCount, store, categories, googleClientId, metaPixelId,
      wishlist, toggleWishlist, ready, toast, notify, dismissToast, loadSession,
    ]
  );

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
}
