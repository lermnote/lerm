const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

module.exports = (env, argv) => {
  const isProd = argv && argv.mode === 'production';

  return {
    // 用命名 entry，方便 manifest 映射
    entry: {
      bundle: './assets/src/js/index.js'
    },
    output: {
      path: path.resolve(__dirname, 'assets/dist'),
      filename: isProd ? '[name].[contenthash].js' : '[name].js',
      publicPath: '', // manifest 中我们只放相对文件名，PHP 负责拼接 URI
      clean: true
    },
    module: {
      rules: [
        // JS
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: { loader: 'babel-loader', options: { presets: ['@babel/preset-env'] } }
        },

        // CSS
        {
          test: /\.css$/i,
          use: [
            isProd ? MiniCssExtractPlugin.loader : 'style-loader',
            { loader: 'css-loader', options: { importLoaders: 1 } },
            { loader: 'postcss-loader', options: { postcssOptions: { plugins: ['autoprefixer'] } } }
          ]
        },

        // SCSS
        {
          test: /\.(sa|sc)ss$/i,
          use: [
            isProd ? MiniCssExtractPlugin.loader : 'style-loader',
            'css-loader',
            { loader: 'postcss-loader', options: { postcssOptions: { plugins: ['autoprefixer'] } } },
            'sass-loader'
          ]
        },

        // assets (images/fonts)
        {
          test: /\.(png|jpe?g|gif|svg|woff2?|eot|ttf|otf)$/i,
          type: 'asset/resource',
          generator: { filename: 'assets/[hash][ext][query]' }
        }
      ]
    },
    plugins: [
      // 生产环境抽离 CSS 文件
      ...(isProd ? [new MiniCssExtractPlugin({ filename: 'styles.[contenthash].css' })] : []),

      // manifest 插件：生成 assets/dist/manifest.json
      new WebpackManifestPlugin({
        fileName: 'manifest.json',
        // 我们自定义生成函数：把重要资源映射到固定键（便于 PHP 读取）
        generate: (seed, files, entries) => {
          const manifest = {};

          files.forEach(file => {
            // file: { name: 'bundle.js' | 'styles.css' | 'assets/..', path: 'bundle.abc123.js' }
            const outPath = file.path || file.name;

            if (outPath.endsWith('.js')) {
              // 将第一个 js 输出映射为 bundle.js （因为 entry 名为 bundle）
              // 若有多个 js，再按 entry.name 处理也可以
              manifest['bundle.js'] = outPath;
            } else if (outPath.endsWith('.css')) {
              // 将 css 映射为 styles.css（不关心具体 contenthash）
              manifest['styles.css'] = outPath;
            } else {
              // 其它资源：按原始名字放入 manifest，便于调试或直接引用
              manifest[file.name] = outPath;
            }
          });

          return manifest;
        }
      })
    ],
    mode: isProd ? 'production' : 'development',
    devtool: isProd ? false : 'source-map',
    devServer: {
      static: path.join(__dirname, 'assets/dist'),
      hot: true,
      port: 3000,
      open: true
    },
    optimization: {
      splitChunks: { chunks: 'all' }
    }
  };
};
