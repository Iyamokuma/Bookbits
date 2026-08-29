/**
 * Wrap an async route handler so rejections reach the Express error handler
 * instead of becoming unhandled promise rejections.
 */
export const asyncRoute = (fn) => (req, res, next) => fn(req, res, next).catch(next);

/** Consistent shape for validation and business-rule failures. */
export class ApiError extends Error {
  constructor(status, message, details = null) {
    super(message);
    this.status = status;
    this.details = details;
  }
}

export const badRequest = (message, details) => new ApiError(400, message, details);
export const unauthorized = (message = 'Please sign in to continue.') => new ApiError(401, message);
export const forbidden = (message = 'You do not have access to that.') => new ApiError(403, message);
export const notFound = (message = 'Not found.') => new ApiError(404, message);
export const unprocessable = (message, details) => new ApiError(422, message, details);

/** Strip fields the browser has no business seeing. */
export const publicUser = (user) =>
  user && {
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role,
    emailVerified: Boolean(user.email_verified_at),
  };
