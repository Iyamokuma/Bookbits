export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#eef4ff', 100: '#d9e6ff', 200: '#bcd3ff', 300: '#8eb4ff',
          400: '#5a8bff', 500: '#3563e9', 600: '#1f47c9', 700: '#0b3d91',
          800: '#0a2f70', 900: '#0a2350',
        },
        accent: { 400: '#f6b73c', 500: '#e5a022', 600: '#c48416' },
      },
      fontFamily: { display: ['Georgia', 'serif'] },
    },
  },
  plugins: [],
};
