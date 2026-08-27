import { Link, useParams } from 'react-router-dom';
import { Spinner, EmptyState } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { formatDate } from '../lib/format';

export default function BlogPost() {
  const { slug } = useParams();
  const { data, loading, error } = useFetch(`/blog/${slug}`);

  if (loading) return <Spinner />;
  if (error) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-16">
        <EmptyState title="Article not found" message={error.message} actionLabel="All articles" actionTo="/blog" />
      </div>
    );
  }

  return (
    <article className="mx-auto max-w-3xl px-4 py-14">
      <Link to="/blog" className="text-sm font-medium text-brand-700 hover:underline">
        ← All articles
      </Link>

      <h1 className="mt-6 font-display text-3xl leading-tight text-slate-900">{data.title}</h1>
      <p className="mt-2 text-xs text-slate-400">{formatDate(data.published_at)}</p>

      {data.cover_image && (
        <img src={data.cover_image} alt="" className="mt-8 w-full rounded-2xl object-cover" />
      )}

      <div className="mt-8 whitespace-pre-line text-[15px] leading-relaxed text-slate-700">
        {data.body}
      </div>
    </article>
  );
}
