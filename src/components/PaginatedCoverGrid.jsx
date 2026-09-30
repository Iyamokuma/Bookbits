import { Link } from 'react-router-dom';
import PaginatedBookGrid from './PaginatedBookGrid';

export default function PaginatedCoverGrid({ books, badge, badgeClass, hoverClass }) {
  return (
    <PaginatedBookGrid
      books={books}
      className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:gap-5"
    >
      {(slice) =>
        slice.map((book) => (
          <CoverTile
            key={book.id}
            book={book}
            badge={badge}
            badgeClass={badgeClass}
            hoverClass={hoverClass}
          />
        ))
      }
    </PaginatedBookGrid>
  );
}

function CoverTile({ book, badge, badgeClass, hoverClass }) {
  return (
    <Link to={`/book/${book.id}`} className="group block">
      <div className="relative aspect-[3/4] overflow-hidden rounded-lg bg-slate-100 shadow-sm ring-1 ring-slate-900/5 transition group-hover:-translate-y-1 group-hover:shadow-md">
        <img
          src={book.coverUrl}
          alt={book.title}
          loading="lazy"
          className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
        />
        <div
          className="absolute inset-y-0 left-0 w-1 bg-gradient-to-r from-black/20 to-transparent"
          aria-hidden="true"
        />
        <span
          className={`absolute left-2 top-2 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase text-white shadow ${badgeClass}`}
        >
          {badge}
        </span>
      </div>
      <div className="mt-2 px-0.5">
        <p
          className={`line-clamp-1 text-xs font-bold text-slate-800 transition sm:text-sm ${hoverClass}`}
        >
          {book.title}
        </p>
        <p className="mt-0.5 text-[11px] text-slate-500 sm:text-xs">{book.author}</p>
      </div>
    </Link>
  );
}
