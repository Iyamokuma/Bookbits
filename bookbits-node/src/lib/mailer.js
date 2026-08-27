import { Resend } from 'resend';
import { config, absoluteUrl } from '../config.js';
import { formatMoney } from './money.js';

const resend = config.mail.resendApiKey ? new Resend(config.mail.resendApiKey) : null;

const esc = (s) =>
  String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

/**
 * Send one transactional email.
 * @returns {Promise<{ok: boolean, error: string|null}>}
 */
export async function sendEmail({ to, subject, html, replyTo }) {
  if (!resend) {
    console.error('[mail] RESEND_API_KEY is not set — skipping email to', to);
    return { ok: false, error: 'Email is not configured (missing RESEND_API_KEY).' };
  }

  try {
    const { error } = await resend.emails.send({
      from: `${config.mail.fromName} <${config.mail.fromEmail}>`,
      to: Array.isArray(to) ? to : [to],
      subject,
      html: wrap(subject, html),
      ...(replyTo ? { reply_to: replyTo } : {}),
    });

    if (error) {
      console.error('[mail] Resend rejected the message:', error);
      return { ok: false, error: friendlyResendError(error) };
    }
    return { ok: true, error: null };
  } catch (err) {
    console.error('[mail] Network error talking to Resend:', err);
    return { ok: false, error: 'Could not reach the email service. Please try again.' };
  }
}

/**
 * Resend's most common rejection in a fresh account is the unverified-domain
 * sandbox, whose raw message doesn't explain the fix.
 */
function friendlyResendError(error) {
  const msg = error?.message || 'Unknown email error.';
  if (config.mail.fromEmail.endsWith('@resend.dev')) {
    return (
      'Resend test mode: with an @resend.dev sender you can only email the address ' +
      'registered on your Resend account. Verify your domain at resend.com/domains, then ' +
      'set MAIL_FROM_EMAIL to an address on that domain.'
    );
  }
  return msg;
}

// --------------------------------------------------------------------------
// Shared HTML shell
// --------------------------------------------------------------------------

function wrap(title, body) {
  return `<!doctype html>
<html><body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(16,24,40,.06);">
        <tr><td style="background:#0b3d91;padding:22px 28px;">
          <div style="color:#fff;font-size:19px;font-weight:700;letter-spacing:.2px;">${esc(config.store.name)}</div>
        </td></tr>
        <tr><td style="padding:28px;color:#1f2937;font-size:15px;line-height:1.65;">
          ${body}
        </td></tr>
        <tr><td style="padding:18px 28px;background:#f9fafb;border-top:1px solid #eceff3;color:#6b7280;font-size:12px;line-height:1.6;">
          ${esc(config.store.name)} &middot; ${esc(config.store.phone)}<br>
          <a href="${absoluteUrl('/')}" style="color:#0b3d91;text-decoration:none;">${esc(config.baseUrl)}</a>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>`;
}

const button = (href, label) =>
  `<p style="margin:26px 0;"><a href="${href}" style="background:#0b3d91;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;display:inline-block;font-weight:600;">${esc(label)}</a></p>`;

function orderItemsTable(items) {
  const rows = items
    .map(
      (i) => `<tr>
        <td style="padding:8px 0;border-bottom:1px solid #eceff3;">
          ${esc(i.title)}${i.cover_type ? ` <span style="color:#6b7280;">(${esc(i.cover_type)})</span>` : ''}
          <div style="color:#6b7280;font-size:13px;">by ${esc(i.author)} &times; ${i.qty}</div>
        </td>
        <td style="padding:8px 0;border-bottom:1px solid #eceff3;text-align:right;white-space:nowrap;">${esc(formatMoney(i.subtotal))}</td>
      </tr>`
    )
    .join('');
  return `<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;font-size:14px;">${rows}</table>`;
}

const addressBlock = (order) => `
  <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;margin:18px 0;font-size:14px;line-height:1.6;">
    <strong>Delivery address</strong><br>
    ${esc(order.shipping_name)}<br>
    ${esc(order.shipping_address)}<br>
    ${esc(order.shipping_city)}, ${esc(order.shipping_state)} ${esc(order.shipping_zip)}<br>
    ${esc(order.shipping_country)}<br>
    ${order.shipping_phone ? esc(order.shipping_phone) : ''}
  </div>`;

// --------------------------------------------------------------------------
// Templates
// --------------------------------------------------------------------------

export function sendVerificationEmail(user, token) {
  const link = absoluteUrl(`/verify-email?token=${encodeURIComponent(token)}`);
  return sendEmail({
    to: user.email,
    subject: 'Confirm your email address',
    html: `<p>Hi ${esc(user.name)},</p>
      <p>Welcome to ${esc(config.store.name)}. Please confirm your email address to activate your account.</p>
      ${button(link, 'Verify my email')}
      <p style="color:#6b7280;font-size:13px;">If the button doesn't work, paste this link into your browser:<br>
      <span style="word-break:break-all;">${esc(link)}</span></p>
      <p style="color:#6b7280;font-size:13px;">If you didn't create this account, you can ignore this email.</p>`,
  });
}

export function sendPasswordResetEmail(email, name, token) {
  const link = absoluteUrl(`/reset-password?token=${encodeURIComponent(token)}`);
  return sendEmail({
    to: email,
    subject: 'Reset your password',
    html: `<p>Hi ${esc(name || 'there')},</p>
      <p>We received a request to reset your password. This link expires in 1 hour.</p>
      ${button(link, 'Reset password')}
      <p style="color:#6b7280;font-size:13px;">If the button doesn't work, paste this link into your browser:<br>
      <span style="word-break:break-all;">${esc(link)}</span></p>
      <p style="color:#6b7280;font-size:13px;">If you didn't request this, no action is needed — your password stays the same.</p>`,
  });
}

export function sendOrderConfirmation(user, order, items) {
  return sendEmail({
    to: user.email,
    subject: `Order #${order.id} confirmed`,
    html: `<p>Hi ${esc(user.name)},</p>
      <p>Thank you — we've received your payment and your order is confirmed.</p>
      ${orderItemsTable(items)}
      <p style="font-size:16px;"><strong>Total paid: ${esc(formatMoney(order.total))}</strong></p>
      ${addressBlock(order)}
      <p>We'll email you again as soon as your order ships. Orders are typically processed within 1–3 business days.</p>
      ${button(absoluteUrl(`/orders/${order.id}`), 'View your order')}`,
  });
}

export function sendAdminOrderNotification(order, user, items) {
  return sendEmail({
    to: config.admin.email,
    replyTo: user.email,
    subject: `New paid order #${order.id} — ${formatMoney(order.total)}`,
    html: `<p><strong>${esc(user.name)}</strong> (${esc(user.email)}) just paid for order #${order.id}.</p>
      ${orderItemsTable(items)}
      <p style="font-size:16px;"><strong>Total: ${esc(formatMoney(order.total))}</strong></p>
      ${addressBlock(order)}
      ${button(absoluteUrl('/admin/payments'), 'Open in dashboard')}`,
  });
}

export function sendProcessingNotice(to, order, name) {
  return sendEmail({
    to,
    subject: `Order #${order.id} is being processed`,
    html: `<p>Hi ${esc(name)},</p>
      <p>Good news — order #${order.id} is now being processed and prepared for dispatch.</p>
      ${addressBlock(order)}
      <p>We'll let you know the moment it ships.</p>`,
  });
}

export function sendShippedNotice(to, order, name) {
  return sendEmail({
    to,
    subject: `Order #${order.id} has shipped`,
    html: `<p>Hi ${esc(name)},</p>
      <p>Your order #${order.id} is on its way. It will be delivered to the address below:</p>
      ${addressBlock(order)}
      ${order.tracking_number ? `<p><strong>Tracking number:</strong> ${esc(order.tracking_number)}</p>` : ''}
      <p>Estimated delivery is 1–3 business days in Lagos, 3–5 days for other major cities, and 5–10 days for remote areas.</p>
      <p>Please make sure someone is available to receive the parcel.</p>`,
  });
}

export function sendDeliveryNotice(to, order, name) {
  return sendEmail({
    to,
    subject: `Order #${order.id} delivered`,
    html: `<p>Hi ${esc(name)},</p>
      <p>Order #${order.id} has been marked as delivered. We hope you enjoy your books.</p>
      <p>If anything is wrong with your order, reply to this email within 48 hours and we'll make it right.</p>`,
  });
}

/** Free-form message composed by an admin on the order detail screen. */
export function sendOrderUpdateEmail(to, subject, message, order) {
  const body = esc(message).replace(/\n/g, '<br>');
  return sendEmail({
    to,
    subject,
    replyTo: config.store.email,
    html: `<p>${body}</p>
      ${order ? `<p style="color:#6b7280;font-size:13px;margin-top:24px;">Regarding order #${order.id}.</p>` : ''}`,
  });
}
