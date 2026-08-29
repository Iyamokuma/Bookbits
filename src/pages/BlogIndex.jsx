import { Link } from 'react-router-dom';
import { Spinner, EmptyState, Alert } from '../components/ui';
import { useFetch } from '../lib/useFetch';
import { formatDate } from '../lib/format';

export default function BlogIndex() {
  const { data, loading, error } = useFetch('/blog');

  return (
    <div className="mx-auto max-w-4xl px-4 py-14">
      <h1 className="font-display text-3xl text-slate-900">From the blog</h1>
      <p className="mt-2 text-sm text-slate-500">Reading notes, recommendations and bookish thoughts.</p>

      <div className="mt-10">
        {loading ? (
          <Spinner />
        ) : error ? (
          <Alert>{error.message}</Alert>
        ) : data.length === 0 ? (
          <EmptyState
            title="No articles yet"
            message="We're working on our first posts. Check back soon."
            actionLabel="Browse books"
            actionTo="/shop"
          />
        ) : (
          <div className="space-y-5">
            {data.map((post) => (
              <Link
                key={post.id}
                to={`/blog/${post.slug}`}
                className="block rounded-2xl border border-slate-200 bg-white p-6 transition hover:shadow-md"
              >
                <p className="text-xs text-slate-400">{formatDate(post.published_at)}</p>
                <h2 className="mt-1.5 text-lg font-semibold text-slate-900">{post.title}</h2>
                {post.excerpt && (
                  <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-600">{post.excerpt}</p>
                )}
                <span className="mt-3 inline-block text-sm font-semibold text-brand-700">Read more →</span>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
