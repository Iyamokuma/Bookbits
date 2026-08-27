import { Router } from 'express';
import { query } from '../db.js';
import * as cart from '../lib/cart.js';
import { createOrder, getOrderForUser, getOrderItems, recordFailedPayment } from '../lib/orders.js';
import { initiatePayment, paymentReference, GATEWAYS, gatewayLabels } from '../lib/payments.js';
import { round2 } from '../lib/money.js';
import { asyncRoute, unauthorized, unprocessable, notFound, badRequest } from './helpers.js';

const router = Router();

const SHIPPING_FEE = 1500;

const requireUser = (req) => {
  if (!req.user) throw unauthorized();
  return req.user;
};

/**
 * Customers must supply a complete, deliverable address — couriers reject
 * partial ones and the shipped-confirmation email quotes this back verbatim.
 */
function validateShipping(body) {
  const clean = (v) => String(v ?? '').trim();
  const shipping = {
    name: clean(body.name),
    phone: clean(body.phone),
    address: clean(body.address),
    city: clean(body.city),
    state: clean(body.state),
    zip: clean(body.zip),
    country: clean(body.country) || 'Nigeria',
  };

  const errors = {};
  if (shipping.name.length < 3) {
    errors.name = 'Enter the full name of the person receiving the order.';
  }
  const digits = shipping.phone.replace(/\D/g, '');
  if (digits.length < 10 || digits.length > 15) {
    errors.phone = 'Enter a valid phone number (at least 10 digits).';
  }
  if (shipping.address.length < 10) {
    errors.address = 'Enter a complete street address, including house number and street name.';
  }
  if (shipping.city.length < 2) errors.city = 'Enter your city or town.';
  if (shipping.state.length < 2) errors.state = 'Enter your state.';
  if (shipping.zip.length < 3) errors.zip = 'Enter your postal code (use 000000 if you have none).';
  if (shipping.country.length < 2) errors.country = 'Enter your country.';

  return { shipping, errors };
}

const presentOrder = (o) => ({
  id: o.id,
  status: o.status,
  subtotal: Number(o.subtotal),
  shippingFee: Number(o.shipping_fee),
  total: Number(o.total),
  trackingNumber: o.tracking_number,
  paid: Boolean(o.payment_confirmed_at),
  createdAt: o.created_at,
  notes: o.notes,
  shipping: {
    name: o.shipping_name,
    phone: o.shipping_phone,
    address: o.shipping_address,
    city: o.shipping_city,
    state: o.shipping_state,
    zip: o.shipping_zip,
    country: o.shipping_country,
  },
});

router.get(
  '/checkout',
  asyncRoute(async (req, res) => {
    requireUser(req);
    const lines = await cart.linesWithBooks(req.session);
    const subtotal = cart.subtotal(lines);

    res.json({
      itemCount: cart.totalQty(req.session),
      subtotal,
      shippingFee: SHIPPING_FEE,
      total: round2(subtotal + SHIPPING_FEE),
      gateways: GATEWAYS.map((id) => ({ id, label: gatewayLabels[id] })),
    });
  })
);

router.post(
  '/checkout',
  asyncRoute(async (req, res) => {
    const user = requireUser(req);

    const lines = await cart.linesWithBooks(req.session);
    if (lines.length === 0) throw badRequest('Your cart is empty.');

    const { shipping, errors } = validateShipping(req.body.shipping || {});
    const gateway = String(req.body.gateway || 'paystack');
    if (!GATEWAYS.includes(gateway)) errors.gateway = 'Choose a payment method.';

    // Re-check stock at submit time; the cart may have sat idle for a while.
    for (const line of lines) {
      if (line.qty > line.book.stock_qty) {
        errors.cart = `"${line.book.title}" only has ${line.book.stock_qty} left in stock.`;
      }
    }
    if (Object.keys(errors).length) throw unprocessable('Please check your details.', errors);

    const order = await createOrder({
      userId: user.id,
      lines,
      shipping,
      shippingFee: SHIPPING_FEE,
      notes: String(req.body.notes || '').trim() || null,
    });

    const reference = paymentReference(gateway, order.id);
    const init = await initiatePayment(gateway, order, user, reference);

    if (!init.ok || !init.redirectUrl) {
      // The gateway's own wording is for us, not the customer.
      console.error('[checkout] payment init failed:', init.error, init.response);
      await recordFailedPayment({
        orderId: order.id,
        method: gateway,
        amount: order.total,
        transactionRef: reference,
        gatewayResponse: init.response ?? null,
      });
      return res.status(502).json({
        orderId: order.id,
        message:
          'We saved your order but could not start the payment. Please try again in a moment, ' +
          'choose a different payment method, or contact us and we will help you complete it.',
      });
    }

    req.session.pendingReference = reference;
    res.json({ orderId: order.id, redirectUrl: init.redirectUrl });
  })
);

router.get(
  '/orders',
  asyncRoute(async (req, res) => {
    const user = requireUser(req);
    const orders = await query(
      'SELECT * FROM orders WHERE user_id = $1 ORDER BY created_at DESC',
      [user.id]
    );
    res.json(orders.map(presentOrder));
  })
);

router.get(
  '/orders/:id',
  asyncRoute(async (req, res) => {
    const user = requireUser(req);
    const order = await getOrderForUser(parseInt(req.params.id, 10), user.id);
    if (!order) throw notFound("We couldn't find that order on your account.");

    const items = await getOrderItems(order.id);
    res.json({
      order: presentOrder(order),
      items: items.map((i) => ({
        id: i.id,
        title: i.title,
        author: i.author,
        coverType: i.cover_type,
        qty: i.qty,
        unitPrice: Number(i.unit_price),
        subtotal: Number(i.subtotal),
      })),
      gateways: GATEWAYS.map((id) => ({ id, label: gatewayLabels[id] })),
    });
  })
);

/** Retry payment on an order that was created but never paid for. */
router.post(
  '/orders/:id/pay',
  asyncRoute(async (req, res) => {
    const user = requireUser(req);
    const order = await getOrderForUser(parseInt(req.params.id, 10), user.id);
    if (!order) throw notFound('Order not found.');
    if (order.payment_confirmed_at) throw badRequest('This order has already been paid for.');

    const gateway = GATEWAYS.includes(req.body.gateway) ? req.body.gateway : 'paystack';
    const reference = paymentReference(gateway, order.id);
    const init = await initiatePayment(gateway, order, user, reference);

    if (!init.ok || !init.redirectUrl) {
      console.error('[orders] retry payment init failed:', init.error);
      return res.status(502).json({
        message:
          'We could not start the payment just now. Please try again shortly or contact us for help.',
      });
    }

    req.session.pendingReference = reference;
    res.json({ redirectUrl: init.redirectUrl });
  })
);

export default router;
