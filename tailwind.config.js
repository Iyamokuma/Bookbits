export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        // Sampled directly from the logo: #003fa7 blue and #e27a00 orange.
        brand: {
          DEFAULT: '#003fa7',
          dark: '#002f7d',
          light: '#1a5fd0',
          soft: '#dbe7fb',
          muted: '#eff5ff',
          50: '#eff5ff',
          100: '#dbe7fb',
          200: '#bcd2f7',
          300: '#8db4f0',
          400: '#568ce5',
          500: '#2f68d6',
          600: '#1a4fc0',
          700: '#003fa7',
          800: '#003288',
          900: '#002a6e',
        },
        accent: {
          DEFAULT: '#e27a00',
          dark: '#b86300',
          light: '#f59522',
          soft: '#fdf0dd',
           50: '#fdf6ec',
          100: '#fbe8cc',
          200: '#f7cf94',
          300: '#f2b25c',
          400: '#ec9630',
          500: '#e27a00',
          600: '#c26800',
          700: '#9a5200',
          800: '#7c4200',
          900: '#653700',
        },
      },
      fontFamily: {
        // Albert Sans + Syne — readable but far less common than Inter/Playfair stacks.
        sans: ['"Albert Sans"', 'system-ui', 'sans-serif'],
        display: ['Syne', 'system-ui', 'sans-serif'],
        serif: ['Syne', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
