import { defineConfig } from 'vite';

export default defineConfig({
  base: './',
  build: {
    target: 'es2019',
    outDir: 'assets/dist',
    emptyOutDir: true,
    sourcemap: false,
    cssCodeSplit: false,
    rollupOptions: {
      input: 'assets/resources/js/index.js',
      external: ['lermData'],
      output: {
        globals: { lermData: 'lermData' },
        entryFileNames: 'bundle.js',
        chunkFileNames: 'chunk-[hash].js',
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name ?? '';

          if (/\.(woff2?|ttf|eot|svg)$/i.test(name)) {
            return '../fonts/[name][extname]';
          }

          if (name.endsWith('.css')) {
            return 'main.css';
          }

          return '[name][extname]';
        },
      },
    },
  },
  css: {
    postcss: './postcss.config.js',
  },
});
