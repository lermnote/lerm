const path               = require('path');
const MiniCssExtractPlugin   = require('mini-css-extract-plugin');
const CssMinimizerPlugin     = require('css-minimizer-webpack-plugin');

const isProd = process.env.NODE_ENV === 'production';

module.exports = {
  // ── 入口 ──────────────────────────────────────────────────────────────────
  // 修复：原路径 ./assets/src/index.js 不存在，源码在 resources/
  entry: {
    main: './assets/resources/js/index.js',   // 同时引入 CSS（见 index.js 顶部 import）
  },

  // ── 输出 ──────────────────────────────────────────────────────────────────
  output: {
    filename: 'bundle.js',               // → assets/dist/bundle.js
    path:     path.resolve(__dirname, 'assets/dist'),
    clean:    true,
  },

  // ── 模块处理 ──────────────────────────────────────────────────────────────
  module: {
    rules: [
      // ── JavaScript：ES6+ → ES5（兼容旧浏览器）──────────────────────────
      {
        test:    /\.js$/,
        exclude: /node_modules/,
        use: {
          loader:  'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', {
                targets:      '> 0.5%, last 2 versions, not dead',
                useBuiltIns:  'usage',   // 只注入用到的 polyfill
                corejs:       { version: 3, proposals: true },
              }],
            ],
            // 缓存提升二次构建速度
            cacheDirectory: true,
          },
        },
      },

      // ── CSS pipeline ─────────────────────────────────────────────────────
      // 处理链（从右到左执行）：
      //   postcss-loader  → 自动添加浏览器前缀（autoprefixer）
      //   css-loader      → 解析 @import 和 url()，支持 node_modules 路径
      //   MiniCssExtractPlugin.loader → 将 CSS 提取为独立文件
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader:  'css-loader',
            options: {
              importLoaders: 1,   // 在 css-loader 前运行 1 个 loader（postcss）
              url:           false, // 不处理 url()，字体/图片路径由主题自行管理
            },
          },
          {
            loader:  'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  ['autoprefixer'],
                ],
              },
            },
          },
        ],
      },
    ],
  },

  // ── 插件 ──────────────────────────────────────────────────────────────────
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'main.css',             // → assets/dist/main.css
    }),
  ],

  // ── 优化（仅生产模式）────────────────────────────────────────────────────
  optimization: {
    minimizer: [
      '...',                            // 保留默认 JS 压缩（TerserPlugin）
      new CssMinimizerPlugin(),         // 压缩 CSS
    ],
  },

  // ── 模式与 SourceMap ─────────────────────────────────────────────────────
  mode:    isProd ? 'production' : 'development',
  devtool: isProd ? false : 'source-map',

  // ── 外部依赖（不打包进 bundle）────────────────────────────────────────────
  // lermData 由 PHP wp_localize_script 注入，不需要也不能被打包
  externals: {
    lermData: 'lermData',
  },

  // ── 性能提示（主题场景下 500KB 以下不警告）──────────────────────────────
  performance: {
    hints:            isProd ? 'warning' : false,
    maxAssetSize:     512 * 1024,
    maxEntrypointSize: 512 * 1024,
  },
};