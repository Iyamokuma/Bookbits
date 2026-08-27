import { Router } from 'express';
import { verifyWebhookSignature, verifyPayment, GATEWAYS } from '../lib/payments.js';
import { getOrder } from '../lib/orders.js';
import { fulfilOrder } from './payments.js';

const router = Router();

/**
 * Gateway server-to-server notification. This is the authoritative fulfilment
 * path — the browser redirect can be abandoned, but the webhook always arrives.
 *
 * Mounted before the body parsers so `req.body` is the raw Buffer the HMAC was
 * computed over.
 */
router.post('/', async (req, res) => {
  const gateway = String(req.query.gateway || '');
  if (!GATEWAYS.includes(gateway)) return res.sendStatus(400);

  const rawBody = Buffer.isBuffer(req.body) ? req.body : Buffer.from('');
  if (!verifyWebhookSignature(gateway, rawBody, req.headers)) {
    console.warn(`[webhook] rejected ${gateway} call with a bad or missing signature`);
    return res.sendStatus(401);
  }

  // Acknowledge immediately; gateways retry on slow responses and we've already
  // authenticated the payload.
  res.sendStatus(200);

  try {
    const payload = JSON.parse(rawBody.toString());
    const data = payload.data || {};
    const reference = data.reference;
    const orderId = parseInt(data.metadata?.order_id ?? extractOrderId(reference), 10);
    if (!reference || !orderId) return;

    const order = await getOrder(orderId);
    if (!order) return;

    // Never trust amounts or status from the webhook body — re-verify with the
    // gateway's own API before releasing stock.
    const verification = await verifyPayment(gateway, reference);
    if (!verification.paid) return;

    await fulfilOrder({ order, gateway, verification, session: null });
  } catch (err) {
    console.error('[webhook] processing failed:', err);
  }
});

/** References are formatted "<PREFIX>-<orderId>-<random>". */
function extractOrderId(reference) {
  return String(reference || '').split('-')[1] || 0;
}

export default router;
