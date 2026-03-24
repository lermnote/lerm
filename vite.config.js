import { defineConfig } from 'vite';
import legacy from '@vitejs/plugin-legacy';

export default defineConfig({
  // ── 基础路径 ────────────────────────────────────────────────────────────
  // './' 使 CSS 内的资源路径保持相对，适合 WordPress 主题部署
  base: './',

  // ── 构建输出 ────────────────────────────────────────────────────────────
  build: {
    outDir:      'assets/dist',
    emptyOutDir: true,
    sourcemap:   false,

    // 不按入口拆分 CSS，保持单文件 main.css 与 PHP Enqueue 路径一致
    cssCodeSplit: false,

    rollupOptions: {
      input: 'assets/resources/js/index.js',

      // lermData 由 PHP wp_localize_script 注入，不打包
      external: ['lermData'],
      output: {
        globals: { lermData: 'lermData' },

        entryFileNames: 'bundle.js',
        chunkFileNames: 'chunk-[hash].js',

        assetFileNames: (assetInfo) => {
          const name = assetInfo.name ?? '';

          // 字体文件输出到 assets/fonts/（主题根目录层级），
          // 与 lerm-font.css 内 url('../fonts/...') 路径保持一致
          if (/\.(woff2?|ttf|eot|svg)$/i.test(name)) {
            return '../fonts/[name][extname]';
          }

          // CSS → assets/dist/main.css（PHP Enqueue 硬编码的路径）
          if (name.endsWith('.css')) return 'main.css';

          return '[name][extname]';
        },
      },
    },
  },

  // ── CSS ─────────────────────────────────────────────────────────────────
  css: {
    postcss: './postcss.config.js',
  },

  // ── 插件 ────────────────────────────────────────────────────────────────
  plugins: [
    // plugin-legacy 生成兼容旧浏览器的 bundle（polyfill + 语法降级）
    // 若只面向现代浏览器可删除此插件，改用 build.target: 'es2017'
    legacy({
      targets: ['> 0.5%', 'last 2 versions', 'not dead', 'not IE 11'],
    }),
  ],
});
