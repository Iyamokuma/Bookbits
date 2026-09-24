import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import { initMetaPixel, trackMeta } from '../lib/metaPixel';

/**
 * Loads the Meta Pixel once we know the ID from /api/session, then sends
 * PageView on each client-side route change.
 */
export default function MetaPixel() {
  const { metaPixelId } = useApp();
  const { pathname } = useLocation();
  const firstPath = useRef(true);

  useEffect(() => {
    if (metaPixelId) initMetaPixel(metaPixelId);
  }, [metaPixelId]);

  useEffect(() => {
    if (!metaPixelId) return;
    // initMetaPixel already tracks the first PageView.
    if (firstPath.current) {
      firstPath.current = false;
      return;
    }
    trackMeta('PageView');
  }, [pathname, metaPixelId]);

  return null;
}
