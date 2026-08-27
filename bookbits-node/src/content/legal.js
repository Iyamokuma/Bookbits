/**
 * Legal page content, rendered by views/legal.ejs.
 *
 * Section shape:
 *   { heading, paragraphs?: string[], subsections?: [{ heading, paragraphs?, list? }], list?: string[] }
 */

const EMAIL = 'hello@booksbitsandco.com';
const PHONE = '+234 906 003 1555';

export const legalPages = {
  privacy: {
    slug: 'privacy',
    eyebrow: 'Legal',
    title: 'Privacy Policy',
    intro:
      'At Books, Bits & Co., your privacy matters. This Privacy Policy explains what personal information we collect, how we use it, and how we protect it when you use our website.',
    closing: 'Books, Bits & Co. — Committed to your privacy and your reading journey.',
    sections: [
      {
        heading: '1. Who We Are',
        paragraphs: [
          `Books, Bits & Co. is an online bookstore based in Nigeria, operating at booksandbits.com.ng. You can reach us at ${EMAIL} or ${PHONE}.`,
        ],
      },
      {
        heading: '2. Information We Collect',
        subsections: [
          {
            heading: '2.1 Information You Provide',
            list: [
              'Full name and contact details (email, phone number)',
              'Delivery address',
              'Payment information (handled securely by Paystack — we never store card details)',
              'Messages sent via our contact form or WhatsApp',
              'Newsletter subscription details (name and email)',
            ],
          },
          {
            heading: '2.2 Information Collected Automatically',
            list: [
              'IP address and browser type',
              'Pages visited and time on site',
              'Device type (mobile, desktop, tablet)',
            ],
            paragraphs: [
              'This data is collected via cookies and analytics tools (e.g., Google Analytics) in aggregated, non-identifying form.',
            ],
          },
        ],
      },
      {
        heading: '3. How We Use Your Information',
        list: [
          'To process and fulfil your orders',
          'To send order confirmations and delivery updates',
          'To respond to enquiries and provide customer support',
          'To send newsletters and promotions (only if you subscribed)',
          'To improve our website and product offerings',
          'To comply with legal obligations',
        ],
        paragraphs: [
          'We will never use your data for purposes beyond those listed without your explicit consent.',
        ],
      },
      {
        heading: '4. How We Share Your Information',
        paragraphs: ['We do not sell or rent your personal data. We share it only with:'],
        list: [
          'Paystack — to process payments',
          'Delivery/courier partners — name, phone, and address only',
          'Email platforms — to send newsletters and order updates',
        ],
      },
      {
        heading: '5. Cookies',
        paragraphs: [
          'We use cookies to remember your cart, understand site usage, and improve performance. You may disable cookies in your browser settings, though some features may be affected.',
        ],
      },
      {
        heading: '6. Data Retention',
        paragraphs: [
          'We retain personal data only as long as necessary. Order records are kept for a minimum of 5 years for legal and accounting purposes. Newsletter subscriber data is deleted within 30 days of unsubscription.',
        ],
      },
      {
        heading: '7. Your Rights',
        list: [
          'Access the personal information we hold about you',
          'Request correction of inaccurate data',
          'Request deletion of your data (subject to legal requirements)',
          'Withdraw marketing consent at any time',
        ],
        paragraphs: [
          `To exercise your rights, contact us at ${EMAIL}. We will respond within 7 business days.`,
        ],
      },
      {
        heading: '8. Security',
        paragraphs: [
          'We implement appropriate technical and organisational security measures to protect your data. All payment transactions are encrypted via Paystack. However, no internet transmission is 100% secure.',
        ],
      },
      {
        heading: "9. Children's Privacy",
        paragraphs: [
          'Our website is not directed at children under 13. We do not knowingly collect data from children. If you believe a child has shared their data with us, please contact us immediately.',
        ],
      },
      {
        heading: '10. Third-Party Links',
        paragraphs: [
          'Our website may link to third-party platforms (e.g., social media). We are not responsible for their privacy practices and encourage you to review their own policies.',
        ],
      },
      {
        heading: '11. Changes to This Policy',
        paragraphs: [
          'We may update this policy periodically. Changes will be posted with a new effective date. Continued use of our website constitutes acceptance of the updated policy.',
        ],
      },
      {
        heading: '12. Contact Us',
        list: [`Email: ${EMAIL}`, `WhatsApp: ${PHONE}`, 'Website: https://booksandbits.com.ng'],
      },
    ],
  },

  terms: {
    slug: 'terms',
    eyebrow: 'Legal',
    title: 'Terms of Use',
    intro:
      'These terms govern your use of our website and any order you place with Books, Bits & Co.',
    sections: [
      {
        heading: '1. Acceptance of Terms',
        paragraphs: [
          'By browsing our website or placing an order, you confirm that you are at least 18 years old (or have parental consent), and that you agree to these Terms of Use and our Privacy Policy.',
        ],
      },
      {
        heading: '2. About Us',
        paragraphs: [
          'Books, Bits & Co. is a Nigerian online bookstore dedicated to making quality books accessible and rebuilding the culture of reading. We sell physical books and curated bundles. We are not a publisher and do not claim ownership of the books we sell.',
        ],
      },
      {
        heading: '3. Products & Pricing',
        subsections: [
          {
            heading: '3.1 Product Descriptions',
            paragraphs: [
              'We strive to describe all books accurately. Images are illustrative; cover designs may vary by edition. Hardcover and softcover pricing is listed separately where both formats are available.',
            ],
          },
          {
            heading: '3.2 Pricing',
            paragraphs: [
              'All prices are in Nigerian Naira (₦) and may change without prior notice. Delivery fees are shown separately at checkout. The price at checkout is final.',
            ],
          },
          {
            heading: '3.3 Availability',
            paragraphs: [
              'If a book you ordered is out of stock post-payment, we will contact you immediately to offer a refund or alternative.',
            ],
          },
        ],
      },
      {
        heading: '4. Orders & Payments',
        subsections: [
          {
            heading: '4.1 Placing an Order',
            paragraphs: [
              'Completing checkout constitutes a binding offer to purchase. We reserve the right to cancel any order, in which case a full refund will be issued.',
            ],
          },
          {
            heading: '4.2 Payment',
            paragraphs: [
              'Payments are processed securely by Paystack. We accept debit/credit cards, bank transfers, and USSD. We do not store card details.',
            ],
          },
          {
            heading: '4.3 Order Confirmation',
            paragraphs: [
              'Your order is confirmed once payment is successfully processed. A confirmation is sent via WhatsApp and/or email.',
            ],
          },
        ],
      },
      {
        heading: '5. Intellectual Property',
        paragraphs: [
          'All website content — including the Books, Bits & Co. name, logo, blog articles, and design — belongs to Books, Bits & Co. and may not be reproduced or distributed without written permission.',
          'Books sold are the intellectual property of their authors and publishers. Purchasing a book grants personal use only; it does not permit reproduction, resale, or distribution.',
        ],
      },
      {
        heading: '6. User Conduct',
        paragraphs: ['When using our website, you agree not to:'],
        list: [
          'Provide false information during checkout',
          'Use the website for unlawful or fraudulent purposes',
          'Attempt to access restricted areas of the website',
          'Resell our books commercially without written authorisation',
        ],
      },
      {
        heading: '7. Book Club & Newsletter',
        paragraphs: [
          'Participation in our Book Club and newsletter is voluntary. By subscribing, you agree to receive emails about new arrivals, promotions, and reading content. You may unsubscribe at any time by replying STOP or using the unsubscribe link in any email.',
        ],
      },
      {
        heading: '8. Disclaimer',
        paragraphs: [
          'Books, Bits & Co. provides this website and services "as is" without warranties of any kind. We do not guarantee uninterrupted access to the site or that product information is always error-free.',
        ],
      },
      {
        heading: '9. Limitation of Liability',
        paragraphs: [
          'To the extent permitted by Nigerian law, Books, Bits & Co. shall not be liable for indirect, incidental, or consequential damages arising from the use of our website or products.',
        ],
      },
      {
        heading: '10. Governing Law',
        paragraphs: ['These terms are governed by the laws of the Federal Republic of Nigeria.'],
      },
      {
        heading: '11. Changes to These Terms',
        paragraphs: [
          'We may update these terms at any time. Changes take effect upon posting. Continued use of the site constitutes acceptance.',
        ],
      },
      {
        heading: '12. Contact',
        list: [`Email: ${EMAIL}`, `WhatsApp: ${PHONE}`],
      },
    ],
  },

  shipping: {
    slug: 'shipping',
    eyebrow: 'Help',
    title: 'Shipping & Returns Policy',
    intro:
      'At Books, Bits & Co., we are committed to getting your books to you safely, quickly, and in perfect condition. This policy explains how we ship orders, what to expect on delivery, and how to return items if needed.',
    sections: [
      {
        heading: '2. Order Processing',
        subsections: [
          {
            heading: '2.1 Processing Time',
            paragraphs: [
              'All orders are processed within 1–3 business days after payment is confirmed. Orders placed on weekends or public holidays will be processed the next business day.',
            ],
          },
          {
            heading: '2.2 Order Confirmation',
            paragraphs: [
              'You will receive an order confirmation via WhatsApp and/or email once payment is successful. Please keep this for your records.',
            ],
          },
          {
            heading: '2.3 Pre-Orders & Out-of-Stock Items',
            paragraphs: [
              'If a book is temporarily out of stock after you order, we will notify you of the expected delivery date. You may choose to wait or receive a full refund.',
            ],
          },
        ],
      },
      {
        heading: '3. Shipping',
        subsections: [
          {
            heading: '3.1 Delivery Areas',
            paragraphs: ['We deliver across Nigeria. Estimated delivery timelines:'],
            list: [
              'Lagos (Mainland & Island): 1–3 business days',
              'Abuja, Port Harcourt, Ibadan, Kano, Enugu: 3–5 business days',
              'Other states and remote areas: 5–10 business days',
            ],
          },
          {
            heading: '3.2 Shipping Fees',
            paragraphs: [
              'Shipping fees are calculated at checkout based on your location and order weight.',
            ],
          },
          {
            heading: '3.3 Packaging',
            paragraphs: [
              'All books are wrapped protectively to prevent damage in transit — at no extra charge.',
            ],
          },
          {
            heading: '3.4 Order Tracking',
            paragraphs: [
              'A tracking number will be sent to you via WhatsApp or email once your order is dispatched.',
            ],
          },
          {
            heading: '3.5 Failed Delivery Attempts',
            paragraphs: [
              'Our courier will attempt delivery twice. If both attempts fail, the order is returned to us and you will be contacted. Redelivery may incur additional shipping charges.',
            ],
          },
        ],
      },
      {
        heading: '4. Returns Policy',
        subsections: [
          {
            heading: '4.1 Return Window',
            paragraphs: [
              'You may return a book within 7 calendar days of receiving your order, provided the item meets our return conditions.',
            ],
          },
          {
            heading: '4.2 Return Conditions',
            paragraphs: ['To be eligible for a return, the book must:'],
            list: [
              'Be in its original, unread, undamaged condition — no writing, torn pages, broken spine, or visible wear',
              'Be in its original or equivalent protective packaging',
              'Be accompanied by proof of purchase (order number or receipt)',
            ],
          },
          {
            heading: '4.3 Non-Returnable Items',
            paragraphs: ['The following cannot be returned or refunded:'],
            list: [
              'Books that have been read, written in, or damaged after delivery',
              'Pre-loved (second-hand) books — all sales final',
              'E-books or digital products',
              'Complete bundles where individual books have been separated or used',
              'Items returned after the 7-day window',
            ],
          },
          {
            heading: '4.4 How to Return an Item',
            list: [
              `Contact us within 7 days of delivery: WhatsApp ${PHONE} or email ${EMAIL}`,
              'Provide your order number and reason for return',
              'Send photos showing the condition of the book',
              'Our team will review within 24–48 hours and respond with next steps',
              'If approved, we will provide return shipping instructions',
            ],
          },
          {
            heading: '4.5 Return Shipping Costs',
            paragraphs: [
              'Customers bear return shipping costs unless the return is due to our error (wrong item sent, damaged in transit). We recommend using a tracked courier service.',
            ],
          },
        ],
      },
      {
        heading: '5. Refunds',
        subsections: [
          {
            heading: '5.1 Refund Timeline',
            paragraphs: [
              'Once we receive and inspect the returned item, your refund will be processed within 7 business days of approval.',
            ],
          },
          {
            heading: '5.2 Refund Method',
            list: [
              'Card payments: Refunded to the original card via Paystack',
              'Bank transfers: Refunded to your provided bank account',
            ],
          },
          {
            heading: '5.3 Defective or Wrong Items',
            paragraphs: [
              'If you receive a damaged or incorrect book, please contact us within 48 hours of delivery with photos. We will arrange a free replacement or full refund.',
            ],
          },
        ],
      },
      {
        heading: '6. Exchanges',
        paragraphs: [
          'We do not offer direct exchanges at this time. Please initiate a return and place a new order for the desired item.',
        ],
      },
      {
        heading: '7. Contact',
        list: [`Email: ${EMAIL}`, `WhatsApp: ${PHONE}`, 'Instagram: @booksbitsandco'],
        paragraphs: ['Customer service is available Monday–Saturday, 9:00 AM – 6:00 PM (WAT).'],
      },
    ],
  },
};
