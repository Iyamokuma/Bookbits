import { useEffect, useState } from 'react';
import { Pagination } from './ui';

/** Six rows on a four-column grid (see server/lib/storefront.js). */
export const BOOKS_PER_PAGE = 24;

/**
 * Client-side paging for book card grids (homepage sections, wishlist).
 * Shop and stationery use server pagination instead.
 */
export default function PaginatedBookGrid({ books, className, children }) {
  const [page, setPage] = useState(1);
  const total = books.length;
  const pages = Math.max(1, Math.ceil(total / BOOKS_PER_PAGE));

  useEffect(() => {
    setPage(1);
  }, [books]);

  useEffect(() => {
    if (page > pages) setPage(pages);
  }, [page, pages]);

  const slice = books.slice((page - 1) * BOOKS_PER_PAGE, page * BOOKS_PER_PAGE);

  return (
    <>
      <div className={className}>{children(slice)}</div>
      <Pagination
        pagination={{ page, pages, total, perPage: BOOKS_PER_PAGE }}
        onPage={(n) => {
          setPage(n);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }}
      />
    </>
  );
}
