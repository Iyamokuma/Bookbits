import { Router } from 'express';
import { config, storePhoneTel } from '../config.js';
import * as cart from '../lib/cart.js';
import { googleEnabled } from '../lib/auth.js';
import { asyncRoute, publicUser } from '../lib/http.js';

const router = Router();

/**
 * Bootstrap payload the SPA fetches on load: who you are, what's in your cart,
 * the CSRF token for subsequent writes, and store details for the chrome.
 */
router.get(
  '/session',
  asyncRoute(async (req, res) => {
    res.json({
      user: publicUser(req.user),
      isAdmin: req.session.isAdmin === true,
      cartCount: cart.totalQty(req.session),
      csrfToken: req.session.csrfToken,
      store: {
        ...config.store,
        phoneTel: storePhoneTel(),
      },
      // Empty while Google sign-in is suspended, which hides the button.
      googleClientId: googleEnabled() ? config.google.clientId : '',
      // Public numeric ID — required for the browser pixel script.
      metaPixelId:
        config.meta.enabled && config.meta.pixelId ? config.meta.pixelId : '',
    });
  })
);

export default router;
