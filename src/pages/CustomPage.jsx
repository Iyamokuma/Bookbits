import { useNavigate, useParams } from 'react-router-dom';
import { Spinner, EmptyState } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { usePageMeta } from '../lib/usePageMeta';

/** A page written in Admin → Pages, served at /pages/<slug>. */
export default function CustomPage() {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { data, loading, error } = useFetch(`/pages/${encodeURIComponent(slug)}`);

  // Links to other pages of the store, written in the editor as plain <a>
  // tags, should navigate inside the app rather than reload it.
  const followInternalLinks = (e) => {
    const link = e.target.closest('a[href]');
    const href = link?.getAttribute('href') || '';
    if (!link || link.target || !href.startsWith('/') || href.startsWith('//')) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
    e.preventDefault();
    navigate(href);
  };

  usePageMeta({
    title: data?.title,
    description: data?.metaDescription,
    image: data?.coverUrl,
  });

  if (loading) return <Spinner />;
  if (error) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-16">
        <EmptyState title="Page not found" message={error.message} actionLabel="Back to the shop" actionTo="/" />
      </div>
    );
  }

  return (
    <article className="mx-auto max-w-3xl px-4 py-14">
      <h1 className="font-display text-3xl leading-tight text-slate-900 sm:text-4xl">{data.title}</h1>

      {data.coverUrl && (
        <img src={data.coverUrl} alt="" className="mt-8 max-h-[28rem] w-full rounded-2xl object-cover" />
      )}

      {/* Sanitised on the server when the page was saved (server/lib/sanitize.js). */}
      {/* Pressing Enter on a focused link also fires click, so keyboards work too. */}
      <div
        className="rich-content mt-8"
        onClick={followInternalLinks}
        dangerouslySetInnerHTML={{ __html: data.body }}
      />
    </article>
  );
}
