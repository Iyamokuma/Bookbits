import { config } from '../config.js';

export function formatMoney(amount) {
  const n = Number(amount || 0);
  return (
    config.store.currencySymbol +
    n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  );
}

export const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
