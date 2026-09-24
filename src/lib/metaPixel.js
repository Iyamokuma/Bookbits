/** @typedef {{ currency?: string, value?: number, content_ids?: string[], content_type?: string, num_items?: number, contents?: object[] }} MetaParams */

let pixelIdLoaded = null;

export function purchaseEventId(orderId) {
  return `purchase_${orderId}`;
}

export function initMetaPixel(pixelId) {
  if (!pixelId || typeof window === 'undefined') return;
  if (pixelIdLoaded === pixelId && window.fbq) return;

  if (!window.fbq) {
    const n = (window.fbq = function fbq() {
      // eslint-disable-next-line prefer-rest-params
      n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
    });
    if (!window._fbq) window._fbq = n;
    n.push = n;
    n.loaded = true;
    n.version = '2.0';
    n.queue = [];
    const script = document.createElement('script');
    script.async = true;
    script.src = 'https://connect.facebook.net/en_US/fbevents.js';
    const first = document.getElementsByTagName('script')[0];
    first.parentNode.insertBefore(script, first);
  }

  window.fbq('init', pixelId);
  window.fbq('track', 'PageView');
  pixelIdLoaded = pixelId;
}

/** @param {string} event @param {MetaParams} [params] @param {string} [eventId] */
export function trackMeta(event, params = {}, eventId) {
  if (typeof window === 'undefined' || !window.fbq) return;
  if (eventId) {
    window.fbq('track', event, params, { eventID: eventId });
  } else {
    window.fbq('track', event, params);
  }
}
