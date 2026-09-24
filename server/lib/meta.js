import crypto from 'node:crypto';
import { config, absoluteUrl } from '../config.js';

export const metaConversionsEnabled = () =>
  config.meta.enabled && Boolean(config.meta.pixelId && config.meta.accessToken);

const sha256 = (value) =>
  crypto.createHash('sha256').update(String(value)).digest('hex');

const hashEmail = (email) => {
  const normalized = String(email || '').trim().toLowerCase();
  return normalized ? sha256(normalized) : undefined;
};

/** Meta expects digits only, usually with country code (234 for Nigeria). */
const hashPhone = (phone) => {
  let digits = String(phone || '').replace(/\D/g, '');
  if (!digits) return undefined;
  if (digits.startsWith('234')) {
    // already international
  } else if (digits.startsWith('0')) {
    digits = `234${digits.slice(1)}`;
  } else if (digits.length === 10) {
    digits = `234${digits}`;
  }
  return sha256(digits);
};

export function parseCookies(header) {
  const out = {};
  if (!header) return out;
  for (const part of header.split(';')) {
    const trimmed = part.trim();
    if (!trimmed) continue;
    const eq = trimmed.indexOf('=');
    if (eq === -1) continue;
    const key = trimmed.slice(0, eq);
    const val = trimmed.slice(eq + 1);
    try {
      out[key] = decodeURIComponent(val);
    } catch {
      out[key] = val;
    }
  }
  return out;
}

/** Browser cookies and request metadata for Conversions API matching. */
export function captureMetaAttribution(req) {
  const cookies = parseCookies(req.headers?.cookie);
  return {
    fbp: cookies._fbp || undefined,
    fbc: cookies._fbc || undefined,
    clientIpAddress: req.ip || undefined,
    clientUserAgent: req.headers?.['user-agent'] || undefined,
  };
}

export function purchaseEventId(orderId) {
  return `purchase_${orderId}`;
}

async function sendMetaEvent({ eventName, eventId, userData, customData, eventSourceUrl }) {
  if (!metaConversionsEnabled()) return { ok: false, skipped: true };

  const body = {
    data: [
      {
        event_name: eventName,
        event_time: Math.floor(Date.now() / 1000),
        event_id: eventId,
        action_source: 'website',
        event_source_url: eventSourceUrl,
        user_data: Object.fromEntries(
          Object.entries(userData).filter(([, v]) => v !== undefined && v !== '')
        ),
        custom_data: customData,
      },
    ],
    access_token: config.meta.accessToken,
  };

  const res = await fetch(
    `https://graph.facebook.com/v21.0/${config.meta.pixelId}/events`,
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }
  );

  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    console.error('[meta] Conversions API error:', res.status, json);
    return { ok: false, error: json };
  }
  return { ok: true, response: json };
}

/**
 * Server-side Purchase — fires once when payment is confirmed (callback or webhook).
 * Uses the same event_id as the browser pixel for deduplication.
 */
export async function trackPurchase({ order, user, items, attribution }) {
  const eventId = purchaseEventId(order.id);
  const contentIds = items
    .map((i) => (i.book_id != null ? String(i.book_id) : null))
    .filter(Boolean);

  return sendMetaEvent({
    eventName: 'Purchase',
    eventId,
    eventSourceUrl: absoluteUrl(`/payment/result?status=success&order=${order.id}`),
    userData: {
      em: hashEmail(user?.email),
      ph: hashPhone(order.shipping_phone),
      client_ip_address: attribution?.clientIpAddress,
      client_user_agent: attribution?.clientUserAgent,
      fbp: attribution?.fbp,
      fbc: attribution?.fbc,
    },
    customData: {
      currency: config.store.currencyCode,
      value: Number(order.total),
      content_type: 'product',
      content_ids: contentIds.length ? contentIds : undefined,
      num_items: items.reduce((sum, i) => sum + Number(i.qty || 0), 0),
      order_id: String(order.id),
    },
  });
}
