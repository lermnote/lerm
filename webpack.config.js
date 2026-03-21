const path = require('path');

const isProd = process.env.NODE_ENV === 'production';

module.exports = {
  entry: './assets/src/index.js', // 项目入口

  output: {
    filename: 'bundle.js',
    path: path.resolve(__dirname, 'assets/dist'),
    clean: true             // 每次打包清理 dist
  },
  module: {
    rules: [
      {
        test: /\.js$/,      // 处理 ES6+
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env', {
              targets: '> 0.5%, last 2 versions, not dead',
              // 按需引入 polyfill，减少体积
              useBuiltIns: 'usage',
              corejs: 3,
            }]
          }
        }
      }
    ]
  },
  mode: isProd ? 'production' : 'development',
  devtool: isProd ? false : 'source-map',
  // 告知 webpack lermData 是外部全局变量，不要打包进去
  externals: {
    lermData: 'lermData',
  }
};
