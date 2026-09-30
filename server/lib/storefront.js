/** Product grids use up to 4 columns; six rows per page before showing Next. */
export const STORE_BOOKS_PER_PAGE = 24;

export function pageParam(raw, maxPages = 1_000_000) {
  const n = parseInt(String(raw || '1'), 10);
  if (!Number.isFinite(n) || n < 1) return 1;
  return Math.min(n, maxPages);
}

export function paginationMeta(page, total, perPage = STORE_BOOKS_PER_PAGE) {
  const pages = Math.max(1, Math.ceil(total / perPage));
  const safePage = Math.min(Math.max(1, page), pages);
  return {
    page: safePage,
    perPage,
    total,
    pages,
  };
}
