import crypto from 'node:crypto';
import { config, absoluteUrl } from '../config.js';

export const GATEWAYS = ['paystack', 'klump', 'korapayment'];

export const gatewayLabels = {
  paystack: 'Paystack',
  klump: 'Klump',
  korapayment: 'Korapay',
};

const REF_PREFIX = { paystack: 'PST', klump: 'KLP', korapayment: 'KRP' };

export function paymentReference(gateway, orderId) {
  const prefix = REF_PREFIX[gateway] || 'PAY';
  return `${prefix}-${orderId}-${crypto.randomBytes(4).toString('hex').toUpperCase()}`;
}

async function httpJson(method, url, headers = {}, payload = null) {
  try {
    const res = await fetch(url, {
      method: method.toUpperCase(),
      headers: { ...headers, ...(payload ? { 'Content-Type': 'application/json' } : {}) },
      body: payload ? JSON.stringify(payload) : undefined,
      signal: AbortSignal.timeout(40_000),
    });
    let body;
    try {
      body = await res.json();
    } catch {
      body = {};
    }
    return { ok: res.ok, statusCode: res.status, body, error: null };
  } catch (err) {
    return { ok: false, statusCode: 0, body: null, error: err.message || 'Gateway request failed.' };
  }
}

// --------------------------------------------------------------------------
// Initialisation
// --------------------------------------------------------------------------

export async function initiatePayment(gateway, order, user, reference) {
  const amount = Number(order.total || 0);
  const callback = absoluteUrl(
    `/api/payment/callback?gateway=${encodeURIComponent(gateway)}&order=${order.id}`
  );

  if (gateway === 'paystack') {
    if (!config.paystack.secretKey) {
      return { ok: false, redirectUrl: null, error: 'Paystack keys are not configured.' };
    }
    const r = await httpJson(
      'POST',
      'https://api.paystack.co/transaction/initialize',
      { Authorization: `Bearer ${config.paystack.secretKey}` },
      {
        email: user.email,
        amount: Math.round(amount * 100), // Paystack charges in kobo
        currency: config.store.currencyCode,
        reference,
        callback_url: callback,
        metadata: { order_id: order.id, user_id: user.id },
      }
    );
    const url = r.body?.data?.authorization_url;
    if (!r.ok || !url) {
      return {
        ok: false,
        redirectUrl: null,
        error: 'Could not initialize Paystack transaction.',
        response: r.body,
      };
    }
    return { ok: true, redirectUrl: url, error: null, response: r.body };
  }

  if (gateway === 'korapayment') {
    if (!config.korapay.secretKey) {
      return { ok: false, redirectUrl: null, error: 'Korapay keys are not configured.' };
    }
    const r = await httpJson(
      'POST',
      'https://api.korapay.com/merchant/api/v1/charges/initialize',
      { Authorization: `Bearer ${config.korapay.secretKey}` },
      {
        amount: Math.round(amount),
        currency: config.store.currencyCode,
        reference,
        redirect_url: callback,
        notification_url: absoluteUrl('/api/payment/webhook?gateway=korapayment'),
        customer: { name: user.name, email: user.email },
      }
    );
    const url = r.body?.data?.checkout_url;
    if (!r.ok || !url) {
      return {
        ok: false,
        redirectUrl: null,
        error: 'Could not initialize Korapay transaction.',
        response: r.body,
      };
    }
    return { ok: true, redirectUrl: url, error: null, response: r.body };
  }

  if (gateway === 'klump') {
    return {
      ok: true,
      redirectUrl: `/payment/klump?order=${order.id}&reference=${encodeURIComponent(reference)}`,
      error: null,
      response: {},
    };
  }

  return { ok: false, redirectUrl: null, error: 'Unsupported payment gateway selected.' };
}

// --------------------------------------------------------------------------
// Verification — always server-to-server, never trust the browser redirect
// --------------------------------------------------------------------------

export async function verifyPayment(gateway, reference) {
  if (gateway === 'paystack') {
    if (!config.paystack.secretKey) {
      return { ok: false, paid: false, transactionRef: reference, response: {}, error: 'Paystack key missing' };
    }
    const r = await httpJson(
      'GET',
      `https://api.paystack.co/transaction/verify/${encodeURIComponent(reference)}`,
      { Authorization: `Bearer ${config.paystack.secretKey}` }
    );
    const status = String(r.body?.data?.status || '').toLowerCase();
    return {
      ok: r.ok,
      paid: r.ok && status === 'success',
      amount: r.body?.data?.amount != null ? Number(r.body.data.amount) / 100 : null,
      transactionRef: r.body?.data?.reference || reference,
      response: r.body || {},
      error: r.ok ? null : 'Paystack verification failed',
    };
  }

  if (gateway === 'korapayment') {
    if (!config.korapay.secretKey) {
      return { ok: false, paid: false, transactionRef: reference, response: {}, error: 'Korapay key missing' };
    }
    const r = await httpJson(
      'GET',
      `https://api.korapay.com/merchant/api/v1/charges/${encodeURIComponent(reference)}`,
      { Authorization: `Bearer ${config.korapay.secretKey}` }
    );
    const status = String(r.body?.data?.status || '').toLowerCase();
    return {
      ok: r.ok,
      paid: r.ok && ['success', 'successful'].includes(status),
      amount: r.body?.data?.amount != null ? Number(r.body.data.amount) : null,
      transactionRef: r.body?.data?.reference || reference,
      response: r.body || {},
      error: r.ok ? null : 'Korapay verification failed',
    };
  }

  if (gateway === 'klump') {
    if (!config.klump.secretKey) {
      return { ok: false, paid: false, transactionRef: reference, response: {}, error: 'Klump key missing' };
    }
    const r = await httpJson(
      'GET',
      `https://api.useklump.com/v1/transactions/${encodeURIComponent(reference)}`,
      { Authorization: `Bearer ${config.klump.secretKey}` }
    );
    const status = String(r.body?.data?.status || '').toLowerCase();
    return {
      ok: r.ok,
      paid: r.ok && ['successful', 'success', 'completed'].includes(status),
      amount: r.body?.data?.amount != null ? Number(r.body.data.amount) : null,
      transactionRef: reference,
      response: r.body || {},
      error: r.ok ? null : 'Klump verification failed',
    };
  }

  return { ok: false, paid: false, transactionRef: reference, response: {}, error: 'Unknown gateway' };
}

// --------------------------------------------------------------------------
// Webhook signature verification
//
// The PHP app accepted webhook bodies unauthenticated, which let anyone mark an
// order paid by POSTing to the endpoint. These checks close that hole.
// --------------------------------------------------------------------------

export function verifyWebhookSignature(gateway, rawBody, headers) {
  if (gateway === 'paystack') {
    const signature = headers['x-paystack-signature'];
    if (!signature || !config.paystack.secretKey) return false;
    const expected = crypto
      .createHmac('sha512', config.paystack.secretKey)
      .update(rawBody)
      .digest('hex');
    return timingSafeEqual(expected, signature);
  }

  if (gateway === 'korapayment') {
    const signature = headers['x-korapay-signature'];
    if (!signature || !config.korapay.secretKey) return false;
    // Korapay signs only the `data` object of the payload, not the whole body.
    let data;
    try {
      data = JSON.parse(rawBody.toString()).data;
    } catch {
      return false;
    }
    const expected = crypto
      .createHmac('sha256', config.korapay.secretKey)
      .update(JSON.stringify(data))
      .digest('hex');
    return timingSafeEqual(expected, signature);
  }

  return false;
}

function timingSafeEqual(a, b) {
  const bufA = Buffer.from(String(a));
  const bufB = Buffer.from(String(b));
  if (bufA.length !== bufB.length) return false;
  return crypto.timingSafeEqual(bufA, bufB);
}
