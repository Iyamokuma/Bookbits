import { Spinner, Alert } from '../components/ui';
import { useFetch } from '../lib/useFetch';

export default function Legal({ slug }) {
  const { data, loading, error } = useFetch(`/legal/${slug}`);

  if (loading) return <Spinner />;
  if (error) return <div className="mx-auto max-w-3xl px-4 py-16"><Alert>{error.message}</Alert></div>;

  return (
    <div className="mx-auto max-w-3xl px-4 py-14">
      {data.eyebrow && (
        <p className="text-xs font-semibold uppercase tracking-wide text-brand-600">{data.eyebrow}</p>
      )}
      <h1 className="mt-2 font-display text-3xl text-slate-900">{data.title}</h1>
      {data.intro && (
        <p className="mt-4 text-sm leading-relaxed text-slate-600">{data.intro}</p>
      )}

      <div className="mt-10 space-y-9">
        {data.sections.map((section, i) => (
          <section key={i}>
            {section.heading && (
              <h2 className="text-base font-semibold text-slate-900">{section.heading}</h2>
            )}

            {section.paragraphs?.map((p, j) => (
              <p key={j} className="mt-2.5 text-sm leading-relaxed text-slate-600">{p}</p>
            ))}

            {section.list && (
              <ul className="mt-3 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-slate-600">
                {section.list.map((item, j) => <li key={j}>{item}</li>)}
              </ul>
            )}

            {section.subsections?.map((sub, j) => (
              <div key={j} className="mt-5">
                <h3 className="text-sm font-semibold text-slate-800">{sub.heading}</h3>
                {sub.paragraphs?.map((p, k) => (
                  <p key={k} className="mt-2 text-sm leading-relaxed text-slate-600">{p}</p>
                ))}
                {sub.list && (
                  <ul className="mt-2 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-slate-600">
                    {sub.list.map((item, k) => <li key={k}>{item}</li>)}
                  </ul>
                )}
              </div>
            ))}
          </section>
        ))}
      </div>

      {data.closing && (
        <p className="mt-12 border-t border-slate-200 pt-6 text-sm italic text-slate-500">
          {data.closing}
        </p>
      )}
    </div>
  );
}
