const path = require('path');

module.exports = {
  entry: './assets/src/index.js', // 项目入口
  output: {
    path: path.resolve(__dirname, 'assets/dist'),
    filename: 'bundle.js',
    library: 'LermApp',     // 浏览器全局变量 window.LermApp
    libraryTarget: 'umd',   // 通用模块定义，支持 import/require
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
            presets: ['@babel/preset-env']
          }
        }
      }
    ]
  },
  mode: 'development', // 开发模式
  devtool: 'source-map',    // 调试用
  // devServer: {
  //   static: path.join(__dirname, 'assets/dist'),
  //   hot: true,
  //   port: 3000,
  //   open: true              // 自动打开浏览器
  // }
};
