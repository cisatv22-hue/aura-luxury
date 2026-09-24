// Loaded as a classic script right after the Tailwind Play CDN (see public/AuraShop/index.php).
tailwind.config = {
  theme: {
    extend: {
      colors: {
        brand: {
          bg: '#08080a',
          surface: '#111115',
          card: '#16161c',
          border: '#272730',
          silver: '#e2e8f0',
          muted: '#a1a1aa',
          price: '#fde68a',
          gold: '#f59e0b',
          cream: '#ded8ce',
        },
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        display: ['Cinzel', 'Georgia', 'serif'],
      },
      letterSpacing: {
        luxe: '0.2em',
      },
    },
  },
}
