import { query, queryOne, transaction } from '../db.js';
import { bookCoverPrice, subtotal as cartSubtotal } from './cart.js';
import { round2 } from './money.js';

export const ORDER_STATUSES = [
  'pending',
  'processing',
  'shipped',
  'delivered',
  'cancelled',
  'refunded',
];

/**
 * Create a pending order from the current cart lines, snapshotting title,
 * author and unit price so later catalogue edits don't rewrite order history.
 */
export async function createOrder({ userId, lines, shipping, shippingFee = 0, notes = null }) {
  const sub = cartSubtotal(lines);
  const total = round2(sub + Number(shippingFee));

  return transaction(async (client) => {
    const { rows } = await client.query(
      `INSERT INTO orders (
         user_id, status, subtotal, discount, shipping_fee, total,
         shipping_name, shipping_phone, shipping_address, shipping_city,
         shipping_state, shipping_zip, shipping_country, notes
       ) VALUES ($1,'pending',$2,0,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12)
       RETURNING *`,
      [
        userId,
        sub,
        shippingFee,
        total,
        shipping.name,
        shipping.phone,
        shipping.address,
        shipping.city,
        shipping.state,
        shipping.zip,
        shipping.country,
        notes,
      ]
    );
    const order = rows[0];

    for (const line of lines) {
      const unitPrice = bookCoverPrice(line.book, line.cover_type);
      await client.query(
        `INSERT INTO order_items (order_id, book_id, title, author, cover_type, qty, unit_price, subtotal)
         VALUES ($1,$2,$3,$4,$5,$6,$7,$8)`,
        [
          order.id,
          line.book.id,
          line.book.title,
          line.book.author,
          line.cover_type,
          line.qty,
          unitPrice,
          round2(unitPrice * line.qty),
        ]
      );
    }

    return order;
  });
}

export async function getOrder(orderId) {
  return queryOne('SELECT * FROM orders WHERE id = $1', [orderId]);
}

export async function getOrderItems(orderId) {
  return query('SELECT * FROM order_items WHERE order_id = $1 ORDER BY id', [orderId]);
}

export async function getOrderForUser(orderId, userId) {
  return queryOne('SELECT * FROM orders WHERE id = $1 AND user_id = $2', [orderId, userId]);
}

/**
 * Record a successful payment exactly once, decrementing stock and advancing
 * the order to `processing`. Re-entrant: gateways may deliver both a browser
 * callback and a webhook for the same reference, and a second call is a no-op.
 *
 * @returns {Promise<{alreadyHandled: boolean}>}
 */
export async function markOrderPaid({ orderId, method, amount, transactionRef, gatewayResponse }) {
  return transaction(async (client) => {
    // Lock the order row so concurrent callback + webhook can't both fulfil.
    const { rows: orderRows } = await client.query(
      'SELECT * FROM orders WHERE id = $1 FOR UPDATE',
      [orderId]
    );
    const order = orderRows[0];
    if (!order) throw new Error(`Order ${orderId} not found`);

    if (order.payment_confirmed_at) {
      return { alreadyHandled: true, order };
    }

    await client.query(
      `INSERT INTO payments (order_id, method, status, amount, currency, transaction_ref, gateway_response, paid_at)
       VALUES ($1,$2,'completed',$3,$4,$5,$6,now())
       ON CONFLICT (transaction_ref) DO UPDATE
         SET status = 'completed', paid_at = now(), gateway_response = EXCLUDED.gateway_response`,
      [orderId, method, amount, 'NGN', transactionRef, gatewayResponse ?? null]
    );

    const { rows: items } = await client.query(
      'SELECT book_id, qty FROM order_items WHERE order_id = $1',
      [orderId]
    );
    for (const item of items) {
      if (!item.book_id) continue;
      await client.query(
        'UPDATE books SET stock_qty = GREATEST(0, stock_qty - $1) WHERE id = $2',
        [item.qty, item.book_id]
      );
    }

    const { rows: updated } = await client.query(
      `UPDATE orders SET status = 'processing', payment_confirmed_at = now()
       WHERE id = $1 RETURNING *`,
      [orderId]
    );

    return { alreadyHandled: false, order: updated[0] };
  });
}

export async function recordFailedPayment({ orderId, method, amount, transactionRef, gatewayResponse }) {
  await query(
    `INSERT INTO payments (order_id, method, status, amount, currency, transaction_ref, gateway_response)
     VALUES ($1,$2,'failed',$3,'NGN',$4,$5)
     ON CONFLICT (transaction_ref) DO UPDATE
       SET status = 'failed', gateway_response = EXCLUDED.gateway_response`,
    [orderId, method, amount, transactionRef, gatewayResponse ?? null]
  );
}

export async function updateOrderStatus(orderId, status, trackingNumber = undefined) {
  if (!ORDER_STATUSES.includes(status)) throw new Error(`Invalid order status: ${status}`);
  if (trackingNumber !== undefined) {
    return queryOne(
      'UPDATE orders SET status = $1, tracking_number = NULLIF($2, \'\') WHERE id = $3 RETURNING *',
      [status, trackingNumber, orderId]
    );
  }
  return queryOne('UPDATE orders SET status = $1 WHERE id = $2 RETURNING *', [status, orderId]);
}

/** Paginated order list for the admin dashboard. */
export async function listOrders({ page = 1, perPage = 5, status = null } = {}) {
  const offset = (page - 1) * perPage;
  const params = [];
  let where = '';
  if (status) {
    params.push(status);
    where = ` WHERE o.status = $${params.length}`;
  }

  const total = await queryOne(`SELECT COUNT(*)::int AS n FROM orders o${where}`, params);
  params.push(perPage, offset);

  const rows = await query(
    `SELECT o.*, u.name AS user_name, u.email AS user_email,
            p.status AS payment_status, p.method AS payment_method, p.transaction_ref
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     LEFT JOIN LATERAL (
       SELECT status, method, transaction_ref FROM payments
       WHERE order_id = o.id ORDER BY id DESC LIMIT 1
     ) p ON TRUE
     ${where}
     ORDER BY o.created_at DESC
     LIMIT $${params.length - 1} OFFSET $${params.length}`,
    params
  );

  return { rows, total: total.n, pages: Math.max(1, Math.ceil(total.n / perPage)), page, perPage };
}
