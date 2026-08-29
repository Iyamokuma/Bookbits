let currencySymbol = '\u20a6';

export const setCurrencySymbol = (symbol) => {
  if (symbol) currencySymbol = symbol;
};

export function formatMoney(amount) {
  return (
    currencySymbol +
    Number(amount || 0).toLocaleString('en-NG', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

export const formatDate = (value, opts) =>
  new Date(value).toLocaleDateString('en-NG', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    ...opts,
  });

export const formatDateTime = (value) =>
  new Date(value).toLocaleString('en-NG', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
