import { useCallback, useEffect, useState } from 'react';
import { api } from './api';

/**
 * Load JSON from the API with loading and error state.
 *
 * Responses are discarded if the path changes before they arrive, so a fast
 * sequence of navigations can't leave stale data on screen.
 */
export function useFetch(path, deps = []) {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);
  const [nonce, setNonce] = useState(0);

  const reload = useCallback(() => setNonce((n) => n + 1), []);

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError(null);

    api
      .get(path)
      .then((result) => active && setData(result))
      .catch((err) => active && setError(err))
      .finally(() => active && setLoading(false));

    return () => {
      active = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [path, nonce, ...deps]);

  return { data, error, loading, reload, setData };
}
