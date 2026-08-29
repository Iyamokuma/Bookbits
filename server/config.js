import 'dotenv/config';

const bool = (v, fallback = false) => {
  if (v === undefined || v === '') return fallback;
  return ['1', 'true', 'yes', 'on'].includes(String(v).toLowerCase());
};

const required = (key) => {
  const v = process.env[key];
  if (!v) {
    throw new Error(
      `Missing required environment variable ${key}. Copy .env.example to .env and fill it in.`
    );
  }
  return v;
};

export const config = {
  env: process.env.NODE_ENV || 'development',
  port: Number(process.env.PORT || 3000),
  baseUrl: (process.env.BASE_URL || 'http://localhost:3000').replace(/\/+$/, ''),

  db: {
    connectionString: required('DATABASE_URL'),
    // Supabase terminates TLS with a cert this pool doesn't have in its trust
    // store, so verification is disabled while the transport stays encrypted.
    ssl: bool(process.env.DATABASE_SSL, true) ? { rejectUnauthorized: false } : false,
  },

  session: {
    secret: required('SESSION_SECRET'),
    maxAgeMs: 1000 * 60 * 60 * 24 * 30,
  },

  admin: {
    email: process.env.ADMIN_EMAIL || 'admin@bookbits.com',
    password: process.env.ADMIN_PASSWORD || 'change-me',
  },

  // Google sign-in, currently suspended. The implementation is complete and
  // tested; set GOOGLE_SIGNIN_ENABLED=true (with a client id) to turn it back
  // on. The flag is deliberately separate from the client id so that setting a
  // client id alone cannot silently re-enable the feature.
  //
  // The client id is not a secret — the browser needs it to open the Google
  // prompt — but the server also checks it as the token audience.
  google: {
    enabled: bool(process.env.GOOGLE_SIGNIN_ENABLED, false),
    clientId: process.env.GOOGLE_CLIENT_ID || '',
  },

  store: {
    name: process.env.STORE_NAME || 'Books, Bits & Co',
    email: process.env.STORE_EMAIL || 'orders@booksandbits.com.ng',
    phone: process.env.STORE_PHONE || '+234 906 003 1555',
    whatsapp: process.env.WHATSAPP_PHONE || '2349060031555',
    facebookUrl: process.env.FACEBOOK_URL || '#',
    instagramUrl: process.env.INSTAGRAM_URL || '#',
    currencySymbol: process.env.CURRENCY_SYMBOL || '\u20a6',
    currencyCode: process.env.CURRENCY_CODE || 'NGN',
  },

  // Object storage for admin-uploaded cover images. Required in any deployment
  // with a read-only or ephemeral filesystem (Vercel); when it is left blank the
  // uploader falls back to writing into public/uploads for local development.
  storage: {
    url: (process.env.SUPABASE_URL || '').replace(/\/+$/, ''),
    serviceKey: process.env.SUPABASE_SERVICE_KEY || '',
    bucket: process.env.SUPABASE_BUCKET || 'covers',
  },

  mail: {
    resendApiKey: process.env.RESEND_API_KEY || '',
    fromEmail: process.env.MAIL_FROM_EMAIL || 'onboarding@resend.dev',
    fromName: process.env.MAIL_FROM_NAME || 'Books, Bits & Co',
  },

  paystack: {
    publicKey: process.env.PAYSTACK_PUBLIC_KEY || '',
    secretKey: process.env.PAYSTACK_SECRET_KEY || '',
  },
  klump: {
    publicKey: process.env.KLUMP_PUBLIC_KEY || '',
    secretKey: process.env.KLUMP_SECRET_KEY || '',
  },
  korapay: {
    publicKey: process.env.KORAPAY_PUBLIC_KEY || '',
    secretKey: process.env.KORAPAY_SECRET_KEY || '',
  },
};

/** Phone digits only, for tel: and wa.me links. */
export const storePhoneTel = () => '+' + config.store.phone.replace(/\D/g, '');

export const absoluteUrl = (path = '/') =>
  config.baseUrl + (path.startsWith('/') ? path : '/' + path);
