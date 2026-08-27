/**
 * Thin fetch wrapper for the JSON API.
 *
 * The CSRF token is captured from GET /api/session and replayed as a header on
 * every write, mirroring what the server expects.
 */

let csrfToken = null;

export const setCsrfToken = (token) => {
  csrfToken = token;
};

export class ApiError extends Error {
  constructor(status, message, details) {
    super(message);
    this.status = status;
    this.details = details || {};
  }
}

async function request(method, path, body, { isFormData = false } = {}) {
  const headers = {};
  if (csrfToken) headers['x-csrf-token'] = csrfToken;
  if (body !== undefined && !isFormData) headers['Content-Type'] = 'application/json';

  const res = await fetch(`/api${path}`, {
    method,
    headers,
    credentials: 'same-origin',
    body: body === undefined ? undefined : isFormData ? body : JSON.stringify(body),
  });

  if (res.status === 204) return null;

  let payload;
  try {
    payload = await res.json();
  } catch {
    throw new ApiError(res.status, 'The server sent an unexpected response.');
  }

  if (!res.ok) {
    throw new ApiError(res.status, payload?.error || 'Something went wrong.', payload?.details);
  }
  return payload;
}

export const api = {
  get: (path) => request('GET', path),
  post: (path, body) => request('POST', path, body ?? {}),
  patch: (path, body) => request('PATCH', path, body ?? {}),
  delete: (path, body) => request('DELETE', path, body ?? {}),
  upload: (path, formData) => request('POST', path, formData, { isFormData: true }),
};
