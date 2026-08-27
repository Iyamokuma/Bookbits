import { sendEmail } from '../src/lib/mailer.js';
import { config } from '../src/config.js';

const to = process.argv[2];

if (!to) {
  console.error('Usage: npm run mail:test -- you@example.com');
  process.exit(1);
}

console.log(`Sending a test email from ${config.mail.fromEmail} to ${to}…`);

const result = await sendEmail({
  to,
  subject: 'Bookbits test email',
  html: `<p>This is a test email from your Bookbits Node application.</p>
         <p>If you're reading this, Resend is configured correctly.</p>`,
});

if (result.ok) {
  console.log('Sent. Check the inbox (and the spam folder).');
} else {
  console.error('Failed:', result.error);
  process.exitCode = 1;
}
