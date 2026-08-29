import { Router } from 'express';
import { verifyPayment, GATEWAYS } from '../lib/payments.js';
import { getOrder, getOrderItems, markOrderPaid, recordFailedPayment } from '../lib/orders.js';
import { sendOrderConfirmation, sendAdminOrderNotification } from '../lib/mailer.js';
import * as cart from '../lib/cart.js';
import { findUserById } from '../lib/auth.js';

const router = Router();

/**
 * Fulfil a verified payment: mark paid, empty the cart, notify both sides.
 * Safe to call more than once for the same order.
 */
export async function fulfilOrder({ order, gateway, verification, session }) {
  const { alreadyHandled, order: paidOrder } = await markOrderPaid({
    orderId: order.id,
    method: gateway,
    amount: verification.amount ?? order.total,
    transactionRef: verification.transactionRef,
    gatewayResponse: verification.response,
  });

  if (alreadyHandled) return paidOrder;

  const [user, items] = await Promise.all([
    findUserById(paidOrder.user_id),
    getOrderItems(paidOrder.id),
  ]);

  await cart.clearForUser(session ?? { cart: {} }, paidOrder.user_id);

  // Email failures must never roll back a successful payment.
  Promise.allSettled([
    sendOrderConfirmation(user, paidOrder, items),
    sendAdminOrderNotification(paidOrder, user, items),
  ]).then((results) => {
    for (const r of results) {
      if (r.status === 'rejected') console.error('[payment] notification failed:', r.reason);
    }
  });

  return paidOrder;
}

/**
 * Where the gateway sends the customer's browser after payment. This is a real
 * server redirect rather than an API call, then hands back to the SPA.
 */
router.get('/callback', async (req, res) => {
  const spaResult = (status, orderId) =>
    res.redirect(`/payment/result?status=${status}${orderId ? `&order=${orderId}` : ''}`);

  try {
    const gateway = String(req.query.gateway || '');
    const orderId = parseInt(req.query.order, 10);
    if (!GATEWAYS.includes(gateway) || !orderId) return spaResult('failed');

    const order = await getOrder(orderId);
    if (!order) return spaResult('failed');

    // Paystack echoes the reference back; others fall back to what we stored.
    const reference =
      String(req.query.reference || req.query.trxref || '') || req.session.pendingReference;
    if (!reference) return spaResult('failed', orderId);

    const verification = await verifyPayment(gateway, reference);
    if (!verification.paid) {
      await recordFailedPayment({
        orderId,
        method: gateway,
        amount: order.total,
        transactionRef: reference,
        gatewayResponse: verification.response,
      });
      return spaResult('failed', orderId);
    }

    await fulfilOrder({ order, gateway, verification, session: req.session });
    delete req.session.pendingReference;

    spaResult('success', orderId);
  } catch (err) {
    console.error('[payment/callback]', err);
    spaResult('failed');
  }
});

export default router;
